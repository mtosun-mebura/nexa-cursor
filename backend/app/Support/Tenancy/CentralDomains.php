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

        if (in_array($normalized, self::all(), true)) {
            return true;
        }

        // Lokaal/staging: LAN-IP (bijv. 192.168.x.x) voor mobiele preview met ?_tenant_host=…
        if (! app()->isProduction() && self::isLocalDevEntryHost($normalized)) {
            return true;
        }

        return false;
    }

    /**
     * localhost, loopback en private IP-ranges —zelfde gedrag als APP_URL op localhost voor dev-preview.
     */
    public static function isLocalDevEntryHost(string $host): bool
    {
        if ($host === 'localhost') {
            return true;
        }

        if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return false;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
