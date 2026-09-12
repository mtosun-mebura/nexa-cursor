<?php

namespace App\Services;

use App\Models\SystemUpgradeLog;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SystemPostgresDockerUpgradeService
{
    public const SERVICE = 'db';

    private const MIN_MAJOR = 16;

    private const IMAGE_PREFIX = 'pgvector/pgvector:';

    public function __construct(
        protected SystemStackSnapshotService $snapshots,
        protected SystemUpgradeService $upgrades,
        protected SystemDockerComposeService $compose,
    ) {}

    /**
     * @return array{
     *     current_tag: string|null,
     *     current_major: int|null,
     *     minor_target: string|null,
     *     major_target: string|null,
     *     can_minor: bool,
     *     can_major: bool,
     *     docker_ready: bool,
     *     message: string,
     *     minor_label: string,
     *     major_label: string
     * }
     */
    public function status(): array
    {
        $tag = $this->currentImageTag();
        $currentMajor = $this->majorFromTag($tag);
        $latestError = null;
        $available = [];
        try {
            $available = $this->availableMajorTags();
        } catch (\Throwable $e) {
            $latestError = $e->getMessage();
        }

        $minorTarget = $tag;
        $majorTarget = $currentMajor !== null
            ? $this->pickNextMajorTag($available, $currentMajor)
            : null;

        $dockerReady = $this->compose->ready();
        $pending = $this->compose->pending(SystemDockerComposeService::KIND_POSTGRES);
        $busy = $pending !== [];
        $web = $this->upgrades->webUpgradeEnabled();

        $canMinor = $web && $dockerReady && $tag !== null && ! $busy;
        $canMajor = $web && $dockerReady && $majorTarget !== null && ! $busy;

        $message = $latestError ?? $this->statusMessage(
            $dockerReady,
            $busy,
            $tag,
            $minorTarget,
            $majorTarget,
        );

        return [
            'current_tag' => $tag,
            'current_major' => $currentMajor,
            'minor_target' => $minorTarget,
            'major_target' => $majorTarget,
            'can_minor' => $canMinor,
            'can_major' => $canMajor,
            'docker_ready' => $dockerReady,
            'message' => $message,
            'minor_label' => $minorTarget
                ? 'Minor-update ('.$minorTarget.')'
                : 'Minor-update',
            'major_label' => $majorTarget
                ? 'Major-update naar '.$majorTarget
                : 'Major-update',
        ];
    }

    /**
     * @param  callable(array<string, mixed>): void|null  $emit
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function run(User $user, string $channel, ?callable $emit = null): array
    {
        $channel = $channel === 'major' ? 'major' : 'minor';
        $status = $this->status();
        if ($channel === 'major' && ! $status['can_major']) {
            throw new \RuntimeException($status['message'] !== ''
                ? $status['message']
                : 'PostgreSQL major-upgrade is nu niet beschikbaar.');
        }
        if ($channel === 'minor' && ! $status['can_minor']) {
            throw new \RuntimeException($status['message'] !== ''
                ? $status['message']
                : 'PostgreSQL minor-upgrade is nu niet beschikbaar.');
        }

        $existing = $this->compose->pendingState();
        $existingKind = is_string($existing['kind'] ?? null) ? $existing['kind'] : null;
        if ($existingKind !== null && $existingKind !== SystemDockerComposeService::KIND_POSTGRES) {
            throw new \RuntimeException('Er loopt al een upgrade. Wacht tot die is afgerond.');
        }

        $started = $this->upgrades->startUpgradeLog($user);
        /** @var SystemUpgradeLog $log */
        $log = $started['log'];
        $steps = $started['steps'];
        $fromRelease = $started['from_release'];
        $toRelease = $started['to_release'];
        $fromStack = is_array($started['from_stack'] ?? null) ? $started['from_stack'] : [];

        $snapshots = [];
        $rewritten = false;

        try {
            $this->upgrades->emitProgress(
                $emit,
                $steps,
                $channel === 'major'
                    ? 'PostgreSQL major: '.($status['current_tag'] ?? '?').' → '.$status['major_target']
                    : 'PostgreSQL minor: image '.($status['minor_target'] ?? '').' vernieuwen',
                'done',
            );

            $dumpPath = $this->createDump($emit, $steps);
            $this->compose->storePending(SystemDockerComposeService::KIND_POSTGRES, [
                'log_id' => $log->id,
                'channel' => $channel,
                'from_tag' => $status['current_tag'],
                'target_tag' => $channel === 'major' ? $status['major_target'] : $status['minor_target'],
                'dump_path' => $dumpPath,
                'user_id' => $user->id,
            ]);

            if ($channel === 'major') {
                $target = (string) $status['major_target'];
                $targetMajor = (int) $this->majorFromTag($target);
                $snapshots = $this->snapshotComposeFiles();
                $this->rewriteComposeFiles($target, $targetMajor);
                $rewritten = true;
                $this->upgrades->emitProgress($emit, $steps, 'Compose-bestanden gezet op '.self::IMAGE_PREFIX.$target, 'done');
                $this->recreateDatabaseService($emit, $steps);
                $this->compose->waitForServiceHealthy(self::SERVICE, 180, $emit);
                $this->restoreDump($dumpPath, $emit, $steps);
            } else {
                $this->compose->runComposeSubcommand(
                    $emit,
                    $steps,
                    ['up', '-d', '--no-deps', '--pull', 'always', '--force-recreate', self::SERVICE],
                    'PostgreSQL-image pullen en container herstarten',
                    900,
                );
                $this->compose->waitForServiceHealthy(self::SERVICE, 180, $emit);
            }

            $this->assertDatabaseAcceptsConnections($emit, $steps);

            $result = $this->upgrades->finishUpgradeLogSuccess(
                $log,
                $steps,
                $emit,
                $fromRelease,
                $toRelease,
                $fromStack,
            );
            $this->compose->clearPending();

            return $result;
        } catch (\Throwable $e) {
            if ($rewritten && $snapshots !== []) {
                try {
                    $this->restoreComposeSnapshots($snapshots);
                    $this->recreateDatabaseService($emit, $steps);
                    $this->compose->waitForServiceHealthy(self::SERVICE, 180, $emit);
                    $this->upgrades->emitProgress($emit, $steps, 'Oude PostgreSQL-versie hersteld na fout', 'done');
                } catch (\Throwable $rollback) {
                    $this->upgrades->emitProgress(
                        $emit,
                        $steps,
                        'Rollback mislukt: '.$rollback->getMessage(),
                        'failed',
                    );
                }
            }
            $this->compose->clearPending();

            return $this->upgrades->finishUpgradeLogFailure($log, $steps, $emit, $fromRelease, $e);
        }
    }

    public function parseImageTag(string $compose): ?string
    {
        if (preg_match('/^\s*image:\s*pgvector\/pgvector:(pg\d+)\s*$/m', $compose, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public function rewriteImageTag(string $compose, string $tag): string
    {
        $replaced = preg_replace(
            '/^(\s*image:\s*pgvector\/pgvector:)pg\d+\s*$/m',
            '${1}'.$tag,
            $compose,
            1,
        );

        return is_string($replaced) ? $replaced : $compose;
    }

    public function rewriteVolumeNameForMajor(string $compose, int $major): string
    {
        $count = 0;
        $updated = preg_replace(
            '/^(\s*name:\s*\$\{COMPOSE_PROJECT_NAME:-[^}]+\}_postgres_data)(?:_pg\d+)?\s*$/m',
            '${1}_pg'.$major,
            $compose,
            1,
            $count,
        );
        if (is_string($updated) && $count > 0) {
            return $updated;
        }

        $inserted = preg_replace(
            '/^(  nexa_postgres_data:\s*)$/m',
            '${1}'."\n    name: \${COMPOSE_PROJECT_NAME:-nexa}_postgres_data_pg".$major,
            $compose,
            1,
            $count,
        );

        return is_string($inserted) && $count > 0 ? $inserted : $compose;
    }

    /**
     * @param  list<string>  $tags
     */
    public function pickNextMajorTag(array $tags, int $currentMajor): ?string
    {
        $majors = [];
        foreach ($tags as $tag) {
            if (preg_match('/^pg(\d+)$/', (string) $tag, $matches) !== 1) {
                continue;
            }
            $major = (int) $matches[1];
            if ($major > $currentMajor && $major >= self::MIN_MAJOR) {
                $majors[$major] = 'pg'.$major;
            }
        }
        if ($majors === []) {
            return null;
        }
        ksort($majors);

        return reset($majors) ?: null;
    }

    /**
     * @return list<string>
     */
    public function availableMajorTags(): array
    {
        $tags = [];
        $url = 'https://hub.docker.com/v2/repositories/pgvector/pgvector/tags?page_size=100&name=pg';
        for ($page = 0; $page < 5; $page++) {
            $response = Http::timeout(20)->acceptJson()->get($url);
            if (! $response->successful() || ! is_array($response->json('results'))) {
                break;
            }
            foreach ($response->json('results') as $row) {
                $name = is_array($row) ? (string) ($row['name'] ?? '') : '';
                if (preg_match('/^pg\d+$/', $name) === 1) {
                    $tags[$name] = $name;
                }
            }
            $next = $response->json('next');
            if (! is_string($next) || $next === '') {
                break;
            }
            $url = $next;
        }

        $list = array_values($tags);
        usort($list, function (string $a, string $b): int {
            return ((int) substr($b, 2)) <=> ((int) substr($a, 2));
        });

        return $list;
    }

    public function currentImageTag(): ?string
    {
        foreach ($this->composeFilePaths() as $path) {
            $tag = $this->parseImageTag((string) file_get_contents($path));
            if ($tag !== null) {
                return $tag;
            }
        }

        $row = $this->compose->containerByService(self::SERVICE);
        $image = is_array($row) ? (string) $row['image'] : '';
        if (preg_match('/pgvector\/pgvector:(pg\d+)/', $image, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function majorFromTag(?string $tag): ?int
    {
        if (! is_string($tag) || preg_match('/^pg(\d+)$/', $tag, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return list<string>
     */
    public function composeFilePaths(): array
    {
        $root = $this->projectRoot();
        $paths = [];
        foreach ([
            $root.'/docker-compose.postgres.yml',
            $root.'/docker-compose.deploy.yml',
        ] as $path) {
            if (is_file($path) && str_contains((string) file_get_contents($path), 'pgvector/pgvector:')) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function rewriteComposeFiles(string $tag, int $major): void
    {
        $paths = $this->composeFilePaths();
        if ($paths === []) {
            throw new \RuntimeException('Geen compose-bestand met pgvector/pgvector-image gevonden.');
        }

        foreach ($paths as $path) {
            $contents = (string) file_get_contents($path);
            $updated = $this->rewriteVolumeNameForMajor($this->rewriteImageTag($contents, $tag), $major);
            if ($updated === $contents) {
                throw new \RuntimeException('Kon '.$path.' niet bijwerken naar '.self::IMAGE_PREFIX.$tag.'.');
            }
            if (file_put_contents($path, $updated) === false) {
                throw new \RuntimeException('Kon '.$path.' niet schrijven.');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function snapshotComposeFiles(): array
    {
        $snapshots = [];
        foreach ($this->composeFilePaths() as $path) {
            $snapshots[$path] = (string) file_get_contents($path);
        }

        return $snapshots;
    }

    /**
     * @param  array<string, string>  $snapshots
     */
    public function restoreComposeSnapshots(array $snapshots): void
    {
        foreach ($snapshots as $path => $contents) {
            file_put_contents($path, $contents);
        }
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function createDump(?callable $emit, array &$steps): string
    {
        $user = $this->postgresUser();
        $path = $this->dumpFilePath();
        $hostPath = $this->dumpPathOnHost($path);
        File::ensureDirectoryExists(dirname($path));
        File::ensureDirectoryExists(dirname($hostPath));
        @unlink($path);
        if ($hostPath !== $path) {
            @unlink($hostPath);
        }

        $this->upgrades->emitProgress($emit, $steps, 'PostgreSQL-backup maken (pg_dumpall)', 'running');
        $result = $this->compose->execInService(
            self::SERVICE,
            'pg_dumpall -U '.$user.' --no-password',
            ['pg_dumpall', '-U', $user, '--no-password'],
            1800,
            $hostPath,
        );
        $sizePath = is_file($path) ? $path : $hostPath;
        if (! $result['success'] || ! is_file($sizePath) || filesize($sizePath) < 64) {
            throw new \RuntimeException(
                'Backup mislukt; de bestaande PostgreSQL-versie blijft ongewijzigd. '.
                trim($result['output'] !== '' ? $result['output'] : 'Dumpbestand is leeg.')
            );
        }
        $this->upgrades->emitProgress(
            $emit,
            $steps,
            'Backup opgeslagen ('.$this->formatBytes((int) filesize($sizePath)).')',
            'done',
        );

        return $sizePath;
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function restoreDump(string $dumpPath, ?callable $emit, array &$steps): void
    {
        $hostDump = $this->dumpPathOnHost($dumpPath);
        $source = is_file($hostDump) ? $hostDump : $dumpPath;
        if (! is_file($source)) {
            throw new \RuntimeException('Backupbestand ontbreekt; rollback naar de oude versie.');
        }
        $user = $this->postgresUser();
        $container = $this->compose->containerByService(self::SERVICE);
        $name = is_array($container) ? (string) $container['name'] : '';
        if ($name === '' || $name === '—') {
            throw new \RuntimeException('Nieuwe database-container heeft geen naam.');
        }

        $cmd = [
            'sh',
            '-c',
            'docker exec -i '.escapeshellarg($name).' psql -U '.escapeshellarg($user).
            ' -d postgres -v ON_ERROR_STOP=1 < '.escapeshellarg($source),
        ];
        $this->compose->runHelperCommand($emit, $steps, $cmd, 'Backup terugzetten in nieuwe PostgreSQL', 1800);
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function recreateDatabaseService(?callable $emit, array &$steps): void
    {
        $this->compose->runComposeSubcommand(
            $emit,
            $steps,
            ['up', '-d', '--no-deps', '--force-recreate', '--remove-orphans', self::SERVICE],
            'Database-container opnieuw opstarten',
            600,
        );
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function assertDatabaseAcceptsConnections(?callable $emit, array &$steps): void
    {
        $user = $this->postgresUser();
        $database = $this->postgresDatabase();
        $result = $this->compose->execInService(
            self::SERVICE,
            'pg_isready -U '.$user.' -d '.$database,
            ['pg_isready', '-U', $user, '-d', $database],
            30,
        );
        if (! $result['success']) {
            throw new \RuntimeException('PostgreSQL accepteert geen verbindingen: '.$result['output']);
        }
        $this->upgrades->emitProgress($emit, $steps, 'PostgreSQL accepteert verbindingen', 'done');
    }

    private function postgresUser(): string
    {
        $env = $this->compose->containerEnv(self::SERVICE);
        $user = trim((string) ($env['POSTGRES_USER'] ?? ''));
        if ($user !== '') {
            return $user;
        }
        $configured = config('database.connections.pgsql.username');

        return is_string($configured) && $configured !== '' ? $configured : 'nexa';
    }

    private function postgresDatabase(): string
    {
        $env = $this->compose->containerEnv(self::SERVICE);
        $database = trim((string) ($env['POSTGRES_DB'] ?? ''));
        if ($database !== '') {
            return $database;
        }
        $configured = config('database.connections.pgsql.database');

        return is_string($configured) && $configured !== '' ? $configured : 'nexa';
    }

    private function dumpFilePath(): string
    {
        return storage_path('app/system-upgrade/postgres-pre-upgrade.sql');
    }

    private function dumpPathOnHost(string $dumpPath): string
    {
        $root = $this->projectRoot();

        return $root.'/backend/storage/app/system-upgrade/'.basename($dumpPath);
    }

    private function projectRoot(): string
    {
        $root = $this->compose->hostProjectDir();
        if (is_string($root) && $root !== '') {
            return $root;
        }

        $parent = realpath(base_path('..'));

        return is_string($parent) ? $parent : dirname(base_path());
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }

    private function statusMessage(
        bool $dockerReady,
        bool $busy,
        ?string $tag,
        ?string $minorTarget,
        ?string $majorTarget,
    ): string {
        if ($busy) {
            return 'Er loopt al een PostgreSQL-upgrade.';
        }
        if (! $this->upgrades->webUpgradeEnabled()) {
            return 'Web-upgrades zijn uitgeschakeld.';
        }
        if (! $this->compose->dockerSocketAvailable()) {
            return 'Docker-socket ontbreekt. PostgreSQL-upgrade is nu niet beschikbaar.';
        }
        if ($this->compose->hostProjectDir() === null) {
            return 'Host-projectmap is onbekend. Zet NEXA_HOST_PROJECT_DIR.';
        }
        if (! $dockerReady) {
            return 'PostgreSQL-upgrade via Docker is nu niet beschikbaar.';
        }
        if ($tag === null) {
            return 'Geen pgvector/pgvector-image in de compose-bestanden gevonden.';
        }
        if ($majorTarget) {
            return 'Minor vernieuwt de huidige image ('.self::IMAGE_PREFIX.$minorTarget.
                ') met backup. Major migreert naar '.self::IMAGE_PREFIX.$majorTarget.
                ' via dump/restore; bij een fout gaat de oude versie weer aan.';
        }

        return 'Al op '.self::IMAGE_PREFIX.$tag.'. Minor pullt de nieuwste patch van deze major, met backup.';
    }
}
