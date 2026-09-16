<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncAdminSelectedTenantFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('admin.tenant.switch')) {
            return $next($request);
        }

        $companyId = $this->requestedTenantCompanyId($request);
        if ($companyId === null) {
            return $next($request);
        }

        if ((int) session('selected_tenant') === $companyId) {
            return $next($request);
        }

        if (! Company::query()->whereKey($companyId)->exists()) {
            return $next($request);
        }

        session(['selected_tenant' => $companyId]);

        return $next($request);
    }

    protected function requestedTenantCompanyId(Request $request): ?int
    {
        foreach (['tenant_company', 'wizard_company'] as $key) {
            $raw = $request->input($key);
            if ($raw === null || $raw === '') {
                continue;
            }
            if (! is_numeric($raw)) {
                continue;
            }
            $id = (int) $raw;
            if ($id > 0) {
                return $id;
            }
        }

        return null;
    }
}
