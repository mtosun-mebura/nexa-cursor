<?php

namespace App\Services;

use App\Models\Company;
use App\Support\ModuleSchemaAvailability;

/**
 * Bepaalt welke dashboard-onderdelen getoond worden op basis van actieve modules (tenant of globaal).
 */
final class AdminDashboardModuleContext
{
    public function __construct(
        protected ModuleManager $moduleManager,
    ) {}

    /**
     * Of vacatures/skillmatching in de huidige admin-context getoond en bereikbaar mogen zijn.
     */
    public function skillmatchingAvailable(?int $tenantId = null): bool
    {
        if ($tenantId === null) {
            $sessionTenant = session('selected_tenant');
            $tenantId = $sessionTenant ? (int) $sessionTenant : null;
        }

        return $this->resolve($tenantId)['show_skillmatching'];
    }

    /**
     * @return array{show_skillmatching: bool, show_taxi: bool}
     */
    public function resolve(?int $tenantId = null): array
    {
        $company = $this->resolveCompany($tenantId);

        $wantSkillmatching = false;
        $wantTaxi = false;

        if ($company !== null) {
            $wantSkillmatching = $company->hasSkillmatchingModule();
            $wantTaxi = $company->hasTaxiModule();
        }

        if (! $wantSkillmatching && ! $wantTaxi) {
            $wantSkillmatching = $this->moduleManager->isActive('skillmatching');
            $wantTaxi = $this->moduleManager->isActive('taxi');
        }

        return [
            'show_skillmatching' => $wantSkillmatching && ModuleSchemaAvailability::vacanciesTableExists(),
            'show_taxi' => $wantTaxi && ModuleSchemaAvailability::rideRequestsTableExists(),
        ];
    }

    protected function resolveCompany(?int $tenantId): ?Company
    {
        if ($tenantId !== null && $tenantId > 0) {
            return Company::query()->find($tenantId);
        }

        $user = auth()->user();
        if ($user && $user->company_id) {
            return Company::query()->find($user->company_id);
        }

        return null;
    }
}
