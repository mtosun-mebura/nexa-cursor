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
        return [
            'nexa_release' => $this->currentReleaseVersion(),
            'app_env' => (string) config('app.env', '—'),
            'app_url' => (string) (config('app.url') ?: '—'),
            'hostname' => $this->detectHostname(),
            'server_ip' => $this->detectServerIp(),
            'public_ip' => $this->detectPublicIp(),
            'app_url_dns' => $this->detectAppUrlDnsIp(),
            'os' => $this->detectOs(),
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
            'app_env' => 'Omgeving',
            'app_url' => 'APP_URL',
            'hostname' => 'Hostnaam',
            'server_ip' => 'Server-IP',
            'public_ip' => 'Publiek IP',
            'app_url_dns' => 'APP_URL → IP',
            'os' => 'OS',
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

    private function detectHostname(): string
    {
        $hostname = gethostname();

        return is_string($hostname) && trim($hostname) !== '' ? trim($hostname) : '—';
    }

    private function detectServerIp(): string
    {
        $candidates = [];

        $serverAddr = request()->server('SERVER_ADDR');
        if (is_string($serverAddr) && $this->isUsableIp($serverAddr)) {
            $candidates[] = $serverAddr;
        }

        $hostname = $this->detectHostname();
        if ($hostname !== '—') {
            $resolved = gethostbyname($hostname);
            if (is_string($resolved) && $this->isUsableIp($resolved)) {
                $candidates[] = $resolved;
            }
        }

        $fromHostnameI = $this->shellVersion('hostname -I');
        if ($fromHostnameI !== '—') {
            foreach (preg_split('/\s+/', trim($fromHostnameI)) ?: [] as $ip) {
                if ($this->isUsableIp($ip)) {
                    $candidates[] = $ip;
                }
            }
        }

        $candidates = array_values(array_unique($candidates));

        return $candidates[0] ?? '—';
    }

    private function detectPublicIp(): string
    {
        if (app()->environment('testing')) {
            return '—';
        }

        try {
            return \Illuminate\Support\Facades\Cache::remember('system_stack.public_ip', 3600, function () {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 2,
                        'ignore_errors' => true,
                    ],
                    'ssl' => [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                    ],
                ]);

                foreach (['https://api.ipify.org', 'https://ifconfig.me/ip'] as $endpoint) {
                    $raw = @file_get_contents($endpoint, false, $context);
                    if (! is_string($raw)) {
                        continue;
                    }
                    $ip = trim($raw);
                    if ($this->isUsableIp($ip, allowPrivate: false)) {
                        return $ip;
                    }
                }

                return '—';
            });
        } catch (\Throwable) {
            return '—';
        }
    }

    private function detectAppUrlDnsIp(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($host) || trim($host) === '') {
            return '—';
        }

        $host = trim($host);
        $records = @dns_get_record($host, DNS_A);
        if (is_array($records) && $records !== []) {
            $ips = [];
            foreach ($records as $record) {
                $ip = $record['ip'] ?? null;
                if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[] = $ip;
                }
            }
            $ips = array_values(array_unique($ips));
            if ($ips !== []) {
                return implode(', ', $ips);
            }
        }

        $resolved = gethostbyname($host);
        if (is_string($resolved) && $resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP)) {
            return $resolved;
        }

        return '—';
    }

    private function detectOs(): string
    {
        $os = trim(php_uname('s').' '.php_uname('r'));

        return $os !== '' ? $os : '—';
    }

    private function isUsableIp(string $ip, bool $allowPrivate = true): bool
    {
        $ip = trim($ip);
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }

        if (! $allowPrivate && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
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
