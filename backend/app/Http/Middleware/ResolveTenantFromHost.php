<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Support\Tenancy\CentralDomains;
use App\Support\Tenancy\TenantFromHostResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetInstance('resolved_tenant');
        app()->forgetInstance('resolved_tenant_id');

        $host = CompanyDomain::normalizeHost($request->getHost());

        if (! app()->isProduction()) {
            $map = config('tenancy.dev_host_company_map', []);
            $forcedId = is_array($map) ? (int) ($map[$host] ?? 0) : 0;
            if ($forcedId > 0) {
                $company = Company::query()->find($forcedId);
                if ($company !== null && $company->is_active) {
                    app()->instance('resolved_tenant', $company);
                    app()->instance('resolved_tenant_id', $forcedId);

                    return $next($request);
                }
            }
        }

        if (CentralDomains::isCentral($host)) {
            return $next($request);
        }

        TenantFromHostResolver::bindTenantForNormalizedHost($host);

        return $next($request);
    }
}
