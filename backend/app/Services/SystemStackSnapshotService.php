<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class SystemStackSnapshotService
{
    public function currentReleaseVersion(): string
    {
        $stored = GeneralSetting::get('nexa_release_version');

        if (is_string($stored) && trim($stored) !== '') {
            return trim($stored);
        }

        return (string) config('nexa.release_version', '1.0.0');
    }

    /**
     * @return array<string, string>
     */
    public function capture(): array
    {
        $basePath = base_path();

        return [
            'nexa_release' => $this->currentReleaseVersion(),
            'php' => PHP_VERSION,
            'laravel' => Application::VERSION,
            'livewire' => $this->composerPackageVersion('livewire/livewire') ?? '—',
            'phpunit' => $this->composerPackageVersion('phpunit/phpunit') ?? '—',
            'vue' => $this->npmPackageVersion('vue') ?? '—',
            'vite' => $this->npmPackageVersion('vite') ?? '—',
            'tailwindcss' => $this->npmPackageVersion('tailwindcss') ?? '—',
            'node' => $this->shellVersion('node -v'),
            'npm' => $this->shellVersion('npm -v'),
            'composer' => $this->shellVersion('composer --version'),
            'docker' => $this->shellVersion('docker --version'),
            'docker_compose' => $this->shellVersion('docker compose version'),
            'postgresql' => $this->detectPostgresVersion(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    public function labeledStack(?array $stack = null): array
    {
        $stack = $stack ?? $this->capture();

        $labels = [
            'nexa_release' => 'Nexa release',
            'php' => 'PHP',
            'laravel' => 'Laravel',
            'livewire' => 'Livewire',
            'phpunit' => 'PHPUnit',
            'vue' => 'Vue.js',
            'vite' => 'Vite',
            'tailwindcss' => 'Tailwind CSS',
            'node' => 'Node.js',
            'npm' => 'NPM',
            'composer' => 'Composer',
            'docker' => 'Docker',
            'docker_compose' => 'Docker Compose',
            'postgresql' => 'PostgreSQL',
        ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'value' => (string) ($stack[$key] ?? '—'),
            ];
        }

        return $rows;
    }

    public function bumpReleasePatch(string $version): string
    {
        $parts = array_map('intval', explode('.', preg_replace('/[^0-9.]/', '', $version) ?: '1.0.0'));
        while (count($parts) < 3) {
            $parts[] = 0;
        }

        $parts[2]++;

        return implode('.', array_slice($parts, 0, 3));
    }

    private function composerPackageVersion(string $package): ?string
    {
        $lockPath = base_path('composer.lock');
        if (! File::isFile($lockPath)) {
            return null;
        }

        $data = json_decode(File::get($lockPath), true);
        if (! is_array($data['packages'] ?? null)) {
            return null;
        }

        foreach ($data['packages'] as $entry) {
            if (($entry['name'] ?? '') === $package) {
                return ltrim((string) ($entry['version'] ?? ''), 'v');
            }
        }

        foreach ($data['packages-dev'] ?? [] as $entry) {
            if (($entry['name'] ?? '') === $package) {
                return ltrim((string) ($entry['version'] ?? ''), 'v');
            }
        }

        return null;
    }

    private function npmPackageVersion(string $package): ?string
    {
        $lockPath = base_path('package-lock.json');
        if (! File::isFile($lockPath)) {
            return null;
        }

        $data = json_decode(File::get($lockPath), true);
        $packages = $data['packages'] ?? [];
        $key = 'node_modules/'.$package;

        if (isset($packages[$key]['version'])) {
            return (string) $packages[$key]['version'];
        }

        return null;
    }

    private function shellVersion(string $command): string
    {
        if (! $this->commandExists(strtok($command, ' ') ?: '')) {
            return '—';
        }

        try {
            $process = Process::fromShellCommandline($command, base_path(), null, null, 15);
            $process->run();

            if (! $process->isSuccessful()) {
                return '—';
            }

            $output = trim($process->getOutput());
            if ($output === '') {
                $output = trim($process->getErrorOutput());
            }

            return $output !== '' ? $output : '—';
        } catch (\Throwable) {
            return '—';
        }
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

    private function detectPostgresVersion(): string
    {
        $fromEnv = env('DB_CONNECTION') === 'pgsql'
            ? $this->shellVersion('psql --version')
            : '—';

        if ($fromEnv !== '—') {
            return $fromEnv;
        }

        return $this->shellVersion('docker exec nexa_db psql --version');
    }
}
