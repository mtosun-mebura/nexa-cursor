<?php

namespace App\Services;

use App\Helpers\GeoHelper;
use App\Models\Company;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Services\PlatformBilling\TenantBillingAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NearestTaxiTenantResolver
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected TenantBillingAccessService $billingAccess,
        protected CompanyEntitlementService $entitlements,
    ) {}

    /**
     * @return array{company: Company, distance_km: float}|null
     */
    public function resolve(float $pickupLat, float $pickupLng, array $excludeCompanyIds = []): ?array
    {
        $ranked = $this->rankedCandidates($pickupLat, $pickupLng, $excludeCompanyIds);

        foreach ($ranked as $candidate) {
            /** @var Company $company */
            $company = $candidate['company'];
            if ($this->billingAccess->isBookingBlocked($company)) {
                continue;
            }
            if (! $this->entitlements->allows($company, \App\Support\TenantPackageCapability::WEBSITE_BOOKING)) {
                continue;
            }
            if (! $this->companyHasActiveVehicles($company)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @return list<array{company: Company, distance_km: float}>
     */
    public function rankedCandidates(float $pickupLat, float $pickupLng, array $excludeCompanyIds = []): array
    {
        $exclude = array_values(array_filter(array_map('intval', $excludeCompanyIds)));
        $ranked = [];

        foreach ($this->eligibleCompanies() as $company) {
            if (in_array((int) $company->id, $exclude, true)) {
                continue;
            }
            $coords = $this->coordinatesFor($company);
            if ($coords === null) {
                continue;
            }
            $ranked[] = [
                'company' => $company,
                'distance_km' => round(GeoHelper::calculateDistance(
                    $pickupLat,
                    $pickupLng,
                    $coords['lat'],
                    $coords['lng']
                ), 2),
            ];
        }

        usort($ranked, static fn (array $a, array $b) => $a['distance_km'] <=> $b['distance_km']);

        return $ranked;
    }

    /**
     * @return Collection<int, Company>
     */
    public function eligibleCompanies(): Collection
    {
        $query = Company::query()
            ->with(['modules', 'mainLocation'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_main', false)->orWhereNull('is_main');
            });

        if (Schema::hasColumn('companies', 'accepts_nexa_suite_bookings')) {
            $query->where('accepts_nexa_suite_bookings', true);
        }

        return $query->get()->filter(fn (Company $company) => $company->hasTaxiModule())->values();
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function coordinatesFor(Company $company): ?array
    {
        $lat = $this->numericOrNull($company->latitude);
        $lng = $this->numericOrNull($company->longitude);
        if ($lat !== null && $lng !== null) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        $location = $company->mainLocation;
        if ($location) {
            $lat = $this->numericOrNull($location->latitude ?? null);
            $lng = $this->numericOrNull($location->longitude ?? null);
            if ($lat !== null && $lng !== null) {
                return ['lat' => $lat, 'lng' => $lng];
            }
            $cityCoords = GeoHelper::getCityCoordinates((string) ($location->city ?? ''));
            if (is_array($cityCoords)) {
                return [
                    'lat' => (float) $cityCoords['latitude'],
                    'lng' => (float) $cityCoords['longitude'],
                ];
            }
        }

        $cityCoords = GeoHelper::getCityCoordinates((string) ($company->city ?? ''));
        if (is_array($cityCoords)) {
            return [
                'lat' => (float) $cityCoords['latitude'],
                'lng' => (float) $cityCoords['longitude'],
            ];
        }

        return null;
    }

    public function companyHasActiveVehicles(Company $company): bool
    {
        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return true;
        }

        return Vehicle::on($conn)
            ->where('company_id', $company->id)
            ->where('active', true)
            ->exists();
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        if ($number == 0.0) {
            return null;
        }

        return $number;
    }
}
