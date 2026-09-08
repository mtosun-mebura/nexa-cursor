<?php

namespace App\Services;

use App\Helpers\GeoHelper;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiGpsRoadPathService;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Support\TenantPackageCapability;
use Illuminate\Support\Facades\Schema;

class TenantBookingLiveFleetService
{
    public const DEFAULT_LAT = 52.2215;

    public const DEFAULT_LNG = 6.8937;

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected CompanyEntitlementService $entitlements,
        protected NearbyAvailableTaxiFleetService $marketplaceFleet,
        protected TaxiGpsRoadPathService $roads,
    ) {}

    /**
     * @param  array<string, mixed>  $sectionConfig
     * @return list<array<string, mixed>>
     */
    public function vehiclesForSection(
        string $sectionKey,
        array $sectionConfig,
        ?Company $company,
        ?float $lat,
        ?float $lng
    ): array {
        if ($this->isMarketplaceSection($sectionKey)) {
            return $this->marketplaceFleet->vehicles($lat, $lng);
        }

        if (! $this->isTenantBookingSection($sectionKey)) {
            return [];
        }

        $logic = is_array($sectionConfig['logic'] ?? null) ? $sectionConfig['logic'] : [];
        $showLive = $this->showsLiveFleet($logic, $company);
        $showDemo = $this->showsDemoFleet($logic);
        if (! $showLive && ! $showDemo) {
            return [];
        }

        $color = $this->fleetCarColor($logic);
        $out = [];
        if ($showLive && $company) {
            $out = $this->tenantVehicles((int) $company->id, $lat, $lng, $color);
        }
        if ($showDemo) {
            try {
                $out = array_merge($out, $this->demoVehicles($company, $lat, $lng, $color));
            } catch (\Throwable) {
                // Demo mag echte voertuigen nooit blokkeren.
            }
        }

        return $out;
    }

    public function shouldShowOnBookingMap(string $sectionKey, array $sectionConfig, ?Company $company): bool
    {
        if ($this->isMarketplaceSection($sectionKey)) {
            return true;
        }
        if (! $this->isTenantBookingSection($sectionKey)) {
            return false;
        }
        $logic = is_array($sectionConfig['logic'] ?? null) ? $sectionConfig['logic'] : [];

        return $this->showsLiveFleet($logic, $company) || $this->showsDemoFleet($logic);
    }

    public function showsOccupancyStatus(string $sectionKey, array $sectionConfig, ?Company $company): bool
    {
        return ! $this->isMarketplaceSection($sectionKey)
            && $this->shouldShowOnBookingMap($sectionKey, $sectionConfig, $company);
    }

    public function isMarketplaceSection(string $sectionKey): bool
    {
        return str_contains(strtolower($sectionKey), 'algemene_boekingsmodule');
    }

    public function isTenantBookingSection(string $sectionKey): bool
    {
        $key = strtolower($sectionKey);

        return str_contains($key, 'boekingsmodule_v2') || str_contains($key, 'taxi.boekingsmodule');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tenantVehicles(int $companyId, ?float $originLat, ?float $originLng, string $color = '#ea580c'): array
    {
        if ($companyId <= 0) {
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

        TaxiDispatchSchema::ensureVehicleIdColumn($conn);
        $rows = DriverAvailability::on($conn)
            ->where('company_id', $companyId)
            ->where('is_online', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $busyDriverIds = $this->busyDriverIds($conn, $companyId);
        $hasVehicleColumn = Schema::connection($conn)->hasColumn('driver_availability', 'vehicle_id');
        $vehicleIds = [];
        foreach ($rows as $row) {
            if ($hasVehicleColumn && $row->vehicle_id) {
                $vehicleIds[] = (int) $row->vehicle_id;
            }
        }
        $vehicles = $vehicleIds === []
            ? collect()
            : Vehicle::on($conn)->whereIn('id', array_values(array_unique($vehicleIds)))->get()->keyBy('id');

        $hasOrigin = $originLat !== null && $originLng !== null;
        $out = [];
        foreach ($rows as $row) {
            $driverId = (int) $row->driver_id;
            $taxiLat = (float) $row->lat;
            $taxiLng = (float) $row->lng;
            if ($driverId <= 0 || ! $this->isValidCoord($taxiLat, $taxiLng)) {
                continue;
            }
            $distanceKm = null;
            if ($hasOrigin) {
                $distanceKm = round(GeoHelper::calculateDistance($originLat, $originLng, $taxiLat, $taxiLng), 2);
            }
            $vehicleId = $hasVehicleColumn && $row->vehicle_id ? (int) $row->vehicle_id : 0;
            $vehicle = $vehicleId > 0 ? $vehicles->get($vehicleId) : null;
            $occupied = isset($busyDriverIds[$driverId]);
            $out[] = $this->publicVehicle(
                'tenant-'.$driverId,
                $taxiLat,
                $taxiLng,
                TaxiGpsTrackingSettingsService::styleFromVehicleType($vehicle?->type ?? null),
                $occupied,
                $vehicle ? trim((string) ($vehicle->name ?? '')) : '',
                $distanceKm,
                $color
            );
        }

        return $out;
    }

    /**
     * Tijdelijke demo-vloot: drie taxi’s die over echte straten rond het bedrijf rijden (1 bezet / 2 vrij).
     *
     * @return list<array<string, mixed>>
     */
    public function demoVehicles(?Company $company, ?float $originLat, ?float $originLng, string $color = '#ea580c'): array
    {
        [$centerLat, $centerLng] = $this->companyMapCenter($company, $originLat, $originLng);
        $now = (float) now()->timestamp;
        $defs = [
            ['name' => 'Demo taxi 1', 'style' => 'sedan', 'occupied' => false, 'speed' => 11.0, 'phase' => 0],
            ['name' => 'Demo taxi 2', 'style' => 'van', 'occupied' => true, 'speed' => 13.0, 'phase' => 90],
            ['name' => 'Demo taxi 3', 'style' => 'sedan', 'occupied' => false, 'speed' => 10.0, 'phase' => 180],
        ];
        $paths = $this->roads->loopPointsMany(array_map(
            fn (int $index): array => $this->demoRouteWaypoints($centerLat, $centerLng, $index),
            array_keys($defs)
        ));

        $out = [];
        foreach ($defs as $index => $def) {
            $path = $paths[$index] ?? [[$centerLat, $centerLng]];
            $distance = ($now * $def['speed']) + ($def['phase'] * $def['speed']);
            $point = $this->roads->pointAndHeading($path, $distance);
            $lat = $point[0];
            $lng = $point[1];
            $distanceKm = ($originLat !== null && $originLng !== null)
                ? round(GeoHelper::calculateDistance($originLat, $originLng, $lat, $lng), 2)
                : null;
            $out[] = $this->publicVehicle(
                'demo-tosun-'.($index + 1),
                $lat,
                $lng,
                $def['style'],
                $def['occupied'],
                $def['name'],
                $distanceKm,
                $color,
                $point[2]
            );
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function publicVehicle(
        string $id,
        float $lat,
        float $lng,
        string $carStyle,
        bool $occupied,
        string $name,
        ?float $distanceKm,
        string $color = '#ea580c',
        ?float $heading = null
    ): array {
        $payload = [
            'id' => $id,
            'lat' => $lat,
            'lng' => $lng,
            'car_style' => $carStyle,
            'status' => $occupied ? 'occupied' : 'free',
            'status_label' => $occupied ? 'Bezet' : 'Vrij',
            'name' => $name,
            'distance_km' => $distanceKm,
            'color' => $color,
            'demo' => str_starts_with($id, 'demo-'),
        ];
        if ($heading !== null) {
            $payload['heading'] = round($heading, 1);
        }

        return $payload;
    }

    /**
     * Luspunten rond het bedrijf; OSRM/Google legt ze op echte wegen.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function demoRouteWaypoints(float $lat, float $lng, int $index): array
    {
        $loops = [
            [
                [0.0036, 0.0008],
                [0.0020, 0.0046],
                [-0.0014, 0.0050],
                [-0.0038, 0.0014],
                [-0.0022, -0.0036],
                [0.0016, -0.0044],
            ],
            [
                [0.0014, 0.0054],
                [-0.0020, 0.0040],
                [-0.0044, 0.0004],
                [-0.0016, -0.0042],
                [0.0026, -0.0030],
                [0.0042, 0.0016],
            ],
            [
                [0.0048, -0.0010],
                [0.0032, 0.0034],
                [-0.0008, 0.0046],
                [-0.0042, 0.0018],
                [-0.0028, -0.0034],
                [0.0010, -0.0048],
            ],
        ];
        $offsets = $loops[$index] ?? $loops[0];
        $points = [];
        foreach ($offsets as $offset) {
            $points[] = [$lat + $offset[0], $lng + $offset[1]];
        }

        return $points;
    }

    /**
     * @param  array<string, mixed>  $logic
     */
    private function showsLiveFleet(array $logic, ?Company $company): bool
    {
        return ! empty($logic['show_live_fleet'])
            && $this->entitlements->allows($company, TenantPackageCapability::GPS_TRACKING);
    }

    /**
     * Demo-vloot is alleen zichtbaar voor de ingelogde super-admin, nooit voor bezoekers.
     *
     * @param  array<string, mixed>  $logic
     */
    private function showsDemoFleet(array $logic): bool
    {
        if (empty($logic['show_live_fleet_demo'])) {
            return false;
        }

        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    /**
     * @param  array<string, mixed>  $logic
     */
    private function fleetCarColor(array $logic): string
    {
        $raw = trim((string) ($logic['live_fleet_car_color'] ?? ''));
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $raw)) {
            if (strlen($raw) === 4) {
                return '#'.$raw[1].$raw[1].$raw[2].$raw[2].$raw[3].$raw[3];
            }

            return $raw;
        }

        return '#ea580c';
    }

    /**
     * @return array<int, true>
     */
    private function busyDriverIds(string $connection, int $companyId): array
    {
        if (! Schema::connection($connection)->hasTable('ride_requests')) {
            return [];
        }

        $ids = RideRequest::on($connection)
            ->where('company_id', $companyId)
            ->whereNotNull('driver_id')
            ->whereIn('status', [
                RideRequest::STATUS_OFFERED,
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_ASSIGNED,
            ])
            ->pluck('driver_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return array_fill_keys($ids, true);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function companyMapCenter(?Company $company, ?float $originLat, ?float $originLng): array
    {
        return GeoHelper::mapCenterForAddress(
            $company?->city,
            $company?->latitude,
            $company?->longitude,
            $originLat ?? self::DEFAULT_LAT,
            $originLng ?? self::DEFAULT_LNG
        );
    }

    private function isValidCoord(float $lat, float $lng): bool
    {
        return abs($lat) <= 90 && abs($lng) <= 180 && ! ($lat == 0.0 && $lng == 0.0);
    }
}
