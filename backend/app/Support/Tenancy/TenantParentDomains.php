<?php

namespace App\Support\Tenancy;

use App\Models\Company;
use App\Models\CompanyDomain;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ouder-domeinen waarvoor {label}.{parent} automatisch aan een bedrijf wordt gekoppeld
 * wanneer er nog geen rij in {@see \App\Models\CompanyDomain} bestaat (bijv. taxi.nexasuite.nl).
 */
final class TenantParentDomains
{
    /** Eerste label van tenant-hosts die we niet als bedrijfsslug proberen (overlap met infra). */
    private const RESERVED_SUBDOMAIN_LABELS = [
        'www', 'mail', 'ftp', 'smtp', 'pop', 'imap', 'admin', 'api', 'cdn', 'static', 'assets', 'app', 'm', 'mobile',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $hosts = config('tenancy.tenant_parent_domains', []);
        if (! is_array($hosts)) {
            $hosts = [];
        }
        $normalized = [];
        foreach ($hosts as $h) {
            if (! is_string($h) || $h === '') {
                continue;
            }
            $normalized[] = strtolower(trim(explode(':', $h, 2)[0]));
        }

        if (function_exists('request') && request()) {
            $h = CompanyDomain::normalizeHost(request()->getHost());
            if ($h !== '' && ! CentralDomains::isCentral($h)) {
                $inferred = self::apexTwoLabels($h);
                if ($inferred !== null) {
                    $normalized[] = $inferred;
                }
            }
        }

        return array_values(array_unique(array_filter($normalized)));
    }

    /**
     * Laatste twee labels van de host (bijv. nexasuite.nl), alleen als er minstens één subdomein voor staat.
     */
    private static function apexTwoLabels(string $normalizedHost): ?string
    {
        $parts = explode('.', $normalizedHost);
        $n = count($parts);
        if ($n < 3) {
            return null;
        }

        return $parts[$n - 2].'.'.$parts[$n - 1];
    }

    public static function normalizeSubdomainKey(string $label): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($label)) ?? '';
    }

    /**
     * @param  string  $normalizedHost  Host lowercase zonder poort (zoals {@see \App\Models\CompanyDomain::normalizeHost}).
     */
    public static function companyFromSubdomainHost(string $normalizedHost): ?Company
    {
        if (CentralDomains::isCentral($normalizedHost)) {
            return null;
        }

        $firstLabel = explode('.', $normalizedHost, 2)[0] ?? '';
        if ($firstLabel !== '' && in_array(strtolower($firstLabel), self::RESERVED_SUBDOMAIN_LABELS, true)) {
            return null;
        }

        foreach (self::all() as $parent) {
            if ($parent === '' || $normalizedHost === $parent) {
                continue;
            }
            $suffix = '.'.$parent;
            if (! str_ends_with($normalizedHost, $suffix)) {
                continue;
            }
            $sub = substr($normalizedHost, 0, -strlen($suffix));
            if ($sub === '' || str_contains($sub, '.')) {
                continue;
            }
            $company = self::findActiveCompanyBySubdomainLabel($sub);
            if ($company !== null) {
                return $company;
            }
        }

        return null;
    }

    private static function findActiveCompanyBySubdomainLabel(string $subdomainLabel): ?Company
    {
        $subKey = self::normalizeSubdomainKey($subdomainLabel);
        if ($subKey === '' || ! Schema::hasTable('companies')) {
            return null;
        }

        $hasSlug = Schema::hasColumn('companies', 'slug');
        $columns = ['id', 'name'];
        if ($hasSlug) {
            $columns[] = 'slug';
        }

        return Company::query()
            ->where('is_active', true)
            ->get($columns)
            ->first(function (Company $c) use ($subKey, $hasSlug) {
                if ($hasSlug && filled($c->slug)) {
                    $fromSlug = self::normalizeSubdomainKey((string) $c->slug);
                    if ($fromSlug !== '' && $fromSlug === $subKey) {
                        return true;
                    }
                }

                return self::normalizeSubdomainKey(Str::slug((string) $c->name)) === $subKey;
            });
    }
}
