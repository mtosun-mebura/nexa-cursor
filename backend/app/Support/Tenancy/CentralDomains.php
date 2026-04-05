<?php

namespace App\Support\Tenancy;

final class CentralDomains
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $hosts = config('tenancy.central_domains', []);
        if (! is_array($hosts)) {
            $hosts = [];
        }
        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appUrlHost) && $appUrlHost !== '') {
            $hosts[] = strtolower($appUrlHost);
        }
        $normalized = [];
        foreach ($hosts as $h) {
            if (! is_string($h) || $h === '') {
                continue;
            }
            $normalized[] = strtolower(trim(explode(':', $h, 2)[0]));
        }

        return array_values(array_unique($normalized));
    }

    public static function isCentral(string $host): bool
    {
        $normalized = strtolower(trim(explode(':', $host, 2)[0]));

        return in_array($normalized, self::all(), true);
    }
}
