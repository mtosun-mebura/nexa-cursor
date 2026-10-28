<?php

namespace App\Services;

use App\Models\NexaSuiteMarketplaceSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Publieke hosts voor betaal-redirects en webhooks van de native app.
 * De lijst is de bron. Een lege lijst toont Productie en Test, met Productie actief.
 */
class NexaSuitePublicHost
{
    public const DEFAULT_URL = 'https://nexasuite.nl';

    /**
     * @return list<array{id: string, name: string, host: string, payments: bool}>
     */
    public static function defaultEntries(): array
    {
        $productie = self::normalize((string) config('nexa_suite_marketplace.public_environments.productie', self::DEFAULT_URL))
            ?? self::DEFAULT_URL;
        $test = self::normalize((string) config('nexa_suite_marketplace.public_environments.test', 'https://nexasuite.online'))
            ?? 'https://nexasuite.online';

        return [
            ['id' => 'productie', 'name' => 'Productie', 'host' => $productie, 'payments' => true],
            ['id' => 'test', 'name' => 'Test', 'host' => $test, 'payments' => false],
        ];
    }

    /**
     * Neemt een eerder opgeslagen enkele host mee in de standaardlijst.
     *
     * @return list<array{id: string, name: string, host: string, payments: bool}>
     */
    public static function listFromLegacyHost(?string $legacy): array
    {
        $entries = self::defaultEntries();
        $normalized = self::normalize($legacy);
        if ($normalized === null) {
            return $entries;
        }

        $matched = false;
        foreach ($entries as $index => $entry) {
            $isMatch = $entry['host'] === $normalized;
            $entries[$index]['payments'] = $isMatch;
            $matched = $matched || $isMatch;
        }
        if ($matched) {
            return $entries;
        }

        foreach ($entries as $index => $entry) {
            $entries[$index]['payments'] = false;
        }
        $entries[] = [
            'id' => 'aangepast',
            'name' => 'Aangepast',
            'host' => $normalized,
            'payments' => true,
        ];

        return $entries;
    }

    public function defaultUrl(): string
    {
        return self::normalize((string) config('nexa_suite_marketplace.public_base_url', self::DEFAULT_URL))
            ?? self::DEFAULT_URL;
    }

