<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $registrar = app(PermissionRegistrar::class);
        if ($user && $user->company_id) {
            $teamId = (int) $user->company_id;
            app()->instance('tenant_id', $teamId);
            $registrar->setPermissionsTeamId($teamId);
        } elseif (app()->bound('resolved_tenant_id') && app('resolved_tenant_id') !== null) {
            $teamId = (int) app('resolved_tenant_id');
            app()->instance('tenant_id', $teamId);
            $registrar->setPermissionsTeamId($teamId);
        } else {
            app()->forgetInstance('tenant_id');
            $registrar->setPermissionsTeamId(null);
        }

        return $next($request);
    }
}
