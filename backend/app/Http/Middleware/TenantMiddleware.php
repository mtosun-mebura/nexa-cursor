<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->company_id) {
            app()->instance('tenant_id', (int) $user->company_id);
        } elseif (app()->bound('resolved_tenant_id') && app('resolved_tenant_id') !== null) {
            app()->instance('tenant_id', (int) app('resolved_tenant_id'));
        } else {
            app()->forgetInstance('tenant_id');
        }

        return $next($request);
    }
}
