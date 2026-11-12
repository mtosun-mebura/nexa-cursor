<?php

namespace App\Http\Middleware;

use App\Models\CompanyDomain;
use App\Support\Tenancy\CentralDomains;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www → apex (301) voor centrale hosts én tenant-domeinen, zodat Google één canonieke host indexeert.
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
        if ($apex === '') {
            return $next($request);
        }

        // Centrale platformhosts (nexasuite.nl): altijd www → apex.
        if (CentralDomains::isCentral($host) || CentralDomains::isCentral($apex)) {
            return $this->redirectToApex($request, $apex);
        }

        // Tenant custom domains: alleen als apex (of www) bekend is.
        $known = CompanyDomain::query()
            ->whereIn('host', [$apex, $host])
            ->exists();
        if (! $known) {
            return $next($request);
        }

        return $this->redirectToApex($request, $apex);
    }

    private function redirectToApex(Request $request, string $apex): Response
    {
        $scheme = $request->secure() ? 'https' : $request->getScheme();
        $target = $scheme.'://'.$apex.$request->getRequestUri();

        return redirect()->away($target, 301);
    }
}
