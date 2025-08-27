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
        } else {
            app()->forgetInstance('tenant_id');
        }
        return $next($request);
    }
}


