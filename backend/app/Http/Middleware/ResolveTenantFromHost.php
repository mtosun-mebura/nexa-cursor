<?php

namespace App\Http\Middleware;

use App\Models\CompanyDomain;
use App\Support\Tenancy\CentralDomains;
use App\Support\Tenancy\TenantParentDomains;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetInstance('resolved_tenant');
        app()->forgetInstance('resolved_tenant_id');

        $host = CompanyDomain::normalizeHost($request->getHost());

        if (CentralDomains::isCentral($host)) {
            return $next($request);
        }

        try {
            $domain = CompanyDomain::query()->where('host', $host)->first();
        } catch (QueryException) {
            // Tabel ontbreekt (migraties niet gedraaid) of DB tijdelijk niet bereikbaar
            return $next($request);
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

            return $next($request);
        }

        $company = $domain->company;
        if ($company === null || ! $company->is_active) {
            return $next($request);
        }

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', (int) $company->id);

        return $next($request);
    }
}
