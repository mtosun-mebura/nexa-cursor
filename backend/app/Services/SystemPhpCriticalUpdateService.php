<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SystemPhpCriticalUpdateService
{
    public const TYPE = 'php_critical_update';

    public function __construct(
        protected IncidentNotifier $incidents,
    ) {}

    /**
     * Live check alleen buiten tests, tenzij een test hem expliciet aanzet.
     * Zo raken andere upgradetests het netwerk niet.
     */
    public function enabled(): bool
    {
        if (! app()->runningUnitTests()) {
            return true;
        }

        return (bool) config('nexa.php_critical_update_check_in_tests');
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>
     */
    public function attachAndNotify(array $status): array
    {
        $updates = $this->updatesFromStatus($status);
        $this->sync($updates);
        $status['critical_updates'] = $updates;

        if ($updates !== [] && ! str_contains((string) ($status['message'] ?? ''), 'kritieke PHP-update')) {
            $status['message'] = trim((string) ($status['message'] ?? '').' Er is een kritieke PHP-update.');
        }

        return $status;
    }

    /**
     * @param  array<string, mixed>  $status
     * @return list<array{key: string, title: string, message: string, severity: string, priority: string}>
     */
    public function updatesFromStatus(array $status): array
    {
        $updates = [];
        $version = $status['critical_runtime_version'] ?? null;
        if (is_string($version) && $version !== '') {
            $current = $this->normalizedVersion(PHP_VERSION);
            $updates[] = [
                'key' => 'php-runtime:'.$version,
                'title' => 'Kritieke PHP-update',
                'message' => 'Er is een kritieke PHP-update. PHP '.$version.' is een security-release en is nieuwer dan de draaiende versie '.($current !== '' ? $current : PHP_VERSION).'.',
                'severity' => 'security',
                'priority' => 'urgent',
            ];
        }

        return array_merge($updates, $this->dependencyUpdates());
    }

    /**
     * @param  list<array{key: string, title: string, message: string, severity?: string, priority?: string}>  $updates
     */
    public function sync(array $updates): void
    {
        $updatesByKey = [];
        foreach ($updates as $update) {
            $key = (string) ($update['key'] ?? '');
            if ($key === '' || ($update['title'] ?? '') === '' || ($update['message'] ?? '') === '') {
                continue;
            }
            $updatesByKey[$key] = $update;
        }

        foreach ($this->incidents->superAdmins() as $admin) {
            $open = Notification::query()
                ->where('user_id', $admin->id)
                ->where('type', self::TYPE)
                ->whereNull('archived_at')
                ->get();

            $seen = [];
            foreach ($open as $notification) {
                $key = $this->keyFromNotification($notification);
                if ($key === '' || ! isset($updatesByKey[$key])) {
                    $notification->forceFill([
                        'archived_at' => now(),
                        'read_at' => $notification->read_at ?? now(),
                    ])->save();

                    continue;
                }
                $seen[$key] = true;
            }

            foreach ($updatesByKey as $key => $update) {
                if (isset($seen[$key])) {
                    continue;
                }

                $existing = $this->findForKey((int) $admin->id, $key);
                $payload = [
                    'title' => $update['title'],
                    'message' => $update['message'],
                    'priority' => $update['priority'] ?? 'urgent',
                    'action_url' => route('admin.settings.upgrade.index'),
                    'archived_at' => null,
                    'read_at' => null,
                    'data' => json_encode([
                        'update_key' => $key,
                        'severity' => $update['severity'] ?? 'critical',
                        'event' => self::TYPE,
                    ]),
                ];

                if ($existing instanceof Notification) {
                    $existing->forceFill($payload)->save();

                    continue;
                }

                Notification::query()->create(array_merge($payload, [
                    'user_id' => $admin->id,
                    'company_id' => null,
                    'type' => self::TYPE,
                    'category' => 'system',
                ]));
            }
        }
    }

    /**
     * Security-advisories met severity critical of high voor de directe Composer-packages.
     * Elke aanroep gaat live naar Packagist, zonder Laravel-cache.
     *
     * @return list<array{key: string, title: string, message: string, severity: string, priority: string}>
     */
    public function dependencyUpdates(): array
    {
        try {
            return $this->collectDependencyUpdates();
        } catch (\Throwable $e) {
            Log::warning('Kritieke PHP-dependencycheck mislukt', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array{key: string, title: string, message: string, severity: string, priority: string}>
     */
    private function collectDependencyUpdates(): array
    {
        $installed = $this->directInstalledPackages();
        if ($installed === []) {
            return [];
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ])
            ->acceptJson()
            ->get('https://packagist.org/api/security-advisories/', [
                'packages' => array_keys($installed),
            ]);

        if (! $response->successful() || ! is_array($response->json())) {
            return [];
        }

        $advisories = $response->json('advisories');
        if (! is_array($advisories)) {
            return [];
        }

        $updates = [];
        foreach ($advisories as $package => $rows) {
            $name = str_replace('\\/', '/', (string) $package);
            $version = $installed[$name] ?? null;
            if (! is_string($version) || ! is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $severity = strtolower(trim((string) ($row['severity'] ?? '')));
                if (! in_array($severity, ['critical', 'high'], true)) {
                    continue;
                }
                $affected = (string) ($row['affectedVersions'] ?? '');
                if (! $this->versionIsAffected($version, $affected)) {
                    continue;
                }

                $advisoryId = trim((string) ($row['advisoryId'] ?? $row['cve'] ?? ''));
                if ($advisoryId === '') {
                    $advisoryId = substr(sha1($name.'|'.(string) ($row['title'] ?? '')), 0, 12);
                }

                $titleText = trim((string) ($row['title'] ?? 'Beveiligingsadvies'));
                $key = $this->updateKey('composer:'.str_replace('/', '|', $name).':'.$advisoryId);
                $updates[] = [
                    'key' => $key,
                    'title' => 'Kritieke PHP-update',
                    'message' => 'Er is een kritieke PHP-update voor '.$name.' ('.$severity.'): '.$titleText.'.',
                    'severity' => $severity,
                    'priority' => $severity === 'high' ? 'high' : 'urgent',
                ];
            }
        }

        return $updates;
    }

    /**
     * @return array<string, string>
     */
    private function directInstalledPackages(): array
    {
        $jsonPath = base_path('composer.json');
        $lockPath = base_path('composer.lock');
        if (! is_file($jsonPath) || ! is_file($lockPath)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($jsonPath), true);
        $lock = json_decode((string) file_get_contents($lockPath), true);
        if (! is_array($json) || ! is_array($lock)) {
            return [];
        }

        $names = [];
        foreach (['require', 'require-dev'] as $section) {
            foreach (array_keys(is_array($json[$section] ?? null) ? $json[$section] : []) as $name) {
                if (is_string($name) && str_contains($name, '/')) {
                    $names[$name] = true;
                }
            }
        }

        $installed = [];
        foreach (['packages', 'packages-dev'] as $section) {
            foreach (is_array($lock[$section] ?? null) ? $lock[$section] : [] as $package) {
                if (! is_array($package)) {
                    continue;
                }
                $name = (string) ($package['name'] ?? '');
                if ($name === '' || ! isset($names[$name]) || isset($installed[$name])) {
                    continue;
                }
                $version = $this->normalizedVersion((string) ($package['version'] ?? ''));
                if ($version !== '') {
                    $installed[$name] = $version;
                }
            }
        }

        return $installed;
    }

    private function versionIsAffected(string $version, string $affected): bool
    {
        $version = $this->normalizedVersion($version);
        $affected = trim($affected);
        if ($version === '' || $affected === '') {
            return false;
        }

        $orParts = preg_split('/\s*\|\|\s*|\s*\|\s*/', $affected) ?: [];
        foreach ($orParts as $and) {
            $pieces = array_values(array_filter(array_map('trim', explode(',', (string) $and)), fn (string $piece): bool => $piece !== ''));
            if ($pieces === []) {
                continue;
            }

            $matches = true;
            foreach ($pieces as $piece) {
                if (! $this->constraintMatches($version, $piece)) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    private function constraintMatches(string $version, string $constraint): bool
    {
        if (preg_match('/^\^v?(\d+)\.(\d+)(?:\.(\d+))?/', $constraint, $matches) === 1) {
            $lower = $matches[1].'.'.$matches[2].'.'.($matches[3] ?? '0');
            $upper = ((int) $matches[1] + 1).'.0.0';

            return version_compare($version, $lower, '>=') && version_compare($version, $upper, '<');
        }

        if (preg_match('/^~v?(\d+)\.(\d+)(?:\.(\d+))?/', $constraint, $matches) === 1) {
            $patch = $matches[3] ?? '0';
            $lower = $matches[1].'.'.$matches[2].'.'.$patch;
            $upper = $matches[1].'.'.((int) $matches[2] + 1).'.0';

            return version_compare($version, $lower, '>=') && version_compare($version, $upper, '<');
        }

        if (preg_match('/^(<=|>=|!=|==|<|>|=)\s*v?(\d+\.\d+\.\d+)/', $constraint, $matches) !== 1) {
            return false;
        }

        $operator = $matches[1] === '=' ? '==' : $matches[1];

        return version_compare($version, $matches[2], $operator);
    }

    private function normalizedVersion(string $version): string
    {
        $version = ltrim(trim($version), 'v');
        if (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function updateKey(string $raw): string
    {
        $key = preg_replace('/[^A-Za-z0-9:._|-]+/', '-', $raw) ?? $raw;

        return substr($key, 0, 160);
    }

    private function keyFromNotification(Notification $notification): string
    {
        $data = json_decode((string) $notification->data, true);

        return is_array($data) ? (string) ($data['update_key'] ?? '') : '';
    }

    private function findForKey(int $userId, string $key): ?Notification
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('type', self::TYPE)
            ->where('data', 'like', '%"update_key":"'.$key.'"%')
            ->orderByDesc('id')
            ->first();
    }
}
