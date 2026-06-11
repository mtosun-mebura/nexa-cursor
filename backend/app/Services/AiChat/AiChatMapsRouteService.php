<?php

namespace App\Services\AiChat;

use App\Services\EnvService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Geocoding + routeberekening voor chat-offertes.
 */
final class AiChatMapsRouteService
{
    /**
     * @param  array{lat?: float, lng?: float, place_id?: string}|null  $pickupMeta
     * @param  array{lat?: float, lng?: float, place_id?: string}|null  $dropoffMeta
     * @return array{distance_meters: int, duration_seconds: int, pickup_lat: ?float, pickup_lng: ?float, dropoff_lat: ?float, dropoff_lng: ?float, polyline?: ?string}|null
     */
    public function resolveRoute(
        string $pickupAddress,
        string $dropoffAddress,
        ?array $pickupMeta = null,
        ?array $dropoffMeta = null,
    ): ?array {
        $pickup = $this->resolvePoint($pickupAddress, $pickupMeta);
        $dropoff = $this->resolvePoint($dropoffAddress, $dropoffMeta);

        if ($pickup === null || $dropoff === null) {
            return null;
        }

        $route = $this->fetchDrivingRoute(
            (float) $pickup['lat'],
            (float) $pickup['lng'],
            (float) $dropoff['lat'],
            (float) $dropoff['lng'],
        );

        if ($route === null) {
            return null;
        }

        return [
            'distance_meters' => (int) $route['distance_meters'],
            'duration_seconds' => (int) $route['duration_seconds'],
            'pickup_lat' => (float) $pickup['lat'],
            'pickup_lng' => (float) $pickup['lng'],
            'dropoff_lat' => (float) $dropoff['lat'],
            'dropoff_lng' => (float) $dropoff['lng'],
            'polyline' => isset($route['polyline']) && is_string($route['polyline']) && $route['polyline'] !== ''
                ? $route['polyline']
                : null,
        ];
    }

