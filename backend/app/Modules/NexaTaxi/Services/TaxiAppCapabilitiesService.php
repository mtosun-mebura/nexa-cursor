<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyEntitlementService;
use App\Services\MarketplaceCompanyRegistrationService;
use App\Services\ModuleDatabaseService;
use App\Support\TenantPackageCapability;

/**
 * Bepaalt welke schermen de geïntegreerde Nexa Taxi-app toont na login.
 */
class TaxiAppCapabilitiesService
{
    public function __construct(
        protected TaxiDriverEligibilityService $drivers,
        protected TaxiContractPortalAccessService $contractAccess,
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected CompanyEntitlementService $entitlements,
        protected ModuleDatabaseService $moduleDb,
    ) {}

    /**
     * @return array{
     *     screens: list<array{key: string, title: string, description: string, badge: string, url: string, primary?: bool}>,
     *     modes: array{chauffeur: bool, contract: bool, marketplace: bool, network: bool},
     *     default_screen: string|null,
     *     company: array{id: int|null, name: string|null, package_key: string|null}
     * }
     */
    public function forUser(User $user): array
    {
        $companyId = (int) ($user->company_id ?? 0);
        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        $packageKey = $company ? (string) ($company->package_key ?? '') : '';
        $isMarketplace = strcasecmp($packageKey, MarketplaceCompanyRegistrationService::PACKAGE_KEY) === 0;

        $canDriver = $companyId > 0
            && $this->drivers->isChauffeurForCompany($user, $companyId)
            && $this->entitlements->allows($company, TenantPackageCapability::DRIVER_APP);

        $canContract = false;
        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
            $canContract = $this->contractAccess->userMayAccessPortal($user, $conn);
        } catch (\Throwable) {
            $canContract = $this->drivers->rolesIncludeContract($user->webRoleNames());
        }

        $networkEnabled = $canDriver && $companyId > 0 && $this->dispatchSettings->networkEnabled($companyId);
        $marketplaceMode = $canDriver && $isMarketplace;

        $screens = [];

        if ($canDriver) {
            $badge = 'Chauffeur';
            $description = 'Ritten ontvangen, accepteren en navigeren.';
            if ($marketplaceMode && $networkEnabled) {
                $badge = 'Marketplace + Network';
                $description = 'Marketplace-ritten én NEXA Network-ritten. Locatie blijft aan op de achtergrond.';
            } elseif ($marketplaceMode) {
                $badge = 'Marketplace';
                $description = 'Nexa Suite-ritten in jouw straal. Locatie blijft aan op de achtergrond.';
            } elseif ($networkEnabled) {
                $badge = 'Network';
                $description = 'Eigen ritten én NEXA Network-partnerritten.';
            }

            $screens[] = [
                'key' => 'driver',
                'title' => 'Doorgaan als chauffeur',
                'description' => $description,
                'badge' => $badge,
                'url' => '/taxi/chauffeur',
                'primary' => true,
            ];
        }

        if ($canContract) {
            $screens[] = [
                'key' => 'contract',
                'title' => 'Doorgaan als contractant / ouder',
                'description' => 'Planning en afwezigheid voor contractvervoer.',
                'badge' => 'Contract',
                'url' => '/taxi/contract',
                'primary' => ! $canDriver,
            ];
        }

        $default = null;
        if (count($screens) === 1) {
            $default = $screens[0]['key'];
        } elseif ($canDriver) {
            $default = 'driver';
        } elseif ($canContract) {
            $default = 'contract';
        }

        return [
            'screens' => $screens,
            'modes' => [
                'chauffeur' => $canDriver,
                'contract' => $canContract,
                'marketplace' => $marketplaceMode,
                'network' => $networkEnabled,
            ],
            'default_screen' => $default,
            'company' => [
                'id' => $companyId > 0 ? $companyId : null,
                'name' => $company?->name,
                'package_key' => $packageKey !== '' ? $packageKey : null,
            ],
        ];
    }
}
