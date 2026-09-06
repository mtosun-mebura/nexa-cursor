<?php

namespace App\Services;

use App\Helpers\GeoHelper;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Support\Facades\Schema;

class NearbyAvailableTaxiFleetService
{
    public const MAX_AGE_SECONDS = 180;

    public const DEFAULT_RADIUS_KM = 40.0;

    public const MAX_RESULTS = 60;

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected NearestTaxiTenantResolver $tenants,
    ) {}

    /**
     * Online taxi's van NEXA Suite-tenants die géén actieve rit hebben.
     *
     * @return list<array{id: string, lat: float, lng: float, car_style: string, distance_km: ?float}>
     */
    public function vehicles(?float $lat, ?float $lng, ?float $radiusKm = null): array
    {
        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return [];
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return [];
        }

        $companyIds = $this->tenants->eligibleCompanies()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();
        if ($companyIds === []) {
            return [];
        }

        TaxiDispatchSchema::ensureVehicleIdColumn($conn);

        $cutoff = now()->subSeconds(self::MAX_AGE_SECONDS);
        $rows = DriverAvailability::on($conn)
            ->whereIn('company_id', $companyIds)
            ->where('is_online', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where(function ($query) use ($cutoff) {
                $query->where('location_updated_at', '>=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('location_updated_at')
                            ->where('last_seen_at', '>=', $cutoff);
                    });
            })
            ->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $busyDriverIds = $this->busyDriverIds($conn, $companyIds);
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

        $radius = $radiusKm !== null && $radiusKm > 0 ? $radiusKm : self::DEFAULT_RADIUS_KM;
        $hasOrigin = $lat !== null && $lng !== null;
        $out = [];
        foreach ($rows as $row) {
            $driverId = (int) $row->driver_id;
            if ($driverId <= 0 || isset($busyDriverIds[$driverId])) {
                continue;
            }
            $taxiLat = (float) $row->lat;
            $taxiLng = (float) $row->lng;
            if (! $this->isValidCoord($taxiLat, $taxiLng)) {
                continue;
            }
            $distanceKm = null;
            if ($hasOrigin) {
                $distanceKm = round(GeoHelper::calculateDistance($lat, $lng, $taxiLat, $taxiLng), 2);
                if ($distanceKm > $radius) {
                    continue;
                }
            }
            $vehicleId = $hasVehicleColumn && $row->vehicle_id ? (int) $row->vehicle_id : 0;
            $vehicle = $vehicleId > 0 ? $vehicles->get($vehicleId) : null;
            $out[] = [
                'id' => $this->publicId($driverId),
                'lat' => $taxiLat,
                'lng' => $taxiLng,
                'car_style' => TaxiGpsTrackingSettingsService::styleFromVehicleType($vehicle?->type ?? null),
                'distance_km' => $distanceKm,
            ];
        }

        usort($out, static function (array $a, array $b) {
            $da = $a['distance_km'];
            $db = $b['distance_km'];
            if ($da === null && $db === null) {
                return 0;
            }
            if ($da === null) {
                return 1;
            }
            if ($db === null) {
                return -1;
            }

            return $da <=> $db;
        });

        return array_slice($out, 0, self::MAX_RESULTS);
    }

    /**
     * @param  list<int>  $companyIds
     * @return array<int, true>
     */
    private function busyDriverIds(string $connection, array $companyIds): array
    {
        if ($companyIds === [] || ! Schema::connection($connection)->hasTable('ride_requests')) {
            return [];
        }

        $ids = RideRequest::on($connection)
            ->whereIn('company_id', $companyIds)
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

    private function publicId(int $driverId): string
    {
        return substr(hash_hmac('sha256', 'taxi-driver-'.$driverId, (string) config('app.key')), 0, 16);
    }

    private function isValidCoord(float $lat, float $lng): bool
    {
        return abs($lat) <= 90 && abs($lng) <= 180 && ! ($lat == 0.0 && $lng == 0.0);
    }
}
