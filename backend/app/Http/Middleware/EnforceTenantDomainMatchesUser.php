<?php

namespace App\Http\Middleware;

use App\Models\CompanyDomain;
use App\Support\Tenancy\CentralDomains;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantDomainMatchesUser
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $authenticated = auth()->check();
        } catch (\Throwable $e) {
            report($e);
            $authenticated = false;
        }
        if (! $authenticated) {
            return $next($request);
        }

        // Centrale host (localhost / SaaS): geen tenant-domein-afdwinging.
        // Dev-simulatie van een andere tenant-host mag admin/home op de centrale URL niet blokkeren.
        $host = CompanyDomain::normalizeHost($request->getHost());
        if (CentralDomains::isCentral($host)) {
            return $next($request);
        }

        if (! app()->bound('resolved_tenant_id')) {
            return $next($request);
        }

        $resolvedId = app('resolved_tenant_id');
        if ($resolvedId === null) {
            return $next($request);
        }

        try {
            $user = auth()->user();
        } catch (\Throwable $e) {
            report($e);

            return $next($request);
        }
        if ($user === null) {
            return $next($request);
        }

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        if ($user->company_id === null) {
            return $next($request);
        }

        if ((int) $user->company_id !== (int) $resolvedId) {
            abort(403, 'Je hebt geen toegang tot deze omgeving.');
        }

        return $next($request);
    }
}
