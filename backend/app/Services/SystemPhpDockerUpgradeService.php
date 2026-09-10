<?php

namespace App\Services;

use App\Models\SystemUpgradeLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class SystemPhpDockerUpgradeService
{
    private const MIN_PHP_MINOR = '8.2';

    public function __construct(
        protected SystemStackSnapshotService $snapshots,
        protected SystemUpgradeService $upgrades,
        protected SystemDockerComposeService $compose,
    ) {}

    /**
     * @return array{
     *     current_php: string,
     *     dockerfile_tag: string|null,
     *     latest_tag: string|null,
     *     can_run: bool,
     *     needs_rebuild: bool,
     *     up_to_date: bool,
     *     pending_finalize: bool,
     *     docker_ready: bool,
     *     message: string,
     *     button_label: string
     * }
     */
    public function status(): array
    {
        $currentPhp = PHP_VERSION;
        $tag = $this->currentDockerfileTag();
        $latest = null;
        $latestError = null;

        try {
            $latest = $this->resolveLatestCliTag();
        } catch (\Throwable $e) {
            $latestError = $e->getMessage();
        }

        $dockerReady = $this->compose->ready();
        $pending = $this->compose->pending(SystemDockerComposeService::KIND_PHP);
        $pendingFinalize = $this->shouldFinalize($pending, $latest ?? $tag);

        $needsRebuild = is_string($latest) && is_string($tag) && $latest !== $tag;
        $runtimeMatchesLatest = is_string($latest) && $this->runtimeMatchesCliTag($latest);
        $upToDate = is_string($latest) && is_string($tag) && $tag === $latest && $runtimeMatchesLatest;
        $needsUpgrade = is_string($latest) && ! $upToDate;
        $canRun = $this->upgrades->webUpgradeEnabled() && $dockerReady && $needsUpgrade && ! $pendingFinalize;

        $message = $latestError ?? $this->statusMessage(
            $dockerReady,
            $needsRebuild,
            $upToDate,
            $pendingFinalize,
            $tag,
            $latest,
        );

        $buttonLabel = $upToDate && $latest
            ? 'PHP is actueel ('.$latest.')'
            : ($needsRebuild && $latest
                ? 'PHP upgraden naar '.$latest
                : ('PHP-image herbouwen'.($latest ? ' ('.$latest.')' : '')));

        return [
            'current_php' => $currentPhp,
            'dockerfile_tag' => $tag,
            'latest_tag' => $latest,
            'can_run' => $canRun,
            'needs_rebuild' => $needsRebuild,
            'up_to_date' => $upToDate,
            'pending_finalize' => $pendingFinalize,
            'docker_ready' => $dockerReady,
            'message' => $message,
            'button_label' => $buttonLabel,
        ];
    }

    /**
     * @param  callable(array<string, mixed>): void|null  $emit
     * @return array{log: SystemUpgradeLog, success: bool, message: string, reconnect?: bool}
     */
    public function run(User $user, ?callable $emit = null): array
    {
        $status = $this->status();
        if ($status['pending_finalize']) {
            return $this->finalize($emit);
        }

        if (! $status['can_run']) {
            throw new \RuntimeException($status['message'] !== ''
                ? $status['message']
                : ($status['up_to_date']
                    ? 'PHP is al actueel.'
                    : 'Docker is niet beschikbaar vanuit deze container.'));
        }

        $latest = $status['latest_tag'] ?? $this->resolveLatestCliTag();
        $started = $this->upgrades->startUpgradeLog($user);
        /** @var SystemUpgradeLog $log */
        $log = $started['log'];
        $steps = $started['steps'];
        $fromRelease = $started['from_release'];
        $toRelease = $started['to_release'];

        try {
            $this->upgrades->emitProgress($emit, $steps, 'Nieuwste PHP-image: php:'.$latest, 'done');

            $this->rewriteDockerfiles($latest);
            $this->upgrades->emitProgress($emit, $steps, 'Dockerfiles bijgewerkt naar php:'.$latest, 'done');

            $this->compose->storePending(SystemDockerComposeService::KIND_PHP, [
                'log_id' => $log->id,
                'from_release' => $fromRelease,
                'to_release' => $toRelease,
                'target_tag' => $latest,
                'previous_tag' => $status['dockerfile_tag'],
                'user_id' => $user->id,
                'steps' => $steps,
            ]);

            $this->upgrades->emitProgress($emit, $steps, 'Docker-stack bouwen en herstarten', 'running');
            $this->emit($emit, 'note', [
                'note' => 'Alle compose-services worden opnieuw opgetuigd (build waar de image is veranderd). De admin is even niet bereikbaar. Daarna volgen automatisch de tests.',
            ]);
            $this->emit($emit, 'reconnect', ['pending_finalize' => true]);

            $this->compose->recreateStack($emit, $steps);

            // Als we hier aankomen is de container niet vervangen (bijv. zelfde image).
            return $this->finalize($emit, $log, $steps, $fromRelease, $toRelease);
        } catch (\Throwable $e) {
            $this->compose->clearPending();

            return $this->upgrades->finishUpgradeLogFailure($log, $steps, $emit, $fromRelease, $e);
        }
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>|null  $steps
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function finalize(
        ?callable $emit = null,
        ?SystemUpgradeLog $log = null,
        ?array $steps = null,
        ?string $fromRelease = null,
        ?string $toRelease = null,
    ): array {
        $pending = $this->compose->pending(SystemDockerComposeService::KIND_PHP);
        if ($log === null) {
            $logId = (int) ($pending['log_id'] ?? 0);
            $log = $logId > 0 ? SystemUpgradeLog::query()->find($logId) : null;
        }

        if (! $log instanceof SystemUpgradeLog) {
            throw new \RuntimeException('Geen openstaande PHP-upgrade om af te ronden.');
        }

        $steps = $steps ?? (is_array($pending['steps'] ?? null) ? $pending['steps'] : []);
        $fromRelease = $fromRelease ?? (string) ($pending['from_release'] ?? $log->from_release);
        $toRelease = $toRelease ?? (string) ($pending['to_release'] ?? $log->to_release ?? $this->snapshots->bumpReleasePatch($fromRelease));

        try {
            $this->upgrades->emitProgress(
                $emit,
                $steps,
                'Nieuwe PHP-runtime: '.PHP_VERSION,
                'done',
            );
            $this->upgrades->runStabilityTests($emit, $steps);
            $result = $this->upgrades->finishUpgradeLogSuccess(
                $log,
                $steps,
                $emit,
                $fromRelease,
                $toRelease,
                is_array($log->from_stack) ? $log->from_stack : [],
            );
            $this->compose->clearPending();

            return $result;
        } catch (\Throwable $e) {
            $this->compose->clearPending();

            return $this->upgrades->finishUpgradeLogFailure($log, $steps, $emit, $fromRelease, $e);
        }
    }

    public function currentDockerfileTag(): ?string
    {
        foreach ($this->dockerfilePaths() as $path) {
            if (! is_file($path)) {
                continue;
            }
            $tag = $this->parseFromTag((string) file_get_contents($path));
            if ($tag !== null) {
                return $tag;
            }
        }

        return null;
    }

    public function parseFromTag(string $dockerfile): ?string
    {
        if (preg_match('/^FROM\s+php:(\S+)/m', $dockerfile, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public function rewriteDockerfileFrom(string $contents, string $tag): string
    {
        $replaced = preg_replace('/^FROM\s+php:\S+/m', 'FROM php:'.$tag, $contents, 1);

        return is_string($replaced) ? $replaced : $contents;
    }

    /**
     * @param  list<string>  $versions
     */
    public function pickLatestStableMinor(array $versions): ?string
    {
        $minors = [];
        foreach ($versions as $version) {
            $version = ltrim(trim((string) $version), 'v');
            if (preg_match('/^(\d+\.\d+)/', $version, $matches) !== 1) {
                continue;
            }
            $minor = $matches[1];
            if (version_compare($minor, self::MIN_PHP_MINOR, '<')) {
                continue;
            }
            $minors[$minor] = true;
        }

        if ($minors === []) {
            return null;
        }

        $list = array_keys($minors);
        usort($list, fn (string $a, string $b) => version_compare($b, $a));

        return $list[0];
    }

    public function resolveLatestCliTag(): string
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->get('https://www.php.net/releases/index.php', [
                'json' => 1,
                'version' => 8,
                'max' => 40,
            ]);

        if (! $response->successful() || ! is_array($response->json())) {
            throw new \RuntimeException('Kon de nieuwste PHP-versie niet ophalen bij php.net.');
        }

        $minor = $this->pickLatestStableMinor(array_keys($response->json()));
        if ($minor === null) {
            throw new \RuntimeException('Geen geschikte PHP 8-release gevonden.');
        }

        $tag = $minor.'-cli';
        if (! $this->dockerHubTagExists($tag)) {
            throw new \RuntimeException('Docker Hub heeft nog geen php:'.$tag.' image.');
        }

        return $tag;
    }

    public function dockerReady(): bool
    {
        return $this->compose->ready();
    }

    public function runtimeMatchesCliTag(string $tag): bool
    {
        $minor = preg_replace('/-cli$/', '', $tag);
        if (! is_string($minor) || $minor === '') {
            return false;
        }

        return str_starts_with(PHP_VERSION, $minor.'.') || PHP_VERSION === $minor;
    }

    public function rewriteDockerfiles(string $tag): void
    {
        $paths = $this->dockerfilePaths();
        if ($paths === []) {
            throw new \RuntimeException('Geen Dockerfile gevonden om PHP-image in te zetten.');
        }

        $onTarget = 0;
        foreach ($paths as $path) {
            $contents = (string) file_get_contents($path);
            $updated = $this->rewriteDockerfileFrom($contents, $tag);
            if ($updated !== $contents && file_put_contents($path, $updated) === false) {
                throw new \RuntimeException('Kon '.$path.' niet bijwerken.');
            }
            if ($this->parseFromTag($updated) === $tag) {
                $onTarget++;
            }
        }

        if ($onTarget === 0) {
            throw new \RuntimeException('Geen Dockerfile gevonden om PHP-image in te zetten.');
        }
    }

    /**
     * @return list<string>
     */
    public function dockerfilePaths(): array
    {
        $backend = $this->backendDir();

        return array_values(array_filter([
            $backend.'/Dockerfile',
            $backend.'/Dockerfile.prod',
        ], 'is_file'));
    }

    private function backendDir(): string
    {
        $root = $this->compose->hostProjectDir();
        if (is_string($root) && is_dir($root.'/backend')) {
            return $root.'/backend';
        }

        return base_path();
    }

    private function dockerHubTagExists(string $tag): bool
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get('https://hub.docker.com/v2/repositories/library/php/tags/'.$tag);

            return $response->successful();
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @param  array<string, mixed>  $pending
     */
    private function shouldFinalize(array $pending, ?string $targetTag): bool
    {
        if ($pending === [] || empty($pending['log_id'])) {
            return false;
        }

        $log = SystemUpgradeLog::query()->find((int) $pending['log_id']);
        if (! $log instanceof SystemUpgradeLog || $log->status !== SystemUpgradeLog::STATUS_RUNNING) {
            $this->compose->clearPending();

            return false;
        }

        $expected = (string) ($pending['target_tag'] ?? $targetTag ?? '');
        if ($expected === '') {
            return true;
        }

        $currentTag = $this->currentDockerfileTag();

        return $currentTag === $expected || str_starts_with(PHP_VERSION, rtrim($expected, '-cli'));
    }

    private function statusMessage(
        bool $dockerReady,
        bool $needsRebuild,
        bool $upToDate,
        bool $pendingFinalize,
        ?string $tag,
        ?string $latest,
    ): string {
        if ($pendingFinalize) {
            return 'De Docker-stack is herstart. Tests worden nu afgerond.';
        }
        if (! $this->upgrades->webUpgradeEnabled()) {
            return 'Web-upgrades zijn uitgeschakeld.';
        }
        if (! $this->compose->dockerSocketAvailable()) {
            return 'Docker-socket ontbreekt. Herstart de backend-container met de bijgewerkte compose-config (docker compose up -d).';
        }
        if ($this->compose->hostProjectDir() === null) {
            return 'Host-projectmap is niet gemount. Zet NEXA_HOST_PROJECT_DIR of herstart via docker compose up -d.';
        }
        if (! $dockerReady) {
            return 'PHP-upgrade via Docker is nu niet beschikbaar.';
        }
        if ($upToDate && $latest) {
            return 'Al op php:'.$latest.'. Er is geen nieuwere PHP-image.';
        }
        if ($needsRebuild && $latest && $tag) {
            return 'Beschikbaar: php:'.$tag.' → php:'.$latest.'. Daarna worden de Docker-containers opnieuw opgetuigd en tests gedraaid.';
        }
        if ($latest) {
            return 'De runtime wijkt af van php:'.$latest.'. Een herbouw tuigt de stack opnieuw op en draait daarna tests.';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function emit(?callable $emit, string $type, array $payload = []): void
    {
        if ($emit === null) {
            return;
        }

        $emit(array_merge(['type' => $type], $payload));
    }
}
