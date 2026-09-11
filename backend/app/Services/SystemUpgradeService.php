<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\SystemUpgradeLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;

class SystemUpgradeService
{
    public function __construct(
        protected SystemStackSnapshotService $snapshots,
    ) {}

    public function webUpgradeEnabled(): bool
    {
        return (bool) config('nexa.web_upgrade_enabled', true);
    }

    /**
     * @param  list<string>  $selections
     * @param  callable(array<string, mixed>): void|null  $emit
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function runUpgrade(User $user, array $selections, ?callable $emit = null): array
    {
        if (! $this->webUpgradeEnabled()) {
            throw new \RuntimeException('Web-upgrades zijn uitgeschakeld. Zet NEXA_WEB_UPGRADE_ENABLED=true in .env.');
        }

        $selections = array_values(array_unique(array_filter($selections, fn ($id) => is_string($id) && $id !== '')));
        if ($selections === []) {
            throw new \InvalidArgumentException('Selecteer minimaal één item om te upgraden.');
        }

        $fromRelease = $this->snapshots->currentReleaseVersion();
        $fromStack = $this->snapshots->capture();
        $toRelease = $this->snapshots->bumpReleasePatch($fromRelease);

        $log = SystemUpgradeLog::query()->create([
            'from_release' => $fromRelease,
            'to_release' => $toRelease,
            'status' => SystemUpgradeLog::STATUS_RUNNING,
            'from_stack' => $fromStack,
            'triggered_by_user_id' => $user->id,
            'started_at' => now(),
        ]);

        $steps = [];

        try {
            $this->step($emit, $steps, 'Huidige stack vastgelegd', 'done');
            $this->step($emit, $steps, count($selections).' item(s) geselecteerd voor upgrade', 'done');

            $composerPackages = $this->composerPackagesFromSelections($selections);
            if ($composerPackages !== [] || in_array('step:composer', $selections, true)) {
                $command = $composerPackages !== []
                    ? 'composer update '.implode(' ', array_map('escapeshellarg', $composerPackages)).' --with-all-dependencies --no-interaction --no-ansi --prefer-dist --no-scripts'
                    : 'composer update --with-all-dependencies --no-interaction --no-ansi --prefer-dist --no-scripts';
                $this->runShellStep($emit, $steps, 'Composer dependencies bijwerken', $command, 900);
                $this->refreshApplicationAfterComposer($emit, $steps);
            }

            $npmPackages = $this->npmPackagesFromSelections($selections);
            $runNpm = $npmPackages !== [] || in_array('step:npm', $selections, true);
            if ($runNpm && $this->commandExists('npm')) {
                $npmCommand = $npmPackages !== []
                    ? 'npm update '.implode(' ', array_map('escapeshellarg', $npmPackages)).' --no-fund --no-audit'
                    : 'npm update --no-fund --no-audit';
                $this->runShellStep($emit, $steps, 'NPM dependencies bijwerken', $npmCommand, 600);
            }

            $shouldBuild = in_array('step:npm_build', $selections, true);
            if ($shouldBuild && $this->commandExists('npm')) {
                $this->runShellStep($emit, $steps, 'Frontend assets bouwen', 'npm run build', 600);
            } elseif ($shouldBuild) {
                $this->step($emit, $steps, 'NPM niet beschikbaar — frontend build overgeslagen', 'skipped');
            }

            if (in_array('step:migrations', $selections, true)) {
                $this->runArtisanStep($emit, $steps, 'Database migraties uitvoeren', ['migrate', '--force'], 300);
            }

            if (in_array('step:tests', $selections, true)) {
                $this->runStabilityTests($emit, $steps);
            }

            $toStack = $this->snapshots->capture();
            GeneralSetting::set('nexa_release_version', $toRelease);

            $log->update([
                'status' => SystemUpgradeLog::STATUS_SUCCESS,
                'to_release' => $toRelease,
                'to_stack' => $toStack,
                'steps_log' => $steps,
                'completed_at' => now(),
            ]);

            $this->step($emit, $steps, 'Upgrade voltooid: '.$fromRelease.' → '.$toRelease, 'done');
            $this->emit($emit, 'summary', [
                'from_release' => $fromRelease,
                'to_release' => $toRelease,
                'from_stack' => $fromStack,
                'to_stack' => $toStack,
                'selections' => $selections,
            ]);

            return [
                'log' => $log->fresh(),
                'success' => true,
                'message' => 'Upgrade voltooid: '.$fromRelease.' → '.$toRelease,
            ];
        } catch (\Throwable $e) {
            $log->update([
                'status' => SystemUpgradeLog::STATUS_FAILED,
                'steps_log' => $steps,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->step($emit, $steps, 'Upgrade mislukt: '.$e->getMessage(), 'failed');
            $this->emit($emit, 'summary', [
                'from_release' => $fromRelease,
                'to_release' => null,
                'error' => $e->getMessage(),
            ]);

            return [
                'log' => $log->fresh(),
                'success' => false,
                'message' => 'Upgrade mislukt: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @param  callable(callable|null, array): void  $operation
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function executeLoggedUpgrade(
        User $user,
        callable $operation,
        ?callable $emit = null,
        bool $runMigrations = false,
        bool $runTests = true,
    ): array {
        if (! $this->webUpgradeEnabled()) {
            throw new \RuntimeException('Web-upgrades zijn uitgeschakeld. Zet NEXA_WEB_UPGRADE_ENABLED=true in .env.');
        }

        $fromRelease = $this->snapshots->currentReleaseVersion();
        $fromStack = $this->snapshots->capture();
        $toRelease = $this->snapshots->bumpReleasePatch($fromRelease);

        $log = SystemUpgradeLog::query()->create([
            'from_release' => $fromRelease,
            'to_release' => $toRelease,
            'status' => SystemUpgradeLog::STATUS_RUNNING,
            'from_stack' => $fromStack,
            'triggered_by_user_id' => $user->id,
            'started_at' => now(),
        ]);

        $steps = [];

        try {
            $operation($emit, $steps);

            if ($runMigrations) {
                $this->runDatabaseMigrations($emit, $steps);
            }

            if ($runTests) {
                $this->runStabilityTests($emit, $steps);
            }

            $toStack = $this->snapshots->capture();
            GeneralSetting::set('nexa_release_version', $toRelease);

            $log->update([
                'status' => SystemUpgradeLog::STATUS_SUCCESS,
                'to_release' => $toRelease,
                'to_stack' => $toStack,
                'steps_log' => $steps,
                'completed_at' => now(),
            ]);

            $this->step($emit, $steps, 'Upgrade voltooid: '.$fromRelease.' → '.$toRelease, 'done');
            $this->emit($emit, 'summary', [
                'from_release' => $fromRelease,
                'to_release' => $toRelease,
                'from_stack' => $fromStack,
                'to_stack' => $toStack,
            ]);

            return [
                'log' => $log->fresh(),
                'success' => true,
                'message' => 'Upgrade voltooid: '.$fromRelease.' → '.$toRelease,
            ];
        } catch (\Throwable $e) {
            $log->update([
                'status' => SystemUpgradeLog::STATUS_FAILED,
                'steps_log' => $steps,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->step($emit, $steps, 'Upgrade mislukt: '.$e->getMessage(), 'failed');
            $this->emit($emit, 'summary', [
                'from_release' => $fromRelease,
                'to_release' => null,
                'error' => $e->getMessage(),
            ]);

            return [
                'log' => $log->fresh(),
                'success' => false,
                'message' => 'Upgrade mislukt: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function runStabilityTests(?callable $emit, array &$steps): void
    {
        $phpunit = $this->phpunitBinary();
        if ($phpunit === null) {
            $this->step($emit, $steps, 'Stabiliteitstests overgeslagen: PHPUnit is niet geïnstalleerd in deze image', 'skipped');

            return;
        }

        $this->runProcessCommandWithUnknownOptionRetry(
            $emit,
            $steps,
            'Stabiliteitstests uitvoeren',
            [PHP_BINARY, $phpunit, '--colors=never', '--no-progress'],
            1200,
            base_path(),
            $this->phpunitProcessEnvironment(),
        );
    }

    public function unknownCliOptionFromOutput(string $output): ?string
    {
        $patterns = [
            '/Unknown option\s+[\'"](--[A-Za-z0-9][A-Za-z0-9\-]*)[\'"]/i',
            '/The [\'"](--[A-Za-z0-9][A-Za-z0-9\-]*)[\'"] option does not exist/i',
            '/The option [\'"](--[A-Za-z0-9][A-Za-z0-9\-]*)[\'"] does not exist/i',
            '/unrecognized option\s+[\'"](--[A-Za-z0-9][A-Za-z0-9\-]*)[\'"]/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $command
     * @return list<string>
     */
    public function commandWithoutOption(array $command, string $option): array
    {
        $filtered = [];
        foreach ($command as $argument) {
            if ($argument === $option || str_starts_with((string) $argument, $option.'=')) {
                continue;
            }
            $filtered[] = $argument;
        }

        return array_values($filtered);
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function runDatabaseMigrations(?callable $emit, array &$steps): void
    {
        $this->runArtisanStep($emit, $steps, 'Database migraties uitvoeren', ['migrate', '--force'], 300);
    }

    /**
     * Composer draait tijdens web-upgrades zonder scripts, zodat Artisan niet
     * start terwijl vendor nog wordt herschreven. Daarna volgt deze afronding.
     *
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function refreshApplicationAfterComposer(?callable $emit, array &$steps): void
    {
        $this->runArtisanStep($emit, $steps, 'Laravel packages ontdekken', ['package:discover', '--ansi'], 120);
        $this->runArtisanStep($emit, $steps, 'Config-cache legen', ['config:clear'], 60);
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function emitProgress(?callable $emit, array &$steps, string $label, string $status): void
    {
        $this->step($emit, $steps, $label, $status);
    }

    /**
     * @param  list<string>  $command
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @param  array<string, string>|null  $env
     */
    public function runProcessCommand(
        ?callable $emit,
        array &$steps,
        string $label,
        array $command,
        int $timeout,
        ?string $cwd = null,
        ?array $env = null,
    ): void {
        $this->step($emit, $steps, $label, 'running');

        $process = new Process($command, $cwd ?? base_path(), $env, null, $timeout);
        $output = '';

        $process->run(function (string $type, string $buffer) use ($emit, $label, &$output): void {
            $output .= $buffer;
            $line = trim($buffer);
            if ($line !== '') {
                $this->emit($emit, 'note', ['note' => $label.': '.$line]);
            }
        });

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($label.' mislukt: '.$this->processFailureOutput($output, $process)));
        }

