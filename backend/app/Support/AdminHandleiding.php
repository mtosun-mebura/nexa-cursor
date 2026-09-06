<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyEntitlementService;
use App\Support\Admin\AdminTenantScope;

class AdminHandleiding
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function pages(): array
    {
        $pages = config('admin-handleiding.pages', []);

        return collect($pages)
            ->sortBy(fn (array $page) => $page['order'] ?? 999)
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function pagesForCurrentUser(): array
    {
        $user = auth()->user();

        return self::pagesForUser($user instanceof User ? $user : null);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function pagesForUser(?User $user): array
    {
        if (! $user) {
            return collect(self::pages())
                ->filter(fn (array $page) => empty($page['super_admin_only']))
                ->all();
        }

        $company = self::companyForUser($user);
        if ($user->isSuperAdmin() && $company === null) {
            return self::pages();
        }

        $visible = self::visiblePages($company);
        if (! $user->isSuperAdmin()) {
            return $visible;
        }

        $platform = collect(self::pages())
            ->filter(fn (array $page) => ! empty($page['super_admin_only']))
            ->all();

        return collect($visible)
            ->union($platform)
            ->sortBy(fn (array $page) => $page['order'] ?? 999)
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function visiblePages(?Company $company): array
    {
        return collect(self::pages())
            ->filter(fn (array $page) => self::pageVisible($page, $company))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $page
     */
    public static function pageVisible(array $page, ?Company $company): bool
    {
        if (! empty($page['super_admin_only'])) {
            return false;
        }

        $packages = $page['packages'] ?? null;
        $capabilities = $page['capabilities'] ?? null;
        $hasPackageGate = is_array($packages) && $packages !== [];
        $hasCapabilityGate = is_array($capabilities) && $capabilities !== [];

        if (! $hasPackageGate && ! $hasCapabilityGate) {
            return true;
        }

        if (! $company) {
            return false;
        }

        $packageKey = trim((string) ($company->package_key ?? ''));
        if ($hasPackageGate && $packageKey !== '' && ! in_array($packageKey, $packages, true)) {
            return false;
        }

        if ($hasCapabilityGate) {
            $entitlements = app(CompanyEntitlementService::class);
            foreach ($capabilities as $capability) {
                if (! $entitlements->allows($company, (string) $capability)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function page(string $slug): ?array
    {
        $page = config("admin-handleiding.pages.{$slug}");

        return is_array($page) ? $page : null;
    }

    private static function companyForUser(User $user): ?Company
    {
        if (auth()->id() === $user->getKey()) {
            $tenantId = app(AdminTenantScope::class)->selectedTenantId();
            if ($tenantId) {
                return Company::query()->find($tenantId);
            }

            return $user->isSuperAdmin() ? null : $user->company;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->company;
    }
}
