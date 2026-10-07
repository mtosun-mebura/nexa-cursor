<?php

namespace App\Services;

use App\Helpers\GeoHelper;
use App\Models\Company;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\PlatformBilling\TenantBillingAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NearestTaxiTenantResolver
{
    public const MARKETPLACE_RADIUS_KM = 10.0;

    public const MARKETPLACE_RADIUS_MIN_KM = 1.0;

    public const MARKETPLACE_RADIUS_MAX_KM = 100.0;

    public const MARKETPLACE_MAX_TENANTS = 5;

    /** Zelfde freshness als live-kaart: chauffeurs met recente presence. */
    public const DRIVER_PRESENCE_MAX_AGE_SECONDS = 180;

    public static function normalizeRadiusKm(mixed $value): float
    {
        if (! is_numeric($value)) {
            return self::MARKETPLACE_RADIUS_KM;
        }

        $radius = (float) $value;
        if ($radius <= 0) {
            return self::MARKETPLACE_RADIUS_KM;
        }

        return max(self::MARKETPLACE_RADIUS_MIN_KM, min(self::MARKETPLACE_RADIUS_MAX_KM, $radius));
    }

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
        $nearby = $this->resolveNearby($pickupLat, $pickupLng, 1, null, $excludeCompanyIds);

        return $nearby[0] ?? null;
    }

    /**
     * Dichtstbijzijnde geschikte taxibedrijven op basis van online taxi's (chauffeur-GPS)
     * rond de ophaallocatie — niet op vestigingsplaats.
     *
     * @return list<array{company: Company, distance_km: float}>
     */
    public function resolveNearby(
        float $pickupLat,
        float $pickupLng,
        int $limit = self::MARKETPLACE_MAX_TENANTS,
        ?float $radiusKm = self::MARKETPLACE_RADIUS_KM,
        array $excludeCompanyIds = []
    ): array {
        $limit = max(1, $limit);
        $eligible = [];

        foreach ($this->rankedCandidates($pickupLat, $pickupLng, $excludeCompanyIds) as $candidate) {
            /** @var Company $company */
            $company = $candidate['company'];
            if ($this->billingAccess->isBookingBlocked($company)) {
                continue;
            }
            if (! $this->canReceiveMarketplaceRides($company)) {
                continue;
            }
            if (! $this->companyHasActiveVehicles($company)) {
                continue;
            }

            $eligible[] = $candidate;
        }

        if ($eligible === []) {
            return [];
        }

        if ($radiusKm !== null && $radiusKm > 0) {
            $nearby = array_values(array_filter(
                $eligible,
                static fn (array $candidate): bool => (float) $candidate['distance_km'] <= $radiusKm
            ));

            return array_slice($nearby, 0, $limit);
        }

        return array_slice($eligible, 0, $limit);
    }

    /**
     * Rangschik bedrijven op afstand van de dichtstbijzijnde online chauffeur tot de pickup.
     *
     * @return list<array{company: Company, distance_km: float}>
     */
    public function rankedCandidates(float $pickupLat, float $pickupLng, array $excludeCompanyIds = []): array
    {
        $exclude = array_values(array_filter(array_map('intval', $excludeCompanyIds)));
        $companies = $this->eligibleCompanies()->keyBy(fn (Company $c) => (int) $c->id);
        if ($companies->isEmpty()) {
            return [];
        }

        $companyIds = $companies->keys()
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => in_array($id, $exclude, true))
            ->values()
            ->all();
        if ($companyIds === []) {
            return [];
        }

        $bestDistanceByCompany = $this->nearestOnlineDriverDistanceByCompany($pickupLat, $pickupLng, $companyIds);
        if ($bestDistanceByCompany === []) {
            return [];
        }

        $ranked = [];
        foreach ($bestDistanceByCompany as $companyId => $distanceKm) {
            $company = $companies->get($companyId);
            if (! $company instanceof Company) {
                continue;
            }
            $ranked[] = [
                'company' => $company,
                'distance_km' => round((float) $distanceKm, 2),
            ];
        }

        usort($ranked, static fn (array $a, array $b) => $a['distance_km'] <=> $b['distance_km']);

        return $ranked;
    }

    /**
     * @param  list<int>  $companyIds
     * @return array<int, float> company_id => afstand in km van dichtstbijzijnde online chauffeur
     */
    public function nearestOnlineDriverDistanceByCompany(float $pickupLat, float $pickupLng, array $companyIds): array
    {
        $companyIds = array_values(array_filter(array_map('intval', $companyIds), static fn (int $id): bool => $id > 0));
        if ($companyIds === []) {
            return [];
        }

        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return [];
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return [];
        }

        $cutoff = now()->subSeconds(self::DRIVER_PRESENCE_MAX_AGE_SECONDS);
        $rows = DriverAvailability::on($conn)
            ->whereIn('company_id', $companyIds)
            ->where('is_online', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where(function ($query) use ($cutoff) {
                $query->where('last_seen_at', '>=', $cutoff)
                    ->orWhere('location_updated_at', '>=', $cutoff);
            })
            ->get(['company_id', 'lat', 'lng']);

        $best = [];
        foreach ($rows as $row) {
            $companyId = (int) ($row->company_id ?? 0);
            if ($companyId <= 0 || ! in_array($companyId, $companyIds, true)) {
                continue;
            }
            $lat = (float) $row->lat;
            $lng = (float) $row->lng;
            if (! $this->isValidCoord($lat, $lng)) {
                continue;
            }
            $distanceKm = GeoHelper::calculateDistance($pickupLat, $pickupLng, $lat, $lng);
            if (! isset($best[$companyId]) || $distanceKm < $best[$companyId]) {
                $best[$companyId] = $distanceKm;
            }
        }

        return $best;
    }

    /**
     * @return Collection<int, Company>
     */
    public function eligibleCompanies(): Collection
    {
        $query = Company::query()
            ->with(['modules', 'mainLocation'])
            ->where('is_active', true);

        if (Schema::hasColumn('companies', 'accepts_nexa_suite_bookings')) {
            $query->where('accepts_nexa_suite_bookings', true);
        }

        return $query->get()->filter(fn (Company $company) => $company->hasTaxiModule())->values();
    }

    /**
     * Vestigingscoördinaten (legacy / overige flows). Marketplace-matching gebruikt chauffeur-GPS.
     *
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

    /**
     * Eigen website-boeking óf fee-only marketplace (dispatch + chauffeur-app).
     */
    public function canReceiveMarketplaceRides(Company $company): bool
    {
        if ($this->entitlements->allows($company, \App\Support\TenantPackageCapability::WEBSITE_BOOKING)) {
            return true;
        }

        return $this->entitlements->allows($company, \App\Support\TenantPackageCapability::DISPATCH)
            && $this->entitlements->allows($company, \App\Support\TenantPackageCapability::DRIVER_APP);
    }

    private function isValidCoord(float $lat, float $lng): bool
    {
        return $lat >= -90.0 && $lat <= 90.0
            && $lng >= -180.0 && $lng <= 180.0
            && ! ($lat === 0.0 && $lng === 0.0);
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $n = (float) $value;
        if (! is_finite($n)) {
            return null;
        }

        return $n;
    }
}
