<?php

namespace App\Modules\NexaTaxi\Services;

use App\Services\EnvService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TaxiGpsRoadPathService
{
    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    public function loopPoints(array $waypoints): array
    {
        $clean = [];
        foreach ($waypoints as $point) {
            if (! isset($point[0], $point[1]) || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                continue;
            }
            $clean[] = [(float) $point[0], (float) $point[1]];
        }
        if ($clean === []) {
            return [[52.3728, 4.8936]];
        }
        if (count($clean) === 1) {
            return $clean;
        }

        $cacheKey = 'gps_road_loop:'.md5(json_encode($clean));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $path = $this->fetchOsrmLoop($clean) ?? $this->fetchGoogleLoop($clean) ?? $this->fallbackDensify($clean);
        Cache::put($cacheKey, $path, now()->addDay());

        return $path;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $path
     * @return array{0: float, 1: float, 2: float}
     */
    public function pointAndHeading(array $path, float $distanceMeters): array
    {
        $count = count($path);
        if ($count === 0) {
            return [52.3728, 4.8936, 0.0];
        }
        if ($count === 1) {
            return [$path[0][0], $path[0][1], 0.0];
        }

        $segments = [];
        $total = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $from = $path[$i];
            $to = $path[($i + 1) % $count];
            $len = $this->haversineMeters($from[0], $from[1], $to[0], $to[1]);
            $segments[] = $len;
            $total += $len;
        }
        if ($total <= 0) {
            return [$path[0][0], $path[0][1], 0.0];
        }

        $travel = fmod(max(0, $distanceMeters), $total);
        for ($i = 0; $i < $count; $i++) {
            $len = $segments[$i];
            if ($travel <= $len || $i === $count - 1) {
                $t = $len > 0 ? min(1, $travel / $len) : 0;
                $from = $path[$i];
                $to = $path[($i + 1) % $count];
                $heading = $this->bearingDegrees($from[0], $from[1], $to[0], $to[1]);

                return [
                    $from[0] + ($to[0] - $from[0]) * $t,
                    $from[1] + ($to[1] - $from[1]) * $t,
                    $heading,
                ];
            }
            $travel -= $len;
        }

        return [$path[0][0], $path[0][1], 0.0];
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    public function decodePolyline(string $encoded, int $precision = 5): array
    {
        $index = 0;
        $lat = 0;
        $lng = 0;
        $length = strlen($encoded);
        $factor = 10 ** $precision;
        $points = [];

        while ($index < $length) {
            $lat += $this->decodePolylineChunk($encoded, $index, $length);
            $lng += $this->decodePolylineChunk($encoded, $index, $length);
            $points[] = [$lat / $factor, $lng / $factor];
        }

        return $points;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchOsrmLoop(array $waypoints): ?array
    {
        $coords = [];
        foreach ($waypoints as $point) {
            $coords[] = number_format($point[1], 6, '.', '').','.number_format($point[0], 6, '.', '');
        }
        $coords[] = $coords[0];

        try {
            $response = Http::timeout(10)->get(
                'https://router.project-osrm.org/route/v1/driving/'.implode(';', $coords),
                ['overview' => 'full', 'geometries' => 'polyline']
            );
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $geometry = $response->json('routes.0.geometry');
        if (! is_string($geometry) || $geometry === '') {
            return null;
        }

        $points = $this->decodePolyline($geometry);
        if (count($points) < 2) {
            return null;
        }

        return $this->dropNearDuplicates($points);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchGoogleLoop(array $waypoints): ?array
    {
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '' || count($waypoints) < 2) {
            return null;
        }

        $origin = $waypoints[0][0].','.$waypoints[0][1];
        $via = [];
        for ($i = 1; $i < count($waypoints); $i++) {
            $via[] = $waypoints[$i][0].','.$waypoints[$i][1];
        }

        try {
            $response = Http::timeout(12)->get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => $origin,
                'destination' => $origin,
                'waypoints' => implode('|', $via),
                'mode' => 'driving',
                'key' => $apiKey,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful() || ($response->json('status') !== 'OK')) {
            return null;
        }

        $encoded = $response->json('routes.0.overview_polyline.points');
        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $points = $this->decodePolyline($encoded);
        if (count($points) < 2) {
            return null;
        }

        return $this->dropNearDuplicates($points);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    private function fallbackDensify(array $waypoints): array
    {
        $out = [];
        $count = count($waypoints);
        for ($i = 0; $i < $count; $i++) {
            $from = $waypoints[$i];
            $to = $waypoints[($i + 1) % $count];
            $steps = 12;
            for ($s = 0; $s < $steps; $s++) {
                $t = $s / $steps;
                $out[] = [
                    $from[0] + ($to[0] - $from[0]) * $t,
                    $from[1] + ($to[1] - $from[1]) * $t,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    private function dropNearDuplicates(array $points): array
    {
        $out = [];
        $prev = null;
        foreach ($points as $point) {
            if ($prev !== null && $this->haversineMeters($prev[0], $prev[1], $point[0], $point[1]) < 2) {
                continue;
            }
            $out[] = $point;
            $prev = $point;
        }

        return $out === [] ? $points : $out;
    }

    private function decodePolylineChunk(string $encoded, int &$index, int $length): int
    {
        $result = 0;
        $shift = 0;
        do {
            if ($index >= $length) {
                return $result;
            }
            $b = ord($encoded[$index++]) - 63;
            $result |= ($b & 0x1F) << $shift;
            $shift += 5;
        } while ($b >= 0x20);

        return ($result & 1) ? ~($result >> 1) : ($result >> 1);
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    private function bearingDegrees(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $y = sin(deg2rad($lng2 - $lng1)) * cos(deg2rad($lat2));
        $x = cos(deg2rad($lat1)) * sin(deg2rad($lat2))
            - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lng2 - $lng1));
        $bearing = rad2deg(atan2($y, $x));

        return fmod($bearing + 360, 360);
    }
}
