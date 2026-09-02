<?php

namespace App\Services;

use App\Http\Controllers\Admin\AdminCompanyWizardController;
use App\Models\Company;
use App\Models\CompanyConfigAccessGrant;
use App\Models\User;
use App\Support\TenantConfigCapability;

class TenantConfigAccessService
{
    public const DENIED_MESSAGE = 'Deze configuratie is alleen voor super-admin. Vraag een beheerder om toegang.';

    public function can(?User $user, Company $company, string $capability): bool
    {
        if ($user === null || ! TenantConfigCapability::isValid($capability)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $user->company_id !== (int) $company->id) {
            return false;
        }

        return CompanyConfigAccessGrant::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->where('capability', $capability)
            ->exists();
    }

    public function canAccessWizardStep(?User $user, Company $company, int $step): bool
    {
        $keys = TenantConfigCapability::keysForWizardStep($step);
        if ($keys === []) {
            return $user !== null;
        }

        foreach ($keys as $key) {
            if ($this->can($user, $company, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    public function lockedWizardSteps(?User $user, ?Company $company): array
    {
        if ($company === null || $user === null || $user->isSuperAdmin()) {
            return [];
        }

        $locked = [];
        foreach (TenantConfigCapability::wizardConfigSteps() as $step) {
            if (! $this->canAccessWizardStep($user, $company, $step)) {
                $locked[] = $step;
            }
        }

        return $locked;
    }

    public function nextAccessibleStep(?User $user, Company $company, int $afterStep): int
    {
        $from = AdminCompanyWizardController::clampStep($afterStep);
        for ($step = $from; $step <= AdminCompanyWizardController::TOTAL_STEPS; $step++) {
            if ($this->canAccessWizardStep($user, $company, $step)) {
                return $step;
            }
        }

        return AdminCompanyWizardController::TOTAL_STEPS;
    }

    public function previousAccessibleStep(?User $user, Company $company, int $beforeStep): ?int
    {
        for ($step = $beforeStep - 1; $step >= 1; $step--) {
            if ($this->canAccessWizardStep($user, $company, $step)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function grantedKeys(User $user, Company $company): array
    {
        if ($user->isSuperAdmin()) {
            return TenantConfigCapability::keys();
        }

        return CompanyConfigAccessGrant::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->pluck('capability')
            ->filter(fn ($key) => TenantConfigCapability::isValid((string) $key))
            ->values()
            ->all();
    }

    /**
     * @return array<int, list<string>>
     */
    public function grantsByUserId(Company $company): array
    {
        $map = [];
        $rows = CompanyConfigAccessGrant::query()
            ->where('company_id', $company->id)
            ->get(['user_id', 'capability']);

        foreach ($rows as $row) {
            if (! TenantConfigCapability::isValid((string) $row->capability)) {
                continue;
            }
            $map[(int) $row->user_id][] = (string) $row->capability;
        }

        return $map;
    }

    /**
     * @param  list<string>  $capabilities
     * @return list<string> Newly granted capability keys
     */
    public function syncUserGrants(Company $company, User $user, array $capabilities, ?User $grantedBy): array
    {
        if ((int) $user->company_id !== (int) $company->id) {
            return [];
        }

        $wanted = array_values(array_unique(array_filter(
            $capabilities,
            fn ($key) => is_string($key) && TenantConfigCapability::isValid($key)
        )));

        $existing = CompanyConfigAccessGrant::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('capability');

        foreach ($existing as $capability => $row) {
            if (! in_array($capability, $wanted, true)) {
                $row->delete();
            }
        }

        $added = [];
        foreach ($wanted as $capability) {
            if ($existing->has($capability)) {
                continue;
            }
            CompanyConfigAccessGrant::query()->create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'capability' => $capability,
                'granted_by' => $grantedBy?->id,
            ]);
            $added[] = $capability;
        }

        return $added;
    }

    public function assertCan(?User $user, Company $company, string $capability, string $message = self::DENIED_MESSAGE): void
    {
        if (! $this->can($user, $company, $capability)) {
            abort(403, $message);
        }
    }

    public function assertWizardStep(?User $user, Company $company, int $step): void
    {
        if (! $this->canAccessWizardStep($user, $company, $step)) {
            abort(403, self::DENIED_MESSAGE);
        }
    }

    public function assertWebsiteAccess(?User $user, ?int $companyId): void
    {
        if ($user?->isSuperAdmin()) {
            return;
        }

        if ($user === null || $companyId === null) {
            abort(403, 'Alleen super-admins hebben toegang tot website-pagina\'s.');
        }

        $company = Company::query()->find($companyId);
        if ($company === null) {
            abort(403, 'Alleen super-admins hebben toegang tot website-pagina\'s.');
        }

        $this->assertCan($user, $company, TenantConfigCapability::WEBSITE, 'Alleen super-admins of gebruikers met website-toegang kunnen pagina\'s beheren.');
    }
}
