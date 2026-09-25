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
     * @param  list<array{0: float, 1: float}>  $points
     */
    public function encodePolyline(array $points, int $precision = 5): string
    {
        $factor = 10 ** $precision;
        $output = '';
        $prevLat = 0;
        $prevLng = 0;
        foreach ($points as $point) {
            $lat = (int) round($point[0] * $factor);
            $lng = (int) round($point[1] * $factor);
            $output .= $this->encodePolylineSigned($lat - $prevLat);
            $output .= $this->encodePolylineSigned($lng - $prevLng);
            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $output;
    }

    /**
     * Snap GPS-broodkruimels naar de echte wegen (open rit, geen lus).
     *
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    public function snapDrivenPath(array $waypoints, bool $allowRemoteFetch = true): array
    {
        $clean = $this->cleanWaypoints($waypoints);
        if (count($clean) < 2) {
            return $clean;
        }

        $cacheKey = $this->drivenCacheKey($clean);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && count($cached) >= 2 && ! $this->pathLooksStraight($cached)) {
            return $cached;
        }

        $path = null;
        if ($allowRemoteFetch) {
            $sparse = count($clean) < 8 || $this->pathLooksStraight($clean);
            if ($sparse) {
                $path = $this->fetchGoogleDirections($clean)
                    ?? $this->fetchGoogleOpenRoute($clean)
                    ?? $this->fetchOsrmOpenRoute($clean);
            } else {
                $path = $this->fetchGoogleSnapToRoads($clean)
                    ?? $this->fetchOsrmMatch($clean)
                    ?? $this->fetchGoogleDirections($clean)
                    ?? $this->fetchGoogleOpenRoute($clean)
                    ?? $this->fetchOsrmOpenRoute($clean);
            }
        }

        if (! is_array($path) || count($path) < 2 || $this->pathLooksStraight($path)) {
            return $clean;
        }

        Cache::put($cacheKey, $path, now()->addDays(7));

        return $path;
    }

    /**
     * Zet live voertuigposities op de dichtstbijzijnde weg, maar alleen als die weg dichtbij is.
     *
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    public function snapLivePositions(array $points, bool $allowRemoteFetch = true, float $maxOffsetMeters = 28.0): array
    {
        $out = [];
        $pending = [];
        foreach ($points as $index => $point) {
            if (! isset($point[0], $point[1]) || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                $out[$index] = $point;
                continue;
            }
            $lat = (float) $point[0];
            $lng = (float) $point[1];
            $out[$index] = [$lat, $lng];
            $cached = Cache::get($this->liveSnapCacheKey($lat, $lng));
            if (is_array($cached) && isset($cached[0], $cached[1])) {
                $out[$index] = [(float) $cached[0], (float) $cached[1]];
                continue;
            }
            $pending[$index] = [$lat, $lng];
        }

        if ($pending === [] || ! $allowRemoteFetch) {
            ksort($out);

            return array_values($out);
        }

        $lookup = $this->fetchGoogleNearestRoads(array_values($pending));
        $slot = 0;
        foreach ($pending as $originalIndex => $original) {
            $candidate = is_array($lookup) ? ($lookup[$slot] ?? null) : null;
            $resolved = $original;
            if (is_array($candidate) && isset($candidate[0], $candidate[1])) {
                $offset = $this->haversineMeters($original[0], $original[1], (float) $candidate[0], (float) $candidate[1]);
                if ($offset <= $maxOffsetMeters) {
                    $resolved = [(float) $candidate[0], (float) $candidate[1]];
                }
            }
            Cache::put($this->liveSnapCacheKey($original[0], $original[1]), $resolved, now()->addSeconds(12));
            $out[$originalIndex] = $resolved;
            $slot++;
        }

        ksort($out);

        return array_values($out);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     * @return array<int, array{0: float, 1: float}>|null
     */
    private function fetchGoogleNearestRoads(array $points): ?array
    {
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '' || $points === []) {
            return null;
        }

        $path = [];
        foreach (array_slice($points, 0, 100) as $point) {
            $path[] = number_format($point[0], 6, '.', '').','.number_format($point[1], 6, '.', '');
        }

        try {
            $response = Http::timeout(1.2)->connectTimeout(0.6)->get('https://roads.googleapis.com/v1/nearestRoads', [
                'points' => implode('|', $path),
                'key' => $apiKey,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $raw = $response->json('snappedPoints');
        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $lookup = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $lat = $item['location']['latitude'] ?? null;
            $lng = $item['location']['longitude'] ?? null;
            $originalIndex = $item['originalIndex'] ?? null;
            if (! is_numeric($lat) || ! is_numeric($lng) || ! is_numeric($originalIndex)) {
                continue;
            }
            $lookup[(int) $originalIndex] = [(float) $lat, (float) $lng];
        }

        return $lookup === [] ? null : $lookup;
    }

    /**
     * Cache-sleutel voor een live GPS-punt, afgerond tot ~1 meter.
     */
    private function liveSnapCacheKey(float $lat, float $lng): string
    {
        return 'gps_live_snap:v1:'.number_format($lat, 5, '.', '').':'.number_format($lng, 5, '.', '');
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     */
    public function pathLooksStraight(array $points): bool
    {
        $count = count($points);
        if ($count < 2) {
            return true;
        }
        if ($count === 2) {
            return true;
        }

        $origin = $points[0];
        $destination = $points[$count - 1];
        $maxDeviation = 0.0;
        for ($i = 1; $i < $count - 1; $i++) {
            $maxDeviation = max(
                $maxDeviation,
                $this->distanceToSegmentMeters($points[$i], $origin, $destination)
            );
        }

        return $maxDeviation < 15.0;
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
     * @param  list<array{0: float, 1: float}>  $waypoints
     */
    private function drivenCacheKey(array $waypoints): string
    {
        return 'gps_driven_snap:v2:'.md5((string) json_encode($waypoints));
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchGoogleSnapToRoads(array $waypoints): ?array
    {
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '') {
            return null;
        }

        $chunks = array_chunk($this->limitWaypoints($waypoints, 400), 100);
        if ($chunks === []) {
            return null;
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($chunks, $apiKey) {
                foreach ($chunks as $index => $chunk) {
                    $path = [];
                    foreach ($chunk as $point) {
                        $path[] = number_format($point[0], 6, '.', '').','.number_format($point[1], 6, '.', '');
                    }
                    $pool->as((string) $index)
                        ->timeout(6)
                        ->connectTimeout(2)
                        ->get('https://roads.googleapis.com/v1/snapToRoads', [
                            'path' => implode('|', $path),
                            'interpolate' => 'true',
                            'key' => $apiKey,
                        ]);
                }
            });
        } catch (\Throwable) {
            return null;
        }

        $out = [];
        foreach ($chunks as $index => $chunk) {
            $snapped = $this->pathFromGoogleRoadsResponse($responses[(string) $index] ?? null);
            if ($snapped === null) {
                return null;
            }
            foreach ($snapped as $point) {
                $out[] = $point;
            }
        }

        $out = $this->dropNearDuplicates($out);

        return count($out) >= 2 ? $out : null;
    }

    /**
     * @return list<array{0: float, 1: float}>|null
     */
    private function pathFromGoogleRoadsResponse(mixed $response): ?array
    {
        if (! is_object($response) || ! method_exists($response, 'successful') || ! $response->successful()) {
            return null;
        }

        $raw = $response->json('snappedPoints');
        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $points = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $lat = $item['location']['latitude'] ?? null;
            $lng = $item['location']['longitude'] ?? null;
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                continue;
            }
            $points[] = [(float) $lat, (float) $lng];
        }

        return count($points) >= 2 ? $points : null;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchOsrmMatch(array $waypoints): ?array
    {
        $sampled = $this->limitWaypoints($waypoints, 100);
        try {
            $response = Http::timeout(6)->connectTimeout(2)->get($this->osrmMatchUrl($sampled), [
                'overview' => 'full',
                'geometries' => 'polyline',
                'tidy' => 'true',
            ]);
        } catch (\Throwable) {
            return null;
        }

        $geometry = $response->successful() ? $response->json('matchings.0.geometry') : null;
        if (! is_string($geometry) || $geometry === '') {
            return null;
        }
        $points = $this->dropNearDuplicates($this->decodePolyline($geometry));

        return count($points) >= 2 ? $points : null;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     */
    private function osrmMatchUrl(array $waypoints): string
    {
        $coords = [];
        foreach ($waypoints as $point) {
            $coords[] = number_format($point[1], 6, '.', '').','.number_format($point[0], 6, '.', '');
        }

        return 'https://router.project-osrm.org/match/v1/driving/'.implode(';', $coords);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchGoogleDirections(array $waypoints): ?array
    {
        $sampled = $this->limitWaypoints($waypoints, 25);
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '' || count($sampled) < 2) {
            return null;
        }

        $origin = number_format($sampled[0][0], 6, '.', '').','.number_format($sampled[0][1], 6, '.', '');
        $last = $sampled[count($sampled) - 1];
        $destination = number_format($last[0], 6, '.', '').','.number_format($last[1], 6, '.', '');
        $via = [];
        for ($i = 1; $i < count($sampled) - 1; $i++) {
            $via[] = 'via:'.number_format($sampled[$i][0], 6, '.', '').','.number_format($sampled[$i][1], 6, '.', '');
        }

        $query = [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => 'driving',
            'language' => 'nl',
            'key' => $apiKey,
        ];
        if ($via !== []) {
            $query['waypoints'] = implode('|', $via);
        }

        try {
            $response = Http::timeout(8)
                ->connectTimeout(2)
                ->get('https://maps.googleapis.com/maps/api/directions/json', $query);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful() || ($response->json('status') ?? '') !== 'OK') {
            return null;
        }

        $encoded = $response->json('routes.0.overview_polyline.points');
        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $points = $this->dropNearDuplicates($this->decodePolyline($encoded));

        return count($points) >= 2 ? $points : null;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchGoogleOpenRoute(array $waypoints): ?array
    {
        $sampled = $this->limitWaypoints($waypoints, 27);
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '' || count($sampled) < 2) {
            return null;
        }

        $origin = $sampled[0];
        $destination = $sampled[count($sampled) - 1];
        $intermediates = [];
        for ($i = 1; $i < count($sampled) - 1; $i++) {
            $intermediates[] = [
                'location' => [
                    'latLng' => [
                        'latitude' => $sampled[$i][0],
                        'longitude' => $sampled[$i][1],
                    ],
                ],
            ];
        }

        try {
            $response = Http::timeout(6)
                ->connectTimeout(2)
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => 'routes.polyline.encodedPolyline',
                ])
                ->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
                    'origin' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $origin[0],
                                'longitude' => $origin[1],
                            ],
                        ],
                    ],
                    'destination' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $destination[0],
                                'longitude' => $destination[1],
                            ],
                        ],
                    ],
                    'intermediates' => $intermediates,
                    'travelMode' => 'DRIVE',
                    'polylineQuality' => 'HIGH_QUALITY',
                ]);
        } catch (\Throwable) {
            return null;
        }

        return $this->pathFromGoogleRoutesResponse($response);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>|null
     */
    private function fetchOsrmOpenRoute(array $waypoints): ?array
    {
        $sampled = $this->limitWaypoints($waypoints, 100);
        $coords = [];
        foreach ($sampled as $point) {
            $coords[] = number_format($point[1], 6, '.', '').','.number_format($point[0], 6, '.', '');
        }

        try {
            $response = Http::timeout(6)->connectTimeout(2)->get(
                'https://router.project-osrm.org/route/v1/driving/'.implode(';', $coords),
                [
                    'overview' => 'full',
                    'geometries' => 'polyline',
                ]
            );
        } catch (\Throwable) {
            return null;
        }

        return $this->pathFromOsrmResponse($response);
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    private function limitWaypoints(array $waypoints, int $max): array
    {
        $count = count($waypoints);
        if ($count <= $max || $max < 2) {
            return $waypoints;
        }

        $out = [$waypoints[0]];
        $inner = $max - 2;
        for ($i = 1; $i <= $inner; $i++) {
            $index = (int) round($i * ($count - 1) / ($inner + 1));
            $out[] = $waypoints[$index];
        }
        $out[] = $waypoints[$count - 1];

        return $this->dropNearDuplicates($out);
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
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    private function densifyAlongOpenPath(array $points, float $maxMeters): array
    {
        $count = count($points);
        if ($count < 2 || $maxMeters <= 0) {
            return $points;
        }

        $out = [];
        for ($i = 0; $i < $count - 1; $i++) {
            $from = $points[$i];
            $to = $points[$i + 1];
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
        $out[] = $points[$count - 1];

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

    private function encodePolylineSigned(int $value): string
    {
        $value = $value < 0 ? ~($value << 1) : ($value << 1);
        $out = '';
        while ($value >= 0x20) {
            $out .= chr((0x20 | ($value & 0x1F)) + 63);
            $value >>= 5;
        }

        return $out.chr($value + 63);
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

    /**
     * @param  array{0: float, 1: float}  $point
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     */
    private function distanceToSegmentMeters(array $point, array $from, array $to): float
    {
        $lat0 = deg2rad(($from[0] + $to[0]) / 2);
        $cosLat = cos($lat0);
        if (abs($cosLat) < 1e-6) {
            $cosLat = 1e-6;
        }
        $ax = deg2rad($from[1]) * $cosLat;
        $ay = deg2rad($from[0]);
        $bx = deg2rad($to[1]) * $cosLat;
        $by = deg2rad($to[0]);
        $px = deg2rad($point[1]) * $cosLat;
        $py = deg2rad($point[0]);
        $dx = $bx - $ax;
        $dy = $by - $ay;
        $len2 = ($dx * $dx) + ($dy * $dy);
        if ($len2 <= 1e-18) {
            return $this->haversineMeters($point[0], $point[1], $from[0], $from[1]);
        }
        $t = (($px - $ax) * $dx + ($py - $ay) * $dy) / $len2;
        $t = max(0.0, min(1.0, $t));
        $qlat = rad2deg($ay + ($t * $dy));
        $qlng = rad2deg(($ax + ($t * $dx)) / $cosLat);

        return $this->haversineMeters($point[0], $point[1], $qlat, $qlng);
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
