<?php

namespace App\Modules\NexaTaxi\Services;

use App\Helpers\GeoHelper;
use App\Models\Company;

class TaxiGpsLiveMapDemoService
{
    public const SESSION_KEY_PREFIX = 'gps_live_map_demo.';

    public const DEFAULT_LAT = 52.2215;

    public const DEFAULT_LNG = 6.8937;

    /** @var array<string, list<array{0: float, 1: float}>> */
    private array $pathCache = [];

    public function __construct(
        protected TaxiGpsRoadPathService $roads,
    ) {}

    public function sessionKey(int $companyId): string
    {
        return self::SESSION_KEY_PREFIX.$companyId;
    }

    public function isEnabled(int $companyId): bool
    {
        return (bool) session($this->sessionKey($companyId), false);
    }

    public function setEnabled(int $companyId, bool $enabled): void
    {
        session([$this->sessionKey($companyId) => $enabled]);
    }

    /**
     * Kaartmidden: bedrijfscoördinaten, anders de vestigingsstad, anders de kaart-defaults.
     *
     * @return array{0: float, 1: float}
     */
    public function centerForCompany(?Company $company, float $fallbackLat = 0.0, float $fallbackLng = 0.0): array
    {
        return GeoHelper::mapCenterForAddress(
            $company?->city,
            $company?->latitude,
            $company?->longitude,
            $fallbackLat,
            $fallbackLng
        );
    }

    /**
     * Haal weg-lussen alvast op, zodat de eerste poll niet op Google/OSRM wacht.
     */
    public function prefetchLoops(float $centerLat, float $centerLng, int $vehicleCount = 4): void
    {
        $n = max(1, min(24, $vehicleCount));
        if ($this->isAmsterdamCenter($centerLat, $centerLng)) {
            $drives = array_values(TaxiGpsDemoFleetService::streetDrives());
            foreach ($drives as $index => $drive) {
                if ($index >= $n) {
                    break;
                }
                $this->pathFor($drive['route']);
            }
            for ($i = count($drives); $i < $n; $i++) {
                $this->pathFor($this->loopWaypointsAround($centerLat, $centerLng, $i));
            }

            return;
        }

        for ($i = 0; $i < $n; $i++) {
            $this->pathFor($this->loopWaypointsAround($centerLat, $centerLng, $i));
        }
    }

    /**
     * Schuif de geconfigureerde vloot over weg-lussen rond de vestigingsstad.
     * Zonder voertuigen blijft er één demotaxi over.
     *
     * @param  list<array<string, mixed>>  $vehicles
     * @return list<array<string, mixed>>
     */
    public function apply(array $vehicles, float $centerLat, float $centerLng, ?float $now = null): array
    {
        $now = $now ?? microtime(true);
        if ($vehicles === []) {
            return [$this->syntheticVehicle($centerLat, $centerLng, $now)];
        }

        $out = [];
        foreach (array_values($vehicles) as $index => $vehicle) {
            $out[] = $this->driveVehicle(is_array($vehicle) ? $vehicle : [], $index, $centerLat, $centerLng, $now);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return array<string, mixed>
     */
    private function driveVehicle(array $vehicle, int $index, float $centerLat, float $centerLng, float $now): array
    {
        $drive = $this->driveFor($vehicle, $index, $centerLat, $centerLng);
        $path = $this->pathFor($drive['route']);
        $point = $this->roads->pointAndHeading($path, ($now * $drive['speed_mps']) + ($index * 220));
        $vehicle['lat'] = round($point[0], 6);
        $vehicle['lng'] = round($point[1], 6);
        $vehicle['heading'] = round($point[2], 1);
        $vehicle['demo'] = true;
        $vehicle['location_updated_at'] = now()->toIso8601String();
        $vehicle['last_seen_at'] = now()->toIso8601String();

        return $vehicle;
    }

    /**
     * @return array<string, mixed>
     */
    private function syntheticVehicle(float $lat, float $lng, float $now): array
    {
        $driven = $this->driveVehicle([
            'license_plate' => 'DEMO-1',
            'car_style' => 'sedan',
            'color' => '#ea580c',
        ], 0, $lat, $lng, $now);

        return array_merge([
            'id' => 'demo-live-1',
            'driver_id' => 0,
            'driver_name' => 'Demo chauffeur',
            'vehicle_id' => null,
            'vehicle_name' => 'Demo taxi',
            'is_online' => true,
        ], $driven);
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return array{speed_mps: float, route: list<array{0: float, 1: float}>}
     */
    private function driveFor(array $vehicle, int $index, float $centerLat, float $centerLng): array
    {
        $known = TaxiGpsDemoFleetService::streetDriveForPlate((string) ($vehicle['license_plate'] ?? ''));
        if ($known !== null && $this->isAmsterdamCenter($centerLat, $centerLng)) {
            return $known;
        }

        $drives = array_values(TaxiGpsDemoFleetService::streetDrives());
        if ($this->isAmsterdamCenter($centerLat, $centerLng) && isset($drives[$index])) {
            return [
                'speed_mps' => $drives[$index]['speed_mps'],
                'route' => $drives[$index]['route'],
            ];
        }

        return [
            'speed_mps' => 11.0 + ($index % 4),
            'route' => $this->loopWaypointsAround($centerLat, $centerLng, $index),
        ];
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    private function pathFor(array $waypoints): array
    {
        $key = md5((string) json_encode($waypoints));
        if (! isset($this->pathCache[$key])) {
            $this->pathCache[$key] = $this->roads->loopPoints(
                $waypoints,
                ! app()->runningUnitTests(),
                5.0
            );
        }

        return $this->pathCache[$key];
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    private function loopWaypointsAround(float $lat, float $lng, int $index): array
    {
        $patterns = [
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
            [
                [0.0024, -0.0048],
                [0.0044, -0.0016],
                [0.0030, 0.0030],
                [-0.0012, 0.0044],
                [-0.0040, 0.0008],
                [-0.0020, -0.0038],
            ],
        ];
        $pattern = $patterns[$index % count($patterns)];
        $scale = 1 + (intdiv($index, count($patterns)) * 0.22);
        $shift = $index % count($pattern);
        $rotated = array_merge(array_slice($pattern, $shift), array_slice($pattern, 0, $shift));
        $points = [];
        foreach ($rotated as $off) {
            $points[] = [$lat + ($off[0] * $scale), $lng + ($off[1] * $scale)];
        }

        return $points;
    }

    private function isAmsterdamCenter(float $lat, float $lng): bool
    {
        return abs($lat - 52.3728) < 0.08 && abs($lng - 4.8936) < 0.12;
    }
}
