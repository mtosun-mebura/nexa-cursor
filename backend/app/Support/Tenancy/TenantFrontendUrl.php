<?php

namespace App\Support\Tenancy;

use App\Models\Company;
use App\Models\CompanyDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class TenantFrontendUrl
{
    /**
     * Frontend-URL die op dev (localhost) de tenant via ?_tenant_host behoudt;
     * op productie het tenant-domein gebruikt wanneer je niet al op dat domein zit.
     */
    public static function for(string $url, ?int $companyId = null, ?Request $request = null): string
    {
        $request = $request ?? request();
        $absolute = self::toAbsoluteUrl($url, $request);
        $tenantHost = self::resolveTenantPublicHost($companyId, $request);

        if ($tenantHost === null || $tenantHost === '') {
            return $absolute;
        }

        $currentHost = CompanyDomain::normalizeHost($request->getHost());
        if ($currentHost === $tenantHost) {
            return $absolute;
        }

        if (app()->isProduction()) {
            return self::replaceHost($absolute, $tenantHost, $request->secure());
        }

        $devParam = (string) config('tenancy.dev_effective_host_query_param', '');
        if ($devParam === '' || ! CentralDomains::isCentral($currentHost)) {
            return $absolute;
        }

        return self::appendQueryParam($absolute, $devParam, $tenantHost);
    }

    public static function resolveTenantPublicHost(?int $companyId = null, ?Request $request = null): ?string
    {
        $request = $request ?? request();

        if (! app()->isProduction()) {
            $devParam = (string) config('tenancy.dev_effective_host_query_param', '');
            if ($devParam !== '' && $request->has($devParam)) {
                $raw = $request->query($devParam);
                if (is_string($raw) && $raw !== '') {
                    $normalized = CompanyDomain::normalizeHost($raw);
                    if ($normalized !== '') {
                        return $normalized;
                    }
                }
            }
        }

        $companyId = $companyId ?? self::resolveCompanyId();
        if ($companyId === null || $companyId <= 0) {
            return null;
        }

        return self::resolvePrimaryHostForCompany($companyId);
    }

    protected static function resolveCompanyId(): ?int
    {
        if (app()->bound('resolved_tenant_id')) {
            $resolved = (int) app('resolved_tenant_id');
            if ($resolved > 0) {
                return $resolved;
            }
        }

        $user = Auth::user();
        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        return null;
    }

    protected static function resolvePrimaryHostForCompany(int $companyId): ?string
    {
        $company = Company::query()->with('domains')->find($companyId);
        if ($company === null) {
            return null;
        }

        $primaryDomain = $company->domains->firstWhere('is_primary', true);
        $host = $primaryDomain?->host ?? $company->domains->sortBy('id')->first()?->host;
        if (is_string($host) && $host !== '') {
            return CompanyDomain::normalizeHost($host);
        }

        foreach (config('tenancy.dev_host_company_map', []) as $mapHost => $mapCompanyId) {
            if ((int) $mapCompanyId === $companyId) {
                return CompanyDomain::normalizeHost((string) $mapHost);
            }
        }

        return null;
    }

    protected static function toAbsoluteUrl(string $url, Request $request): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }

    protected static function replaceHost(string $url, string $host, bool $secure): string
    {
        $parts = parse_url($url) ?: [];
        $scheme = $secure ? 'https' : 'http';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$path.$query;
    }

    protected static function appendQueryParam(string $url, string $param, string $value): string
    {
        $parts = parse_url($url) ?: [];
        $queryParams = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $queryParams);
        }
        $queryParams[$param] = $value;
        $path = ($parts['path'] ?? '/') ?: '/';

        return url($path).'?'.http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
    }
}
