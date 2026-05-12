<?php

namespace App\Support\Tenancy;

use App\Models\CompanyDomain;
use Illuminate\Database\QueryException;

/**
 * Zet {@see \App\Http\Middleware\ResolveTenantFromHost} / dev-simulatie om naar dezelfde DB-lookup.
 */
final class TenantFromHostResolver
{
    /**
     * Host moet al genormaliseerd zijn (lowercase, zonder poort). Geen centrale-domein-check hier.
     */
    public static function bindTenantForNormalizedHost(string $host): void
    {
        try {
            $domain = CompanyDomain::query()->where('host', $host)->first();
        } catch (QueryException) {
            return;
        }

        if ($domain === null) {
            try {
                $fallbackCompany = TenantParentDomains::companyFromSubdomainHost($host);
            } catch (QueryException) {
                $fallbackCompany = null;
            }
            if ($fallbackCompany !== null) {
                app()->instance('resolved_tenant', $fallbackCompany);
                app()->instance('resolved_tenant_id', (int) $fallbackCompany->id);
            }

            return;
        }

        $company = $domain->company;
        if ($company === null || ! $company->is_active) {
            return;
        }

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', (int) $company->id);
    }
}
