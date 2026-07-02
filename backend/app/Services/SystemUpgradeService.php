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
     * @param  callable(array<string, mixed>): void|null  $emit
     * @return array{log: SystemUpgradeLog, success: bool, message: string}
     */
    public function runUpgrade(User $user, ?callable $emit = null): array
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

        $steps = [];

        try {
            $this->step($emit, $steps, 'Huidige stack vastgelegd', 'done');
            $this->step($emit, $steps, 'Upgrade naar release '.$toRelease.' gestart', 'running');

            $this->runShellStep($emit, $steps, 'Composer dependencies bijwerken', 'composer update --no-interaction --no-ansi --prefer-dist', 900);

            if ($this->commandExists('npm')) {
                $this->runShellStep($emit, $steps, 'NPM dependencies bijwerken', 'npm update --no-fund --no-audit', 600);
                $this->runShellStep($emit, $steps, 'Frontend assets bouwen', 'npm run build', 600);
            } else {
                $this->step($emit, $steps, 'NPM niet beschikbaar — frontend build overgeslagen', 'skipped');
            }

            $this->runArtisanStep($emit, $steps, 'Database migraties uitvoeren', ['migrate', '--force'], 300);
            $this->runArtisanStep($emit, $steps, 'Unit tests uitvoeren', ['test', '--colors=never'], 1200);

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
            throw new \RuntimeException(trim($label.' mislukt: '.$process->getErrorOutput()."\n".$process->getOutput()));
        }

        $this->markLastStepDone($steps, $output);
        $this->step($emit, $steps, $label.' voltooid', 'done');
    }

    /**
     * @param  list<string>  $artisanArgs
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function runArtisanStep(?callable $emit, array &$steps, string $label, array $artisanArgs, int $timeout): void
    {
        $this->step($emit, $steps, $label, 'running');

        $command = array_merge([PHP_BINARY, base_path('artisan')], $artisanArgs);
        $process = new Process($command, base_path(), null, null, $timeout);
        $output = '';

        $process->run(function (string $type, string $buffer) use ($emit, &$output): void {
            $output .= $buffer;
            $line = trim($buffer);
            if ($line !== '') {
                $this->emit($emit, 'note', ['note' => $line]);
            }
        });

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($label.' mislukt: '.$process->getErrorOutput()."\n".$process->getOutput()));
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
}