    /**
     * @return list<array{id: string, name: string, host: string, payments: bool}>
     */
    public function entries(): array
    {
        try {
            if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
                return self::defaultEntries();
            }

            $stored = $this->storedEntries();
            if ($stored !== []) {
                return $stored;
            }

            $legacy = $this->legacyHost();
            if ($legacy !== null) {
                return self::listFromLegacyHost($legacy);
            }

            return self::defaultEntries();
        } catch (Throwable) {
            return self::defaultEntries();
        }
    }

    public function current(): string
    {
        foreach ($this->entries() as $entry) {
            if ($entry['payments']) {
                return $entry['host'];
            }
        }

        return $this->defaultUrl();
    }

    public function add(string $name, string $rawHost): void
    {
        [$name, $host] = $this->validated($name, $rawHost, null);
        $entries = $this->entries();
        $entries[] = [
            'id' => (string) Str::ulid(),
            'name' => $name,
            'host' => $host,
            'payments' => false,
        ];
        $this->persist($this->ensurePayments($entries));
    }

    public function update(string $id, string $name, string $rawHost): void
    {
        [$name, $host] = $this->validated($name, $rawHost, $id);
        $entries = $this->entries();
        $found = false;
        foreach ($entries as $index => $entry) {
            if ($entry['id'] !== $id) {
                continue;
            }
            $entries[$index]['name'] = $name;
            $entries[$index]['host'] = $host;
            $found = true;
            break;
        }
        if (! $found) {
            throw new NotFoundHttpException;
        }
        $this->persist($this->ensurePayments($entries));
    }

    public function delete(string $id): void
    {
        $entries = $this->entries();
        if (count($entries) <= 1) {
            throw ValidationException::withMessages([
                'public_hosts' => __('Er moet minstens één omgeving blijven.'),
            ]);
        }

        $next = [];
        $found = false;
        foreach ($entries as $entry) {
            if ($entry['id'] === $id) {
                $found = true;
                continue;
            }
            $next[] = $entry;
        }
        if (! $found) {
            throw new NotFoundHttpException;
        }
        $this->persist($this->ensurePayments($next));
    }

    public function useForPayments(string $id): void
    {
        $entries = $this->entries();
        $found = false;
        foreach ($entries as $index => $entry) {
            if ($entry['id'] === $id) {
                $entries[$index]['payments'] = true;
                $found = true;
            } else {
                $entries[$index]['payments'] = false;
            }
        }
        if (! $found) {
            throw new NotFoundHttpException;
        }
        $this->persist($entries);
    }

    /**
     * Kopieert een gevulde public_base_url naar de lijst. Een gevulde lijst blijft staan.
     */
    public function backfillFromLegacyColumn(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_hosts')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_base_url')) {
            return;
        }

        $rows = DB::table('nexa_suite_marketplace_settings')->orderBy('id')->get(['id', 'public_base_url', 'public_hosts']);
        foreach ($rows as $row) {
            if ($this->sanitize(self::decodeJson($row->public_hosts ?? null)) !== []) {
                continue;
            }
            $legacy = self::normalize(is_string($row->public_base_url ?? null) ? $row->public_base_url : null);
            if ($legacy === null) {
                continue;
            }
            $list = self::listFromLegacyHost($legacy);
            $active = self::DEFAULT_URL;
            foreach ($list as $entry) {
                if ($entry['payments']) {
                    $active = $entry['host'];
                    break;
                }
            }
            DB::table('nexa_suite_marketplace_settings')->where('id', $row->id)->update([
                'public_hosts' => json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'public_base_url' => $active,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * https-URL, zonder trailing slash. http alleen voor localhost.
     * Een kale domeinnaam krijgt https://. Pad en query vallen weg: dit is een host.
     */
    public static function normalize(?string $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }
        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            $value = 'https://'.$value;
        }
        $value = rtrim($value, '/');
        $parts = parse_url($value);
        if (! is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) $parts['host']);
        if ($scheme === 'http') {
            if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return null;
            }
        } elseif ($scheme !== 'https') {
            return null;
        }
        if (! self::hostIsAcceptable($host)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';
        $hostOut = str_contains($host, ':') ? '['.$host.']' : $host;

        return $scheme.'://'.$hostOut.$port;
    }

    /**
     * Zet het host-deel van een http(s)-URL op de actieve publieke host.
     * Andere schemes (app-deeplinks) blijven staan.
     */
    public function withPublicHost(string $url): string
    {
        $base = $this->current();
        $url = trim($url);
        if ($url === '') {
            return $base;
        }
        if (str_starts_with($url, '/')) {
            return $base.$url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return $base.'/'.ltrim($url, '/');
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return $url;
        }

        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $base.$path.$query.$fragment;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function validated(string $name, string $rawHost, ?string $ignoreId): array
    {
        $errors = [];
        $name = trim($name);
        if ($name === '') {
            $errors['name'] = __('Vul een omgevingsnaam in.');
        } elseif (mb_strlen($name) > 80) {
            $errors['name'] = __('De omgevingsnaam is te lang.');
        } else {
            foreach ($this->entries() as $entry) {
                if ($ignoreId !== null && $entry['id'] === $ignoreId) {
                    continue;
                }
                if (mb_strtolower($entry['name']) === mb_strtolower($name)) {
                    $errors['name'] = __('Deze omgevingsnaam bestaat al.');
                    break;
                }
            }
        }

        $host = self::normalize($rawHost);
        if ($host === null || strlen($host) > 255) {
            $errors['host'] = __('Vul een geldige https-URL in. http is alleen toegestaan voor localhost.');
            $host = '';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [$name, $host];
    }

    /**
     * @param  list<array{id: string, name: string, host: string, payments: bool}>  $entries
     */
    private function persist(array $entries): void
    {
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_hosts')) {
            throw ValidationException::withMessages([
                'host' => __('De database-update voor de publieke hosts is nog niet uitgevoerd.'),
            ]);
        }

        $entries = array_values($this->ensurePayments($entries));
        $active = $this->defaultUrl();
        foreach ($entries as $entry) {
            if ($entry['payments']) {
                $active = $entry['host'];
                break;
            }
        }

        $settings = NexaSuiteMarketplaceSetting::current();
        $settings->public_hosts = $entries;
        if (Schema::hasColumn('nexa_suite_marketplace_settings', 'public_base_url')) {
            $settings->public_base_url = $active;
        }
        $settings->save();
    }

    /**
     * @return list<array{id: string, name: string, host: string, payments: bool}>
     */
    private function storedEntries(): array
    {
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_hosts')) {
            return [];
        }

        return $this->sanitize(NexaSuiteMarketplaceSetting::current()->public_hosts);
    }

    private function legacyHost(): ?string
    {
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_base_url')) {
            return null;
        }

        return self::normalize((string) (NexaSuiteMarketplaceSetting::current()->public_base_url ?? ''));
    }

    /**
     * @return list<array{id: string, name: string, host: string, payments: bool}>
     */
    private function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        $names = [];
        $ids = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $host = self::normalize(isset($row['host']) ? (string) $row['host'] : null);
            $name = trim((string) ($row['name'] ?? ''));
            $id = trim((string) ($row['id'] ?? ''));
            if ($host === null || $name === '' || $id === '') {
                continue;
            }
            $nameKey = mb_strtolower($name);
            if (isset($names[$nameKey]) || isset($ids[$id])) {
                continue;
            }
            $names[$nameKey] = true;
            $ids[$id] = true;
            $out[] = [
                'id' => $id,
                'name' => $name,
                'host' => $host,
                'payments' => (bool) ($row['payments'] ?? false),
            ];
        }

        return $out === [] ? [] : $this->ensurePayments($out);
    }

    /**
     * @param  list<array{id: string, name: string, host: string, payments: bool}>  $entries
     * @return list<array{id: string, name: string, host: string, payments: bool}>
     */
    private function ensurePayments(array $entries): array
    {
        if ($entries === []) {
            return self::defaultEntries();
        }

        $marked = [];
        foreach ($entries as $index => $entry) {
            if ($entry['payments']) {
                $marked[] = $index;
            }
        }
        $keep = $marked[0] ?? null;
        if ($keep === null) {
            $keep = 0;
            foreach ($entries as $index => $entry) {
                if ($entry['id'] === 'productie' || mb_strtolower($entry['name']) === 'productie') {
                    $keep = $index;
                    break;
                }
            }
        }
        foreach ($entries as $index => $entry) {
            $entries[$index]['payments'] = $index === $keep;
        }

        return array_values($entries);
    }

    private static function decodeJson(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function hostIsAcceptable(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        return str_contains($host, '.') && ! str_contains($host, ' ');
    }
}
