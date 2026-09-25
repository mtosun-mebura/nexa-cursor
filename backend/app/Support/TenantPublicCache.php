<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-busting voor publieke tenant-assets (logo/favicon) en gerelateerde website-inhoud.
 *
 * Admin-logo-routes gebruiken no-store + ?v=; website mag cachen zolang de URL-versie meeloopt.
 */
class TenantPublicCache
{
    public static function versionForCompany(?Company $company): string
    {
        if (! $company) {
            return '0';
        }

        $updated = $company->updated_at?->getTimestamp() ?? 0;
        $bump = (int) Cache::get(self::bumpKey((int) $company->id), 0);

        return (string) max($updated, $bump);
    }

    /**
     * Bump bij logo/favicon/tarieven e.d. zodat website-asset-URL's meteen veranderen.
     */
    public static function bump(Company|int|null $company): void
    {
        $id = $company instanceof Company ? (int) $company->id : (int) $company;
        if ($id <= 0) {
            return;
        }

        $key = self::bumpKey($id);
        $next = ((int) Cache::get($key, 0)) + 1;
        if ($next < time()) {
            $next = time();
        }
        Cache::forever($key, $next);

        // updated_at meenemen zodat DB-gestuurde ?v= ook vernieuwt zonder cache-store.
        Company::query()->whereKey($id)->update(['updated_at' => now()]);
    }

    public static function appendVersion(string $url, Company|int|null $company): string
    {
        $version = $company instanceof Company
            ? self::versionForCompany($company)
            : self::versionForCompanyId((int) $company);

        if ($version === '0' || $version === '') {
            return $url;
        }

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.'v='.$version;
    }

    public static function appendFileMtime(string $url, string $absolutePath): string
    {
        if ($absolutePath === '' || ! is_file($absolutePath)) {
            return $url;
        }

        $mtime = (string) filemtime($absolutePath);
        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.'v='.$mtime;
    }

    private static function versionForCompanyId(int $companyId): string
    {
        if ($companyId <= 0) {
            return '0';
        }

        $company = Company::query()->find($companyId);

        return self::versionForCompany($company);
    }

    private static function bumpKey(int $companyId): string
    {
        return 'tenant_public_asset_v:'.$companyId;
    }
}
