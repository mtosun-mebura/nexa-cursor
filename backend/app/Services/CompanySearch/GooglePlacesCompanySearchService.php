<?php

namespace App\Services\CompanySearch;

use App\Services\EnvService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesCompanySearchService
{
    private const SEARCH_URL = 'https://places.googleapis.com/v1/places:searchText';

    private const FIELD_MASK = 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.location,places.addressComponents,nextPageToken';

    public function __construct(
        protected EnvService $env,
        protected GeoSearchGridService $grid,
        protected DomainService $domains,
        protected PhoneExtractorService $phones,
    ) {}

    public function isEnabled(): bool
    {
        if (! (bool) config('company-enrichment.google.enabled', true)) {
            return false;
        }

        return $this->apiKey() !== '';
    }

    /**
     * @param  (callable(int $found): void)|null  $onProgress
     * @return list<GooglePlaceData>
     */
    public function search(CompanySearchData $search, ?callable $onProgress = null): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $found = [];
        $rps = max(1, (int) config('company-enrichment.google.requests_per_second', 4));
        $delayUs = app()->environment('testing') ? 0 : (int) floor(1_000_000 / $rps);
        $maxPages = max(1, (int) config('company-enrichment.google.max_pages_per_query', 3));
        $pageSize = max(1, min(20, (int) config('company-enrichment.google.page_size', 20)));

        foreach ($this->grid->points($search) as $point) {
            foreach ($search->searchTerms() as $term) {
                $pageToken = null;
                for ($page = 0; $page < $maxPages; $page++) {
                    $payload = $this->searchPayload($term, $point, $pageSize, $pageToken);
                    $json = $this->postSearch($payload);
                    if ($json === null) {
                        break;
                    }
                    foreach ($json['places'] ?? [] as $place) {
                        if (! is_array($place)) {
                            continue;
                        }
                        $mapped = $this->mapPlace($place, $point['province']);
                        if ($mapped === null) {
                            continue;
                        }
                        $found[$mapped->placeId] = $mapped;
                        if (is_callable($onProgress)) {
                            $onProgress(count($found));
                        }
                        if (count($found) >= $search->maxResults) {
                            return array_values($found);
                        }
                    }
                    $pageToken = is_string($json['nextPageToken'] ?? null) ? $json['nextPageToken'] : null;
                    if ($pageToken === null || $pageToken === '') {
                        break;
                    }
                    usleep($delayUs);
                }
                usleep($delayUs);
            }
        }

        return array_values($found);
    }

    /**
     * @param  array{city: string, province: string, lat: float, lng: float, radius_km: int}  $point
     * @return array<string, mixed>
     */
    private function searchPayload(string $term, array $point, int $pageSize, ?string $pageToken): array
    {
        $query = trim($term.' '.$point['city']);
        $payload = [
            'textQuery' => $query,
            'languageCode' => 'nl',
            'regionCode' => 'NL',
            'pageSize' => $pageSize,
        ];
        if ($pageToken) {
            $payload['pageToken'] = $pageToken;
        }
        if ($point['lat'] != 0.0 && $point['lng'] != 0.0) {
            $payload['locationBias'] = [
                'circle' => [
                    'center' => [
                        'latitude' => $point['lat'],
                        'longitude' => $point['lng'],
                    ],
                    'radius' => (float) max(1000, min(50000, $point['radius_km'] * 1000)),
                ],
            ];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function postSearch(array $payload): ?array
    {
        try {
            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Goog-Api-Key' => $this->apiKey(),
                    'X-Goog-FieldMask' => self::FIELD_MASK,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::SEARCH_URL, $payload);
        } catch (\Throwable $e) {
            Log::warning('Google Places search failed', ['error' => $e->getMessage(), 'query' => $payload['textQuery'] ?? '']);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Google Places search failed', [
                'status' => $response->status(),
                'query' => $payload['textQuery'] ?? '',
            ]);

            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array<string, mixed>  $place
     */
    private function mapPlace(array $place, string $fallbackRegion): ?GooglePlaceData
    {
        $id = $place['id'] ?? '';
        if (! is_string($id) || $id === '') {
            $name = $place['name'] ?? '';
            if (is_string($name) && str_starts_with($name, 'places/')) {
                $id = substr($name, 7);
            }
        }
        if (! is_string($id) || $id === '') {
            return null;
        }

        $display = $place['displayName']['text'] ?? $place['displayName'] ?? '';
        $name = is_string($display) ? trim($display) : '';
        if ($name === '') {
            return null;
        }

        $website = is_string($place['websiteUri'] ?? null) ? $this->domains->normalizeWebsite($place['websiteUri']) : '';
        $phone = $this->phones->normalize((string) ($place['nationalPhoneNumber'] ?? $place['internationalPhoneNumber'] ?? ''));
        $components = is_array($place['addressComponents'] ?? null) ? $place['addressComponents'] : [];

        return new GooglePlaceData(
            placeId: $id,
            name: $name,
            formattedAddress: is_string($place['formattedAddress'] ?? null) ? $place['formattedAddress'] : null,
            phone: $phone,
            website: $website !== '' ? $website : null,
            city: $this->component($components, ['locality', 'postal_town']) ?: null,
            region: $this->component($components, ['administrative_area_level_1']) ?: ($fallbackRegion !== '' ? $fallbackRegion : null),
            postalCode: $this->component($components, ['postal_code']) ?: null,
            latitude: isset($place['location']['latitude']) ? (float) $place['location']['latitude'] : null,
            longitude: isset($place['location']['longitude']) ? (float) $place['location']['longitude'] : null,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @param  list<string>  $types
     */
    private function component(array $components, array $types): string
    {
        foreach ($components as $component) {
            $componentTypes = $component['types'] ?? [];
            if (! is_array($componentTypes) || array_intersect($types, $componentTypes) === []) {
                continue;
            }
            $text = $component['longText'] ?? $component['shortText'] ?? '';
            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }
        }

        return '';
    }

    private function apiKey(): string
    {
        $key = trim((string) config('company-enrichment.google.api_key', ''));
        if ($key !== '') {
            return $key;
        }

        return $this->env->getGoogleMapsApiKey();
    }
}
