<?php

namespace App\Support;

use App\Models\GeneralSetting;

/**
 * Publiek VPS-/Coolify-IP voor tenant-DNS (A-records) en documentatie.
 */
final class CoolifyVpsPublicIp
{
    public const SETTING_KEY = 'COOLIFY_VPS_PUBLIC_IP';

    public const DEFAULT = '152.239.119.238';

    public static function get(): string
    {
        $stored = GeneralSetting::get(self::SETTING_KEY, null);
        if ($stored === null || trim((string) $stored) === '') {
            self::set(self::DEFAULT);

            return self::DEFAULT;
        }

        return trim((string) $stored);
    }

    public static function set(string $ip): void
    {
        $ip = trim($ip);
        GeneralSetting::set(self::SETTING_KEY, $ip !== '' ? $ip : self::DEFAULT);
    }
}
