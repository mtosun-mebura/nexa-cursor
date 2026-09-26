<?php

namespace App\Http\Middleware;

use App\Models\CompanyDomain;
use App\Support\Tenancy\CentralDomains;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant custom domains: www → apex (301), zodat Google één canonieke host indexeert.
 */
class PreferApexHostRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $host = strtolower((string) $request->getHost());
        if ($host === '' || ! str_starts_with($host, 'www.')) {
            return $next($request);
        }

        $apex = substr($host, 4);
        if ($apex === '' || CentralDomains::isCentral($host) || CentralDomains::isCentral($apex)) {
            return $next($request);
        }

        // Alleen redirecten als apex een bekende company-domain is (of www zelf).
        $known = CompanyDomain::query()
            ->whereIn('host', [$apex, $host])
            ->exists();
        if (! $known) {
            return $next($request);
        }

        $target = $request->getScheme().'://'.$apex.$request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