    /**
     * @param  array{lat?: float, lng?: float, place_id?: string}|null  $meta
     * @return array{lat: float, lng: float, label: string}|null
     */
    private function resolvePoint(string $address, ?array $meta): ?array
    {
        $label = trim($address);
        if ($label === '' && is_array($meta)) {
            $label = trim((string) ($meta['label'] ?? ''));
        }

        if (is_array($meta)) {
            $lat = $meta['lat'] ?? null;
            $lng = $meta['lng'] ?? null;
            if (is_numeric($lat) && is_numeric($lng)) {
                return [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                    'label' => $label !== '' ? $label : 'Adres',
                ];
            }

            $placeId = trim((string) ($meta['place_id'] ?? ''));
            if ($placeId !== '') {
                $fromPlace = $this->geocodePlaceId($placeId);
                if ($fromPlace !== null) {
                    if ($label !== '') {
                        $fromPlace['label'] = $label;
                    }

                    return $fromPlace;
                }
            }
        }

        if ($label === '') {
            return null;
        }

        return $this->geocode($label);
    }

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    private function geocode(string $address): ?array
    {
        $query = trim($address);
        if ($query === '') {
            return null;
        }

        $cacheKey = 'ai_chat_geocode:'.md5(mb_strtolower($query));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = null;
        if ($this->mapsApiKey() !== '') {
            $result = $this->geocodeWithGoogle($query);
        }

        if ($result === null) {
            $result = $this->geocodeWithNominatim($query);
        }

        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addHours(6));
        }

        return $result;
    }

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    private function geocodePlaceId(string $placeId): ?array
    {
        $placeId = trim($placeId);
        if ($placeId === '') {
            return null;
        }

        $apiKey = $this->mapsApiKey();
        if ($apiKey === '') {
            return null;
        }

        $cacheKey = 'ai_chat_geocode_place:'.md5($placeId);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'fields' => 'geometry,formatted_address',
            'key' => $apiKey,
            'language' => 'nl',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (($payload['status'] ?? '') !== 'OK') {
            return null;
        }

        $result = is_array($payload['result'] ?? null) ? $payload['result'] : null;
        if ($result === null) {
            return null;
        }

        $location = $result['geometry']['location'] ?? null;
        if (! is_array($location)) {
            return null;
        }

        $lat = $location['lat'] ?? null;
        $lng = $location['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $resolved = [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => trim((string) ($result['formatted_address'] ?? $placeId)),
        ];

        Cache::put($cacheKey, $resolved, now()->addHours(6));

        return $resolved;
    }

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    private function geocodeWithNominatim(string $query): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'NexaTaxi').'/1.0 (ai chat quote)',
        ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'jsonv2',
            'addressdetails' => 0,
            'limit' => 1,
            'dedupe' => 1,
            'accept-language' => 'nl',
            'q' => $query,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $rows = $response->json();
        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $row = $rows[0];
        if (! is_array($row)) {
            return null;
        }

        $lat = $row['lat'] ?? null;
        $lng = $row['lon'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => trim((string) ($row['display_name'] ?? $query)),
        ];
    }

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    private function geocodeWithGoogle(string $query): ?array
    {
        $apiKey = $this->mapsApiKey();
        if ($apiKey === '') {
            return null;
        }

        $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $query,
            'key' => $apiKey,
            'language' => 'nl',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (($payload['status'] ?? '') !== 'OK') {
            return null;
        }

        $results = is_array($payload) ? ($payload['results'] ?? []) : [];
        if (! is_array($results) || $results === []) {
            return null;
        }

        $result = $results[0];
        if (! is_array($result)) {
            return null;
        }

        $location = $result['geometry']['location'] ?? null;
        if (! is_array($location)) {
            return null;
        }

        $lat = $location['lat'] ?? null;
        $lng = $location['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => trim((string) ($result['formatted_address'] ?? $query)),
        ];
    }

    /**
     * @return array{distance_meters: int, duration_seconds: int, polyline?: ?string}|null
     */
    private function fetchDrivingRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        $cacheKey = 'ai_chat_route:'.md5(implode('|', [$fromLng, $fromLat, $toLng, $toLat]));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $route = $this->fetchOsrmRoute($fromLng, $fromLat, $toLng, $toLat)
            ?? $this->fetchGoogleDirectionsRoute($fromLat, $fromLng, $toLat, $toLng);

        if ($route !== null) {
            Cache::put($cacheKey, $route, now()->addHours(6));
        }

        return $route;
    }

    /**
     * @return array{distance_meters: int, duration_seconds: int, polyline?: ?string}|null
     */
    private function fetchOsrmRoute(float $fromLng, float $fromLat, float $toLng, float $toLat): ?array
    {
        $coordinates = implode(';', [
            $this->formatOsrmCoordinate($fromLng, $fromLat),
            $this->formatOsrmCoordinate($toLng, $toLat),
        ]);

        $response = Http::timeout(10)->get(
            'https://router.project-osrm.org/route/v1/driving/'.$coordinates,
            ['overview' => 'simplified', 'geometries' => 'polyline']
        );

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        $route = is_array($payload) ? ($payload['routes'][0] ?? null) : null;
        if (! is_array($route)) {
            return null;
        }

        $distance = $route['distance'] ?? null;
        $duration = $route['duration'] ?? null;
        if (! is_numeric($distance) || ! is_numeric($duration)) {
            return null;
        }

        $geometry = $route['geometry'] ?? null;

        return [
            'distance_meters' => (int) round((float) $distance),
            'duration_seconds' => (int) round((float) $duration),
            'polyline' => is_string($geometry) && $geometry !== '' ? $geometry : null,
        ];
    }

    /**
     * @return array{distance_meters: int, duration_seconds: int, polyline?: ?string}|null
     */
    private function fetchGoogleDirectionsRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): ?array
    {
        $apiKey = $this->mapsApiKey();
        if ($apiKey === '') {
            return null;
        }

        $response = Http::timeout(12)->get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $fromLat.','.$fromLng,
            'destination' => $toLat.','.$toLng,
            'mode' => 'driving',
            'language' => 'nl',
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (($payload['status'] ?? '') !== 'OK') {
            return null;
        }

        $leg = $payload['routes'][0]['legs'][0] ?? null;
        if (! is_array($leg)) {
            return null;
        }

        $distance = $leg['distance']['value'] ?? null;
        $duration = $leg['duration']['value'] ?? null;
        if (! is_numeric($distance) || ! is_numeric($duration)) {
            return null;
        }

        $polyline = $payload['routes'][0]['overview_polyline']['points'] ?? null;

        return [
            'distance_meters' => (int) round((float) $distance),
            'duration_seconds' => (int) round((float) $duration),
            'polyline' => is_string($polyline) && $polyline !== '' ? $polyline : null,
        ];
    }

    private function mapsApiKey(): string
    {
        $fromConfig = trim((string) config('maps.api_key', ''));

        return $fromConfig !== '' ? $fromConfig : trim((string) app(EnvService::class)->getGoogleMapsApiKey());
    }

    private function formatOsrmCoordinate(float $lng, float $lat): string
    {
        return number_format($lng, 6, '.', '').','.number_format($lat, 6, '.', '');
    }
}
