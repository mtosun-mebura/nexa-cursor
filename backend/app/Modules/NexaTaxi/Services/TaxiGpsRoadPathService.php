<?php

namespace App\Modules\NexaTaxi\Services;

use App\Services\EnvService;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TaxiGpsRoadPathService
{
    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    public function loopPoints(array $waypoints, bool $allowRemoteFetch = true, float $maxMeters = 5.0): array
    {
        return $this->loopPointsMany([$waypoints], $allowRemoteFetch, $maxMeters)[0];
    }

    /**
     * @param  list<list<array{0: float, 1: float}>>  $waypointSets
     * @return list<list<array{0: float, 1: float}>>
     */
    public function loopPointsMany(array $waypointSets, bool $allowRemoteFetch = true, float $maxMeters = 5.0): array
    {
        $cleaned = [];
        foreach ($waypointSets as $index => $waypoints) {
            $clean = $this->cleanWaypoints(is_array($waypoints) ? $waypoints : []);
            if ($clean === []) {
                $cleaned[$index] = [[52.3728, 4.8936]];
            } elseif (count($clean) === 1) {
                $cleaned[$index] = $clean;
            } else {
                $cleaned[$index] = $clean;
            }
        }

        $out = [];
        $pending = [];
        foreach ($cleaned as $index => $clean) {
            if (count($clean) < 2) {
                $out[$index] = $clean;
                continue;
            }
            $cached = Cache::get($this->cacheKey($clean, $maxMeters));
            if (is_array($cached) && count($cached) >= 2) {
                $out[$index] = $cached;
                continue;
            }
            if (! $allowRemoteFetch) {
                $out[$index] = $this->fallbackDensify($clean, $maxMeters);
                continue;
            }
            $pending[$index] = $clean;
        }

        if ($pending !== []) {
            $fetchedGoogle = $this->fetchGoogleLoopsParallel($pending);
            $needOsrm = [];
            foreach ($pending as $index => $clean) {
                if (isset($fetchedGoogle[$index])) {
                    $path = $this->densifyAlongPath($fetchedGoogle[$index], $maxMeters);
                    Cache::put($this->cacheKey($clean, $maxMeters), $path, now()->addDay());
                    $out[$index] = $path;
                } else {
                    $needOsrm[$index] = $clean;
                }
            }
            if ($needOsrm !== []) {
                $fetchedOsrm = $this->fetchOsrmLoopsParallel($needOsrm);
                foreach ($needOsrm as $index => $clean) {
                    $path = $fetchedOsrm[$index] ?? null;
                    if (is_array($path) && count($path) >= 2) {
                        $path = $this->densifyAlongPath($path, $maxMeters);
                        Cache::put($this->cacheKey($clean, $maxMeters), $path, now()->addDay());
                        $out[$index] = $path;
                    } else {
                        $out[$index] = $this->fallbackDensify($clean, $maxMeters);
                    }
                }
            }
        }

        ksort($out);

        return array_values($out);
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
     * @return list<array{0: float, 1: float}>
     */
    private function cleanWaypoints(array $waypoints): array
    {
        $clean = [];
        foreach ($waypoints as $point) {
            if (! isset($point[0], $point[1]) || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                continue;
            }
            $clean[] = [(float) $point[0], (float) $point[1]];
        }

        return $clean;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     */
    private function cacheKey(array $waypoints, float $maxMeters = 5.0): string
    {
        return 'gps_road_snap:v5:'.number_format($maxMeters, 1, '.', '').':'.md5((string) json_encode($waypoints));
    }

    /**
     * @param  array<int, list<array{0: float, 1: float}>>  $pending
     * @return array<int, list<array{0: float, 1: float}>>
     */
    private function fetchOsrmLoopsParallel(array $pending): array
    {
        $urls = [];
        foreach ($pending as $index => $waypoints) {
            $urls[$index] = $this->osrmLoopUrl($waypoints);
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($urls) {
                foreach ($urls as $index => $url) {
                    $pool->as((string) $index)
                        ->timeout(5)
                        ->connectTimeout(2)
                        ->get($url, [
                            'overview' => 'full',
                            'geometries' => 'polyline',
                        ]);
                }
            });
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($pending as $index => $waypoints) {
            $response = $responses[(string) $index] ?? null;
            $path = $this->pathFromOsrmResponse($response);
            if ($path !== null) {
                $out[$index] = $path;
            }
        }

        return $out;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     */
    private function osrmLoopUrl(array $waypoints): string
    {
        $coords = [];
        foreach ($waypoints as $point) {
            $coords[] = number_format($point[1], 6, '.', '').','.number_format($point[0], 6, '.', '');
        }
        $coords[] = $coords[0];

        return 'https://router.project-osrm.org/route/v1/driving/'.implode(';', $coords);
    }

    /**
     * @return list<array{0: float, 1: float}>|null
     */
    private function pathFromOsrmResponse(mixed $response): ?array
    {
        if (! is_object($response) || ! method_exists($response, 'successful') || ! $response->successful()) {
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
    private function fetchOsrmLoop(array $waypoints): ?array
    {
        $fetched = $this->fetchOsrmLoopsParallel([0 => $waypoints]);

        return $fetched[0] ?? null;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchGoogleLoop(array $waypoints): ?array
    {
        $fetched = $this->fetchGoogleLoopsParallel([0 => $waypoints]);

        return $fetched[0] ?? null;
    }

    /**
     * @param  array<int, list<array{0: float, 1: float}>>  $pending
     * @return array<int, list<array{0: float, 1: float}>>
     */
    private function fetchGoogleLoopsParallel(array $pending): array
    {
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '') {
            return [];
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($pending, $apiKey) {
                foreach ($pending as $index => $waypoints) {
                    $pool->as((string) $index)
                        ->timeout(5)
                        ->connectTimeout(2)
                        ->withHeaders([
                            'X-Goog-Api-Key' => $apiKey,
                            'X-Goog-FieldMask' => 'routes.polyline.encodedPolyline',
                        ])
                        ->post('https://routes.googleapis.com/directions/v2:computeRoutes', $this->googleRoutesBody($waypoints));
                }
            });
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($pending as $index => $waypoints) {
            $path = $this->pathFromGoogleRoutesResponse($responses[(string) $index] ?? null);
            if ($path !== null) {
                $out[$index] = $path;
            }
        }

        return $out;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return array<string, mixed>
     */
    private function googleRoutesBody(array $waypoints): array
    {
        $origin = [
            'location' => [
                'latLng' => [
                    'latitude' => $waypoints[0][0],
                    'longitude' => $waypoints[0][1],
                ],
            ],
        ];
        $intermediates = [];
        for ($i = 1; $i < count($waypoints); $i++) {
            $intermediates[] = [
                'location' => [
                    'latLng' => [
                        'latitude' => $waypoints[$i][0],
                        'longitude' => $waypoints[$i][1],
                    ],
                ],
            ];
        }

        return [
            'origin' => $origin,
            'destination' => $origin,
            'intermediates' => $intermediates,
            'travelMode' => 'DRIVE',
            'polylineQuality' => 'HIGH_QUALITY',
        ];
    }

    /**
     * @return list<array{0: float, 1: float}>|null
     */
    private function pathFromGoogleRoutesResponse(mixed $response): ?array
    {
        if (! is_object($response) || ! method_exists($response, 'successful') || ! $response->successful()) {
            return null;
        }

        $encoded = $response->json('routes.0.polyline.encodedPolyline');
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
     * Extra punten op de al gesnapte weg, zodat de auto de bocht volgt i.p.v. af te snijden.
     *
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    private function densifyAlongPath(array $points, float $maxMeters): array
    {
        $count = count($points);
        if ($count < 2 || $maxMeters <= 0) {
            return $points;
        }

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $from = $points[$i];
            $to = $points[($i + 1) % $count];
            $out[] = $from;
            $len = $this->haversineMeters($from[0], $from[1], $to[0], $to[1]);
            if ($len <= $maxMeters) {
                continue;
            }
            $steps = (int) floor($len / $maxMeters);
            for ($s = 1; $s <= $steps; $s++) {
                $t = $s / ($steps + 1);
                $out[] = [
                    $from[0] + ($to[0] - $from[0]) * $t,
                    $from[1] + ($to[1] - $from[1]) * $t,
                ];
            }
        }

        return $this->dropNearDuplicates($out);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    private function fallbackDensify(array $waypoints, float $maxMeters = 12.0): array
    {
        return $this->densifyAlongPath($waypoints, max(8.0, $maxMeters));
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
