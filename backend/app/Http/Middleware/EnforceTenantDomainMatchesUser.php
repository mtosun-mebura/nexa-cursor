<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantDomainMatchesUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if (! app()->bound('resolved_tenant_id')) {
            return $next($request);
        }

        $resolvedId = app('resolved_tenant_id');
        if ($resolvedId === null) {
            return $next($request);
        }

        $user = auth()->user();
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
