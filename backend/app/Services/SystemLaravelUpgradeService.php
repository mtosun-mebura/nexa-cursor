<?php

namespace App\Services;

use App\Models\SystemUpgradeLog;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;

class SystemLaravelUpgradeService
{
    public function __construct(
        protected SystemStackSnapshotService $snapshots,
        protected SystemUpgradeService $upgrades,
        protected SystemDockerComposeService $compose,
    ) {}

    /**
     * @return array{
     *     current: string,
     *     constraint: string|null,
     *     minor_target: string|null,
     *     major_target: string|null,
     *     can_minor: bool,
     *     can_major: bool,
     *     major_blocked_reason: string|null,
     *     pending_finalize: bool,
     *     docker_ready: bool,
     *     message: string
     * }
     */
    public function status(): array
    {
        $current = ltrim((string) Application::VERSION, 'v');
        $constraint = $this->currentConstraint();
        $versions = [];
        $fetchError = null;

        try {
            $versions = $this->packagistStableVersions();
        } catch (\Throwable $e) {
            $fetchError = $e->getMessage();
        }

        $currentMajor = $this->majorOf($current);
        $minorTarget = $this->latestInMajor($versions, $currentMajor);
        $nextMajor = $currentMajor !== null ? $currentMajor + 1 : null;
        $majorPackage = $nextMajor !== null ? $this->latestPackageInMajor($versions, $nextMajor) : null;
        $majorTarget = is_array($majorPackage) ? $this->normalizeVersion((string) ($majorPackage['version'] ?? '')) : null;

        $canMinor = $minorTarget !== null && version_compare($minorTarget, $current, '>');
        $majorBlocked = $this->majorBlockedReason($majorPackage);
        $canMajor = $majorTarget !== null && $majorBlocked === null && $this->upgrades->webUpgradeEnabled();

        $message = $fetchError ?? $this->statusMessage($current, $minorTarget, $majorTarget, $canMinor, $canMajor, $majorBlocked);
        $pending = $this->compose->pending(SystemDockerComposeService::KIND_LARAVEL);
        $pendingFinalize = $this->shouldFinalize($pending);

        return [
            'current' => $current,
            'constraint' => $constraint,
            'minor_target' => $canMinor ? $minorTarget : null,
            'major_target' => $majorTarget,
            'can_minor' => $canMinor && $this->upgrades->webUpgradeEnabled() && ! $pendingFinalize,
            'can_major' => $canMajor && ! $pendingFinalize,
            'major_blocked_reason' => $majorBlocked,
            'pending_finalize' => $pendingFinalize,
            'docker_ready' => $this->compose->ready(),
            'message' => $pendingFinalize
                ? 'De Docker-stack is herstart. De Laravel-upgrade wordt nu afgerond.'
                : $message,
        ];
    }

