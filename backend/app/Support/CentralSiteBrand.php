<?php

namespace App\Support;

/**
 * Merk-label op centrale website_pages (company_id null).
 * nexataxi.nl is géén centraal merk — dat is tenant "Nexa Taxi" via company_domains.
 */
final class CentralSiteBrand
{
    public const NEXASUITE = 'nexasuite';

    public const NEXATAXI = 'nexataxi';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::NEXASUITE, self::NEXATAXI];
    }

    public static function isValid(?string $brand): bool
    {
        return $brand !== null && in_array($brand, self::all(), true);
    }

    /**
     * Centrale marketinghost = altijd Suite.
     */
    public static function fromHost(?string $host = null): string
    {
        return self::NEXASUITE;
    }

    public static function current(): string
    {
        return self::NEXASUITE;
    }

    public static function displayName(?string $brand = null): string
    {
        return 'NEXA Suite';
    }
}