        $this->markLastStepDone($steps, $output);
        $this->step($emit, $steps, $label.' voltooid', 'done');
    }

    /**
     * @param  list<string>  $command
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @param  array<string, string>|null  $env
     */
    public function runProcessCommandWithUnknownOptionRetry(
        ?callable $emit,
        array &$steps,
        string $label,
        array $command,
        int $timeout,
        ?string $cwd = null,
        ?array $env = null,
    ): void {
        $this->step($emit, $steps, $label, 'running');

        $attempt = array_values($command);
        $lastOutput = '';

        for ($i = 0; $i < 8; $i++) {
            $output = '';
            $process = new Process($attempt, $cwd ?? base_path(), $env, null, $timeout);
            $process->run(function (string $type, string $buffer) use ($emit, $label, &$output): void {
                $output .= $buffer;
                $line = trim($buffer);
                if ($line !== '') {
                    $this->emit($emit, 'note', ['note' => $label.': '.$line]);
                }
            });

            $lastOutput = $this->combinedProcessOutput($output, $process);

            if ($process->isSuccessful()) {
                $this->markLastStepDone($steps, $output);
                $this->step($emit, $steps, $label.' voltooid', 'done');

                return;
            }

            $unknown = $this->unknownCliOptionFromOutput($lastOutput);
            if ($unknown === null) {
                break;
            }

            $stripped = $this->commandWithoutOption($attempt, $unknown);
            if ($stripped === $attempt) {
                break;
            }

            $this->emit($emit, 'note', [
                'note' => $label.': optie '.$unknown.' wordt niet ondersteund, opnieuw zonder die vlag.',
            ]);
            $attempt = $stripped;
        }

        throw new \RuntimeException(trim($label.' mislukt: '.$this->truncateProcessOutput(
            $this->compactPhpunitFailureOutput($this->compactComposerFailureOutput($lastOutput))
        )));
    }

    /**
     * @return array{log: SystemUpgradeLog, from_release: string, to_release: string, from_stack: array<string, string>, steps: list<array{label: string, status: string, output?: string}>}
     */
    public function startUpgradeLog(User $user): array
    {
        if (! $this->webUpgradeEnabled()) {
            throw new \RuntimeException('Web-upgrades zijn uitgeschakeld. Zet NEXA_WEB_UPGRADE_ENABLED=true in .env.');
        }

        $fromRelease = $this->snapshots->currentReleaseVersion();
        $fromStack = $this->snapshots->capture();
        $toRelease = $this->snapshots->bumpReleasePatch($fromRelease);

        $log = SystemUpgradeLog::query()->create([
            'from_release' => $fromRelease,
            'to_release' => $toRelease,
            'status' => SystemUpgradeLog::STATUS_RUNNING,
            'from_stack' => $fromStack,
            'triggered_by_user_id' => $user->id,
            'started_at' => now(),
        ]);

        return [
            'log' => $log,
            'from_release' => $fromRelease,
            'to_release' => $toRelease,
            'from_stack' => $fromStack,
            'steps' => [],
        ];
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @param  array<string, string>  $fromStack
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function finishUpgradeLogSuccess(
        SystemUpgradeLog $log,
        array &$steps,
        ?callable $emit,
        string $fromRelease,
        string $toRelease,
        array $fromStack,
    ): array {
        $toStack = $this->snapshots->capture();
        GeneralSetting::set('nexa_release_version', $toRelease);

        $log->update([
            'status' => SystemUpgradeLog::STATUS_SUCCESS,
            'to_release' => $toRelease,
            'to_stack' => $toStack,
            'steps_log' => $steps,
            'completed_at' => now(),
        ]);

        $this->step($emit, $steps, 'Upgrade voltooid: '.$fromRelease.' → '.$toRelease, 'done');
        $this->emit($emit, 'summary', [
            'from_release' => $fromRelease,
            'to_release' => $toRelease,
            'from_stack' => $fromStack,
            'to_stack' => $toStack,
        ]);

        return [
            'log' => $log->fresh(),
            'success' => true,
            'message' => 'Upgrade voltooid: '.$fromRelease.' → '.$toRelease,
        ];
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function finishUpgradeLogFailure(
        SystemUpgradeLog $log,
        array &$steps,
        ?callable $emit,
        string $fromRelease,
        \Throwable $e,
    ): array {
        $log->update([
            'status' => SystemUpgradeLog::STATUS_FAILED,
            'steps_log' => $steps,
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ]);

        $this->step($emit, $steps, 'Upgrade mislukt: '.$e->getMessage(), 'failed');
        $this->emit($emit, 'summary', [
            'from_release' => $fromRelease,
            'to_release' => null,
            'error' => $e->getMessage(),
        ]);

        return [
            'log' => $log->fresh(),
            'success' => false,
            'message' => 'Upgrade mislukt: '.$e->getMessage(),
        ];
    }

    /**
     * @param  list<string>  $selections
     * @return list<string>
     */
    private function composerPackagesFromSelections(array $selections): array
    {
        $packages = [];
        foreach ($selections as $id) {
            if (str_starts_with($id, 'pkg:composer:')) {
                $packages[] = substr($id, strlen('pkg:composer:'));
            }
        }

        return $packages;
    }

    /**
     * @param  list<string>  $selections
     * @return list<string>
     */
    private function npmPackagesFromSelections(array $selections): array
    {
        $packages = [];
        foreach ($selections as $id) {
            if (str_starts_with($id, 'pkg:npm:')) {
                $packages[] = substr($id, strlen('pkg:npm:'));
            }
        }

        return $packages;
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function runShellStep(?callable $emit, array &$steps, string $label, string $command, int $timeout): void
    {
        $this->step($emit, $steps, $label, 'running');

        if (! $this->commandExists(strtok($command, ' ') ?: '')) {
            throw new \RuntimeException($label.' mislukt: command niet gevonden.');
        }

        $process = Process::fromShellCommandline($command, base_path(), null, null, $timeout);
        $output = '';

        $process->run(function (string $type, string $buffer) use ($emit, $label, &$output): void {
            $output .= $buffer;
            $line = trim($buffer);
            if ($line !== '') {
                $this->emit($emit, 'note', ['note' => $label.': '.$line]);
            }
        });

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($label.' mislukt: '.$this->processFailureOutput($output, $process)));
        }

        $this->markLastStepDone($steps, $output);
        $this->step($emit, $steps, $label.' voltooid', 'done');
    }

    /**
     * @param  list<string>  $artisanArgs
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @param  array<string, string>|null  $env
     */
    private function runArtisanStep(?callable $emit, array &$steps, string $label, array $artisanArgs, int $timeout, ?array $env = null): void
    {
        $this->step($emit, $steps, $label, 'running');

        $command = array_merge([PHP_BINARY, base_path('artisan')], $artisanArgs);
        $process = new Process($command, base_path(), $env, null, $timeout);
        $output = '';

        $process->run(function (string $type, string $buffer) use ($emit, &$output): void {
            $output .= $buffer;
            $line = trim($buffer);
            if ($line !== '') {
                $this->emit($emit, 'note', ['note' => $line]);
            }
        });

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($label.' mislukt: '.$this->processFailureOutput($output, $process)));
        }

        $this->markLastStepDone($steps, $output);
        $this->step($emit, $steps, $label.' geslaagd', 'done');
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function step(?callable $emit, array &$steps, string $label, string $status): void
    {
        $steps[] = [
            'label' => $label,
            'status' => $status,
            'at' => Carbon::now()->toIso8601String(),
        ];

        $this->emit($emit, 'step', [
            'label' => $label,
            'status' => $status,
        ]);
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function markLastStepDone(array &$steps, string $output): void
    {
        if ($steps === []) {
            return;
        }

        $index = count($steps) - 1;
        $steps[$index]['status'] = 'done';
        if (trim($output) !== '') {
            $steps[$index]['output'] = mb_substr(trim($output), 0, 4000);
        }
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

    private function commandExists(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary), base_path(), null, null, 10);
        $process->run();

        return $process->isSuccessful();
    }

    private function phpunitBinary(): ?string
    {
        foreach ([base_path('vendor/bin/phpunit'), base_path('vendor/phpunit/phpunit/phpunit')] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Tests vanuit de web-upgrade erven anders DB_CONNECTION=pgsql uit de draaiende app.
     *
     * @return array<string, string>
     */
    private function phpunitProcessEnvironment(): array
    {
        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'MAIL_MAILER' => 'array',
            'PULSE_ENABLED' => 'false',
            'TELESCOPE_ENABLED' => 'false',
            'NIGHTWATCH_ENABLED' => 'false',
            'TERM' => 'dumb',
            'NO_COLOR' => '1',
        ];
    }

    private function processFailureOutput(string $capturedOutput, Process $process): string
    {
        return $this->truncateProcessOutput(
            $this->compactPhpunitFailureOutput(
                $this->compactComposerFailureOutput($this->combinedProcessOutput($capturedOutput, $process))
            )
        );
    }

    private function combinedProcessOutput(string $capturedOutput, Process $process): string
    {
        $combined = trim($process->getErrorOutput()."\n".$process->getOutput());
        $captured = trim($capturedOutput);

        if ($combined === '') {
            return $captured;
        }
        if ($captured !== '' && ! str_contains($combined, $captured)) {
            return trim($captured."\n".$combined);
        }

        return $combined;
    }

    public function compactComposerFailureOutput(string $output): string
    {
        $hint = '';
        if (preg_match('/^Script .+ returned with error code \d+.*/m', $output, $matches) === 1) {
            $hint = trim($matches[0])."\n";
        }

        $lines = preg_split('/\R/', $output) ?: [];
        $kept = [];
        $skipped = 0;
        foreach ($lines as $line) {
            if (preg_match('/^\s*-\s*Conclusion: don\'t install /', $line) === 1) {
                $skipped++;
                continue;
            }
            $kept[] = $line;
        }
        if ($skipped > 0) {
            $kept[] = '('.$skipped.' verdere Composer-versieconflicten weggelaten)';
        }

        return trim($hint.implode("\n", $kept));
    }

    public function compactPhpunitFailureOutput(string $output): string
    {
        if (
            ! str_contains($output, 'PHPUnit')
            && ! preg_match('/There were \d+ (?:failures|errors)/', $output)
            && ! str_contains($output, 'FAILURES!')
        ) {
            return $output;
        }

        $lines = preg_split('/\R/', $output) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            if (preg_match('/^[.\-FESWDN]+\s+\d+\s+\/\s+\d+/', $line) === 1) {
                continue;
            }
            if (preg_match('/^[.\-FESWDN]{10,}$/', $line) === 1) {
                continue;
            }
            $kept[] = $line;
        }
        $output = implode("\n", $kept);

        $output = preg_replace_callback(
            '/(Failed asserting that[\s\S]{0,240})([\s\S]*?)(?=\n\d+\) |\n(?:FAILURES!|ERRORS!|WARNINGS!)|$)/',
            function (array $matches): string {
                $rest = $matches[2];
                if (strlen($rest) <= 400) {
                    return $matches[0];
                }

                return $matches[1]."\n… [assertiedump ingekort, ".strlen($rest)." tekens] …\n";
            },
            $output
        ) ?? $output;

        if (preg_match_all('/^\d+\) .+$/m', $output, $names) === false || $names[0] === []) {
            return trim($output);
        }

        $hint = "Mislukte tests:\n".implode("\n", $names[0])."\n\n";
        if (! str_starts_with(ltrim($output), 'Mislukte tests:')) {
            $output = $hint.$output;
        }

        return trim($output);
    }

    private function truncateProcessOutput(string $output, int $max = 6000): string
    {
        $output = trim($output);
        if ($output === '' || mb_strlen($output) <= $max) {
            return $output;
        }

        $head = 1800;

        return mb_substr($output, 0, $head)."\n…\n".mb_substr($output, -($max - $head));
    }
}