    /**
     * @param  callable(array<string, mixed>): void|null  $emit
     * @return array{log: SystemUpgradeLog, success: bool, message: string, reconnect?: bool}
     */
    public function run(User $user, string $channel, ?callable $emit = null): array
    {
        if (app()->environment('testing') && ! config('nexa.laravel_upgrade_allow_in_tests')) {
            throw new \RuntimeException('Laravel-upgrade is in tests geblokkeerd.');
        }

        $status = $this->status();
        if ($status['pending_finalize']) {
            return $this->finalize($emit);
        }

        $channel = $channel === 'major' ? 'major' : 'minor';

        if ($channel === 'minor' && ! $status['can_minor']) {
            throw new \RuntimeException($status['message'] !== ''
                ? $status['message']
                : 'Geen Laravel minor-update beschikbaar.');
        }
        if ($channel === 'major' && ! $status['can_major']) {
            throw new \RuntimeException($status['major_blocked_reason'] ?: 'Geen Laravel major-update beschikbaar.');
        }

        $composerJson = base_path('composer.json');
        $composerLock = base_path('composer.lock');
        $jsonBackup = is_file($composerJson) ? (string) file_get_contents($composerJson) : null;
        $lockBackup = is_file($composerLock) ? (string) file_get_contents($composerLock) : null;
        $restored = false;

        $restore = function () use ($composerJson, $composerLock, $jsonBackup, $lockBackup, &$restored, $emit): void {
            if ($restored) {
                return;
            }
            $restored = true;
            if (is_string($jsonBackup)) {
                file_put_contents($composerJson, $jsonBackup);
            }
            if (is_string($lockBackup)) {
                file_put_contents($composerLock, $lockBackup);
            }
            try {
                $steps = [];
                $this->upgrades->runProcessCommand(
                    $emit,
                    $steps,
                    'Composer-bestanden terugzetten',
                    ['composer', 'install', '--no-interaction', '--no-ansi', '--prefer-dist'],
                    600,
                );
            } catch (\Throwable) {
                // Herstel best-effort; de fout van de upgrade blijft leidend.
            }
        };

        $started = $this->upgrades->startUpgradeLog($user);
        /** @var SystemUpgradeLog $log */
        $log = $started['log'];
        $steps = $started['steps'];
        $fromRelease = $started['from_release'];
        $toRelease = $started['to_release'];
        $fromStack = $started['from_stack'];

        try {
            if ($channel === 'major') {
                $targetMajor = $this->majorOf((string) $status['major_target']);
                $this->upgrades->emitProgress($emit, $steps, 'Laravel major naar '.$status['major_target'], 'done');
                $this->bumpComposerForMajor((int) $targetMajor, $status['major_target']);
                $this->upgrades->runProcessCommand(
                    $emit,
                    $steps,
                    'Laravel '.$status['major_target'].' installeren',
                    [
                        'composer', 'require', 'laravel/framework:^'.$targetMajor.'.0',
                        '--update-with-all-dependencies', '--no-interaction', '--no-ansi', '--prefer-dist',
                    ],
                    900,
                );
            } else {
                $this->upgrades->emitProgress($emit, $steps, 'Laravel minor naar '.$status['minor_target'], 'done');
                $this->upgrades->runProcessCommand(
                    $emit,
                    $steps,
                    'Laravel binnen huidige major bijwerken',
                    [
                        'composer', 'update', 'laravel/framework',
                        '--with-all-dependencies', '--no-interaction', '--no-ansi', '--prefer-dist',
                    ],
                    900,
                );
            }

            $this->upgrades->runDatabaseMigrations($emit, $steps);
            $this->upgrades->runStabilityTests($emit, $steps);

            if (! $this->compose->ready()) {
                $this->upgrades->emitProgress($emit, $steps, 'Docker-stack overgeslagen: socket of projectmap ontbreekt', 'skipped');

                return $this->upgrades->finishUpgradeLogSuccess($log, $steps, $emit, $fromRelease, $toRelease, $fromStack);
            }

            $this->compose->storePending(SystemDockerComposeService::KIND_LARAVEL, [
                'log_id' => $log->id,
                'from_release' => $fromRelease,
                'to_release' => $toRelease,
                'user_id' => $user->id,
                'steps' => $steps,
                'tests_done' => true,
            ]);

            $this->upgrades->emitProgress($emit, $steps, 'Docker-stack bouwen en herstarten', 'running');
            if ($emit !== null) {
                $emit(['type' => 'note', 'note' => 'Alle compose-services worden opnieuw opgetuigd (build waar de image is veranderd). De admin is even niet bereikbaar.']);
                $emit(['type' => 'reconnect', 'pending_finalize' => true]);
            }

            $this->compose->recreateStack($emit, $steps);

            return $this->finalize($emit, $log, $steps, $fromRelease, $toRelease);
        } catch (\Throwable $e) {
            $restore();
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
        $pending = $this->compose->pending(SystemDockerComposeService::KIND_LARAVEL);
        if ($log === null) {
            $logId = (int) ($pending['log_id'] ?? 0);
            $log = $logId > 0 ? SystemUpgradeLog::query()->find($logId) : null;
        }

        if (! $log instanceof SystemUpgradeLog) {
            throw new \RuntimeException('Geen openstaande Laravel-upgrade om af te ronden.');
        }

        $steps = $steps ?? (is_array($pending['steps'] ?? null) ? $pending['steps'] : []);
        $fromRelease = $fromRelease ?? (string) ($pending['from_release'] ?? $log->from_release);
        $toRelease = $toRelease ?? (string) ($pending['to_release'] ?? $log->to_release ?? $this->snapshots->bumpReleasePatch($fromRelease));

        try {
            $this->upgrades->emitProgress($emit, $steps, 'Docker-stack opnieuw opgetuigd', 'done');
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

    /**
     * @param  array<string, mixed>  $pending
     */
    private function shouldFinalize(array $pending): bool
    {
        if ($pending === [] || empty($pending['log_id'])) {
            return false;
        }

        $log = SystemUpgradeLog::query()->find((int) $pending['log_id']);
        if (! $log instanceof SystemUpgradeLog || $log->status !== SystemUpgradeLog::STATUS_RUNNING) {
            $this->compose->clearPending();

            return false;
        }

        return true;
    }

    public function currentConstraint(): ?string
    {
        $path = base_path('composer.json');
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        $constraint = $data['require']['laravel/framework'] ?? null;

        return is_string($constraint) ? $constraint : null;
    }

    public function replaceRequireConstraint(string $composerJson, string $package, string $constraint): string
    {
        $pattern = '/("'.preg_quote($package, '/').'"\s*:\s*")([^"]+)(")/';
        $replaced = preg_replace($pattern, '${1}'.$constraint.'${3}', $composerJson, 1);

        return is_string($replaced) ? $replaced : $composerJson;
    }

    /**
     * @param  list<array<string, mixed>>  $packages
     */
    public function latestInMajor(array $packages, ?int $major): ?string
    {
        $package = $this->latestPackageInMajor($packages, $major);

        return is_array($package) ? $this->normalizeVersion((string) ($package['version'] ?? '')) : null;
    }

    public function bumpComposerForMajor(int $major, ?string $targetVersion = null): void
    {
        $path = base_path('composer.json');
        if (! is_file($path)) {
            throw new \RuntimeException('composer.json ontbreekt.');
        }

        $contents = (string) file_get_contents($path);
        $phpConstraint = $this->phpConstraintForTarget($targetVersion);
        if ($phpConstraint !== null) {
            $contents = $this->replaceRequireConstraint($contents, 'php', $phpConstraint);
        }
        $contents = $this->replaceRequireConstraint($contents, 'laravel/framework', '^'.$major.'.0');

        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException('Kon composer.json niet bijwerken.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function packagistStableVersions(): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->get('https://repo.packagist.org/p2/laravel/framework.json');

        if (! $response->successful()) {
            throw new \RuntimeException('Kon Laravel-versies niet ophalen bij Packagist.');
        }

        $packages = $response->json('packages.laravel/framework');
        if (! is_array($packages)) {
            throw new \RuntimeException('Ongeldig Packagist-antwoord voor laravel/framework.');
        }

        return array_values(array_filter($packages, function ($package): bool {
            if (! is_array($package)) {
                return false;
            }
            $version = $this->normalizeVersion((string) ($package['version'] ?? ''));

            return $version !== '' && preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;
        }));
    }

    /**
     * @param  array<string, mixed>|null  $package
     */
    private function majorBlockedReason(?array $package): ?string
    {
        if (! $this->upgrades->webUpgradeEnabled()) {
            return 'Web-upgrades zijn uitgeschakeld.';
        }
        if ($package === null) {
            return 'Geen nieuwere Laravel-major beschikbaar.';
        }

        $requiredPhp = $this->minimumPhpFromConstraint((string) ($package['require']['php'] ?? ''));
        if ($requiredPhp !== null && version_compare(PHP_VERSION, $requiredPhp, '<')) {
            return 'Laravel '.$this->normalizeVersion((string) ($package['version'] ?? '')).' vereist PHP '.$requiredPhp.' of hoger (nu '.PHP_VERSION.'). Upgrade eerst PHP.';
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $packages
     * @return array<string, mixed>|null
     */
    private function latestPackageInMajor(array $packages, ?int $major): ?array
    {
        if ($major === null) {
            return null;
        }

        $best = null;
        $bestVersion = null;
        foreach ($packages as $package) {
            $version = $this->normalizeVersion((string) ($package['version'] ?? ''));
            if ($this->majorOf($version) !== $major) {
                continue;
            }
            if ($bestVersion === null || version_compare($version, $bestVersion, '>')) {
                $best = $package;
                $bestVersion = $version;
            }
        }

        return $best;
    }

    private function phpConstraintForTarget(?string $targetVersion): ?string
    {
        if ($targetVersion === null) {
            return null;
        }

        try {
            $packages = $this->packagistStableVersions();
        } catch (\Throwable) {
            return '^8.3';
        }

        foreach ($packages as $package) {
            if ($this->normalizeVersion((string) ($package['version'] ?? '')) !== $this->normalizeVersion($targetVersion)) {
                continue;
            }
            $php = (string) ($package['require']['php'] ?? '');
            $min = $this->minimumPhpFromConstraint($php);

            return $min !== null ? '^'.$min : null;
        }

        return null;
    }

    private function minimumPhpFromConstraint(string $constraint): ?string
    {
        if (preg_match('/(\d+\.\d+)/', $constraint, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function majorOf(string $version): ?int
    {
        $version = $this->normalizeVersion($version);
        if ($version === '' || ! preg_match('/^(\d+)\./', $version, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function normalizeVersion(string $version): string
    {
        $version = ltrim(trim($version), 'v');
        if (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) === 1) {
            return $matches[1];
        }

        return $version;
    }

    private function statusMessage(
        string $current,
        ?string $minorTarget,
        ?string $majorTarget,
        bool $canMinor,
        bool $canMajor,
        ?string $majorBlocked,
    ): string {
        if (! $this->upgrades->webUpgradeEnabled()) {
            return 'Web-upgrades zijn uitgeschakeld.';
        }
        if ($canMinor && $canMajor) {
            return 'Minor naar '.$minorTarget.' of major naar '.$majorTarget.'. Na de upgrade volgen migraties en tests.';
        }
        if ($canMinor) {
            return 'Minor-update naar '.$minorTarget.' beschikbaar. Daarna volgen migraties en tests.'
                .($majorBlocked ? ' Major: '.$majorBlocked : '');
        }
        if ($canMajor) {
            return 'Al op de nieuwste '.$this->majorOf($current).'.x. Major naar '.$majorTarget.' is beschikbaar. Daarna volgen migraties en tests.';
        }
        if ($majorBlocked && $majorTarget) {
            return 'Laravel '.$current.' is actueel binnen deze major. '.$majorBlocked;
        }

        return 'Laravel '.$current.' is actueel. Er is geen nieuwere minor of major beschikbaar.';
    }
}
