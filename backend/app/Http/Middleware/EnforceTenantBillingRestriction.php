<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Services\PlatformBilling\TenantBillingAccessService;
use App\Support\WebGuardUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantBillingRestriction
{
    public function __construct(
        protected TenantBillingAccessService $access
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->companyFromRequest($request);
        if (! $company || ! $this->access->isFullyBlocked($company)) {
            return $next($request);
        }

        $user = WebGuardUser::fromGuard('web');
        if ($user instanceof User && $user->hasRole('super-admin')) {
            return $next($request);
        }

        if ($this->isExemptPath($request)) {
            return $next($request);
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            if ($user) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => $this->access->fullBlockMessage(),
                    'redirect' => '/admin/login',
                ], 403);
            }

            return redirect()->route('admin.login')->with('error', $this->access->fullBlockMessage());
        }

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->access->fullBlockMessage(),
            ], 403);
        }

        return response()->view('frontend.tenant-billing-blocked', [
            'message' => $this->access->fullBlockMessage(),
            'company' => $company,
        ], 403);
    }

    private function companyFromRequest(Request $request): ?Company
    {
        if (app()->bound('resolved_tenant') && app('resolved_tenant') instanceof Company) {
            return app('resolved_tenant');
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            $user = WebGuardUser::fromRequest($request, 'web');
            if ($user instanceof User && $user->company_id) {
                return Company::query()->find((int) $user->company_id);
            }
        }

        return null;
    }

    private function isExemptPath(Request $request): bool
    {
        return $request->is('admin/login')
            || $request->is('admin/logout')
            || $request->is('admin/password/*')
            || $request->is('admin/wachtwoord-wijzigen')
            || $request->is('admin/forgot-password')
            || $request->is('admin/reset-password')
            || $request->is('admin/reset-password/*')
            || $request->is('up')
            || $request->is('livewire/*')
            || $request->is('proefperiode')
            || $request->is('proefperiode/*');
    }
}
