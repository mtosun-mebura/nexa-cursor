<?php

namespace App\Support\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AdminTenantScope
{
    public function selectedTenantId(): ?int
    {
        $user = auth()->user();
        if ($user === null) {
            return null;
        }

        if ($user->hasRole('super-admin')) {
            $selected = session('selected_tenant');
            if ($selected !== null && $selected !== '' && is_numeric($selected)) {
                return (int) $selected;
            }

            return null;
        }

        return $user->company_id ? (int) $user->company_id : null;
    }

    public function isSuperAdminWithoutTenant(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole('super-admin')
            && $this->selectedTenantId() === null;
    }

    public function isTenantScopedActive(): bool
    {
        return ! $this->isSuperAdminWithoutTenant();
    }

    public function routeRequiresTenant(?Request $request = null): bool
    {
        $request ??= request();
        if ($request === null) {
            return false;
        }

        $routeName = $request->route()?->getName();
        if (! is_string($routeName) || ! str_starts_with($routeName, 'admin.')) {
            return false;
        }

        foreach (config('admin_tenant_scope.exempt_route_names', []) as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return false;
            }
        }

        return true;
    }

    public function shouldShowTenantNotice(): bool
    {
        return $this->isSuperAdminWithoutTenant() && $this->routeRequiresTenant();
    }

    public function shouldHideContent(): bool
    {
        if (! $this->shouldShowTenantNotice()) {
            return false;
        }

        $routeName = request()->route()?->getName();
        if (! is_string($routeName)) {
            return true;
        }

        foreach (config('admin_tenant_scope.notice_only_route_names', []) as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return false;
            }
        }

        return true;
    }

    public function noticeVariant(): string
    {
        $routeName = request()->route()?->getName();
        if (! is_string($routeName)) {
            return 'default';
        }

        foreach (config('admin_tenant_scope.route_notice_variants', []) as $pattern => $variant) {
            if (Str::is($pattern, $routeName)) {
                return (string) $variant;
            }
        }

        return 'default';
    }

    public function defaultNoticeMessage(): string
    {
        return (string) config('admin_tenant_scope.default_notice');
    }

    /**
     * Optionele tenantfilter voor platform-pagina's: queryparam wint van de zijbalkkeuze.
     * Lege queryparam = alle tenants, ook als er in de zijbalk een tenant staat.
     */
    public function optionalFilterTenantId(?Request $request = null, string $input = 'company_id'): ?int
    {
        $request ??= request();
        if ($request === null) {
            return $this->selectedTenantId();
        }

        if ($request->exists($input)) {
            $raw = $request->input($input);
            if ($raw === null || $raw === '') {
                return null;
            }

            return is_numeric($raw) ? (int) $raw : null;
        }

        return $this->selectedTenantId();
    }
}
