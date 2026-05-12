<?php

namespace App\Http\Middleware;

use App\Models\CompanyDomain;
use App\Support\Tenancy\CentralDomains;
use App\Support\Tenancy\TenantFromHostResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Niet-productie: na StartSession — simuleer tenant-host via query + sessie op een centrale dev-URL (localhost).
 */
class ApplyDevSimulatedTenantHost
{
    private const SESSION_DEV_EFFECTIVE_HOST = 'nexa.tenancy_dev_effective_host';

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            return $next($request);
        }

        $param = (string) config('tenancy.dev_effective_host_query_param', '');
        if ($param === '') {
            return $next($request);
        }

        $fromRequest = CompanyDomain::normalizeHost($request->getHost());
        if (! CentralDomains::isCentral($fromRequest)) {
            return $next($request);
        }

        $effective = $this->effectiveSimulatedHost($request);
        if ($effective === null || $effective === '') {
            return $next($request);
        }

        if (CentralDomains::isCentral($effective)) {
            return $next($request);
        }

        app()->forgetInstance('resolved_tenant');
        app()->forgetInstance('resolved_tenant_id');

        TenantFromHostResolver::bindTenantForNormalizedHost($effective);

        return $next($request);
    }

    private function effectiveSimulatedHost(Request $request): ?string
    {
        $param = (string) config('tenancy.dev_effective_host_query_param', '');

        if ($request->has($param)) {
            $raw = $request->query($param);
            if (! is_string($raw) || $raw === '') {
                $request->session()->forget(self::SESSION_DEV_EFFECTIVE_HOST);

                return null;
            }

            $candidate = CompanyDomain::normalizeHost($raw);
            if ($candidate === '' || strlen($candidate) > 253
                || filter_var($candidate, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                return null;
            }

            $request->session()->put(self::SESSION_DEV_EFFECTIVE_HOST, $candidate);

            return $candidate;
        }

        $stored = $request->session()->get(self::SESSION_DEV_EFFECTIVE_HOST);
        if (is_string($stored) && $stored !== '') {
            $candidate = CompanyDomain::normalizeHost($stored);
            if ($candidate !== '' && strlen($candidate) <= 253
                && filter_var($candidate, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
                return $candidate;
            }
            $request->session()->forget(self::SESSION_DEV_EFFECTIVE_HOST);
        }

        return null;
    }
}
