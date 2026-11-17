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
            $route = $this->isTwenteCenter($centerLat, $centerLng)
                ? $this->twenteCityLoop($i)
                : $this->loopWaypointsAround($centerLat, $centerLng, $i);
            $this->pathFor($route);
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
        $path = $this->thinPathForClient($this->pathFor($drive['route']), 28.0);
        $distance = ($now * $drive['speed_mps']) + ($index * 220);
        $point = $this->roads->pointAndHeading($path, $distance);
        $vehicle['lat'] = round($point[0], 6);
        $vehicle['lng'] = round($point[1], 6);
        $vehicle['heading'] = round($point[2], 1);
        $vehicle['demo'] = true;
        // Zelfde polyline voor server-positie én client-rAF (OSRM/Google-weggeometrie).
        $vehicle['demo_path'] = $this->roads->encodePolyline($path);
        $vehicle['demo_speed_mps'] = round((float) $drive['speed_mps'], 2);
        $vehicle['demo_distance_m'] = round($distance, 1);
        $vehicle['location_updated_at'] = now()->toIso8601String();
        $vehicle['last_seen_at'] = now()->toIso8601String();

        return $vehicle;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $path
     * @return list<array{0: float, 1: float}>
     */
    private function thinPathForClient(array $path, float $minMeters = 28.0): array
    {
        $count = count($path);
        if ($count <= 2) {
            return $path;
        }
        $out = [$path[0]];
        $prev = $path[0];
        for ($i = 1; $i < $count - 1; $i++) {
            $point = $path[$i];
            if (GeoHelper::calculateDistance($prev[0], $prev[1], $point[0], $point[1]) * 1000 < $minMeters) {
                continue;
            }
            $out[] = $point;
            $prev = $point;
        }
        $last = $path[$count - 1];
        if ($out[count($out) - 1] !== $last) {
            $out[] = $last;
        }

        return $out;
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
            'driver_name' => __('Demo chauffeur'),
            'vehicle_id' => null,
            'vehicle_name' => __('Demo taxi'),
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

        if ($this->isTwenteCenter($centerLat, $centerLng)) {
            return [
                'speed_mps' => 18.0 + ($index % 5),
                'route' => $this->twenteCityLoop($index),
            ];
        }

        return [
            'speed_mps' => 14.0 + ($index % 4),
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
                8.0
            );
        }

        return $this->pathCache[$key];
    }

    /**
     * Grotere stadsritten (~2–4 km) zodat beweging op de live-kaart zichtbaar is.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function loopWaypointsAround(float $lat, float $lng, int $index): array
    {
        $patterns = [
            [
                [0.018, 0.004],
                [0.010, 0.022],
                [-0.008, 0.024],
                [-0.020, 0.006],
                [-0.012, -0.018],
                [0.008, -0.020],
            ],
            [
                [0.008, 0.026],
                [-0.012, 0.018],
                [-0.022, 0.002],
                [-0.008, -0.020],
                [0.014, -0.014],
                [0.020, 0.008],
            ],
            [
                [0.024, -0.006],
                [0.016, 0.016],
                [-0.004, 0.022],
                [-0.020, 0.010],
                [-0.014, -0.016],
                [0.006, -0.022],
            ],
            [
                [0.012, -0.022],
                [0.022, -0.008],
                [0.014, 0.014],
                [-0.006, 0.020],
                [-0.020, 0.004],
                [-0.010, -0.018],
            ],
        ];
        $pattern = $patterns[$index % count($patterns)];
        $scale = 1 + (intdiv($index, count($patterns)) * 0.28);
        $shift = $index % count($pattern);
        $rotated = array_merge(array_slice($pattern, $shift), array_slice($pattern, 0, $shift));
        $points = [];
        foreach ($rotated as $off) {
            $points[] = [$lat + ($off[0] * $scale), $lng + ($off[1] * $scale)];
        }

        return $points;
    }

    /**
     * Lus langs Twente-steden (Enschede, Hengelo, Almelo, …).
     *
     * @return list<array{0: float, 1: float}>
     */
    private function twenteCityLoop(int $index): array
    {
        $hubs = [
            [52.2215, 6.8936], // Enschede
            [52.2658, 6.7931], // Hengelo
            [52.3014, 6.7550], // Borne
            [52.3570, 6.6626], // Almelo
            [52.3134, 6.9292], // Oldenzaal
            [52.1567, 6.7389], // Haaksbergen
        ];
        $shift = $index % count($hubs);
        $rotated = array_merge(array_slice($hubs, $shift), array_slice($hubs, 0, $shift));
        // Neem 4 hubs per voertuig zodat de rit over meerdere steden gaat.
        $take = array_slice($rotated, 0, 4);

        return array_map(
            static fn (array $hub): array => [(float) $hub[0], (float) $hub[1]],
            $take
        );
    }

    private function isAmsterdamCenter(float $lat, float $lng): bool
    {
        return abs($lat - 52.3728) < 0.08 && abs($lng - 4.8936) < 0.12;
    }

    private function isTwenteCenter(float $lat, float $lng): bool
    {
        return $lat > 52.10 && $lat < 52.42 && $lng > 6.55 && $lng < 7.10;
    }
}
