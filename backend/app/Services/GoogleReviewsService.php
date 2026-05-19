<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Haalt Google Place-reviews op via Places API (New) en cached ze.
 * Configuratie: GeneralSetting google_reviews_place_id, google_reviews_business_name, google_reviews_cache_hours, google_reviews_section_title, google_reviews_section_background.
 * Place ID of bedrijfsnaam: vul Place ID in (bijv. ChIJ...) of alleen bedrijfsnaam; bij bedrijfsnaam wordt Text Search gebruikt om het eerste resultaat te vinden.
 * API key: zelfde als Maps (config('maps.api_key')); Places API moet ingeschakeld zijn.
 */
class GoogleReviewsService
{
    public const COMPONENT_SECTION_KEY = 'component:website.google_reviews';

    /** @var list<string> */
    public const COMPONENT_SECTION_KEYS = [
        self::COMPONENT_SECTION_KEY,
        'component:nexa.google_reviews',
    ];

    protected const PLACES_API_URL = 'https://places.googleapis.com/v1/places/%s';

    protected const PLACES_SEARCH_TEXT_URL = 'https://places.googleapis.com/v1/places:searchText';

    protected const WRITE_REVIEW_URL = 'https://search.google.com/local/writereview?placeid=%s';

    /**
     * Normaliseert een Place ID: verwijdert het "places/" prefix als dat in de waarde staat (API verwacht alleen het ID in het URL-pad).
     */
    public static function normalizePlaceId(string $placeId): string
    {
        $placeId = trim($placeId);
        if (stripos($placeId, 'places/') === 0) {
            return trim(substr($placeId, 7));
        }

        return $placeId;
    }

    /**
     * Bepaalt of een string eruitziet als een Google Place ID (bijv. begint met ChIJ).
     */
    public static function looksLikePlaceId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (stripos($value, 'places/') === 0) {
            return true;
        }

        return preg_match('/^[A-Za-z0-9_-]{20,}$/', $value) === 1;
    }

    /**
     * Normaliseert een hex-kleur voor de review-sectie (#rrggbb) of geeft '' bij ongeldige invoer.
     */
    public static function normalizeHexColor(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if ($value[0] !== '#') {
            $value = '#'.$value;
        }
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value) !== 1) {
            return '';
        }
        if (strlen($value) === 4) {
            $value = '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
        }

        return strtolower($value);
    }

    /**
     * Bedrijf voor review-instellingen: pagina-koppeling, anders tenant uit host/sessie.
     *
     * getReviews-structuur o.a.: place_name, rating, reviews, section_title, section_background (hex of leeg).
     */
    public static function resolveCompanyIdForWebsitePage(?\App\Models\WebsitePage $page = null): ?int
    {
        if ($page !== null && $page->company_id !== null && $page->company_id !== '') {
            return (int) $page->company_id;
        }
        if (app()->bound('resolved_tenant_id')) {
            $tenantId = app('resolved_tenant_id');
            if ($tenantId !== null && $tenantId !== '' && is_numeric($tenantId)) {
                return (int) $tenantId;
            }
        }

        return \App\Models\GeneralSetting::resolveScopeCompanyId();
    }

    public function getReviews(?int $forCompanyId = null): array
    {
        $settings = $this->reviewSettings($forCompanyId);
        $placeId = $settings['place_id'];
        $businessName = $settings['business_name'];

        $resolvedPlaceId = '';
        if ($placeId !== '' && self::looksLikePlaceId($placeId)) {
            $resolvedPlaceId = self::normalizePlaceId($placeId);
        } elseif ($businessName !== '') {
            $resolvedPlaceId = $this->resolvePlaceIdFromBusinessName($businessName);
        }

        if ($resolvedPlaceId === '') {
            return [
                'place_name' => '',
                'rating' => 0.0,
                'user_rating_count' => 0,
                'place_id' => '',
                'reviews' => [],
                'write_review_url' => '',
                'section_title' => trim((string) ($settings['section_title'] ?? '')),
                'section_background' => trim((string) ($settings['section_background'] ?? '')),
            ];
        }

        $cacheHours = $settings['cache_hours'];
        $cacheKey = 'google_reviews_'.md5($placeId.'|'.$businessName.'|'.($forCompanyId ?? 'global'));

        $result = Cache::remember($cacheKey, $cacheHours * 3600, function () use ($resolvedPlaceId) {
            return $this->fetchPlaceAndReviews($resolvedPlaceId);
        });

        $minStars = $settings['min_stars'];
        $result['reviews'] = array_values(array_filter($result['reviews'], function ($r) use ($minStars) {
            return ((int) ($r['rating'] ?? 0)) >= $minStars;
        }));

        // Places API (New) levert max 5 reviews; instelling bepaalt hoeveel we tonen (1–5).
        $count = $settings['count'];
        $count = max(1, min(5, $count));
        $result['reviews'] = array_slice($result['reviews'], 0, $count);
        $result['section_title'] = trim((string) ($settings['section_title'] ?? ''));
        $result['section_background'] = trim((string) ($settings['section_background'] ?? ''));

        return $result;
    }

    /**
     * Haalt dezelfde place + reviews op als getReviews() maar zonder min_stars- en count-filter.
     * Handig om te tellen hoeveel reviews er zijn met bv. minimaal 3 sterren (Places API levert max 5).
     *
     * @return array{place_name: string, rating: float, user_rating_count: int, place_id: string, reviews: array, write_review_url: string, section_title: string, section_background: string}
     */
    public function getPlaceAndReviewsUnfiltered(?int $forCompanyId = null): array
    {
        $settings = $this->reviewSettings($forCompanyId);
        $placeId = $settings['place_id'];
        $businessName = $settings['business_name'];

        $resolvedPlaceId = '';
        if ($placeId !== '' && self::looksLikePlaceId($placeId)) {
            $resolvedPlaceId = self::normalizePlaceId($placeId);
        } elseif ($businessName !== '') {
            $resolvedPlaceId = $this->resolvePlaceIdFromBusinessName($businessName);
        }

        if ($resolvedPlaceId === '') {
            return [
                'place_name' => '',
                'rating' => 0.0,
                'user_rating_count' => 0,
                'place_id' => '',
                'reviews' => [],
                'write_review_url' => '',
                'section_title' => trim((string) ($settings['section_title'] ?? '')),
                'section_background' => trim((string) ($settings['section_background'] ?? '')),
            ];
        }

        $cacheHours = $settings['cache_hours'];
        $cacheKey = 'google_reviews_'.md5($placeId.'|'.$businessName.'|'.($forCompanyId ?? 'global'));

        $result = Cache::remember($cacheKey, $cacheHours * 3600, function () use ($resolvedPlaceId) {
            return $this->fetchPlaceAndReviews($resolvedPlaceId);
        });
        $result['section_title'] = trim((string) ($settings['section_title'] ?? ''));
        $result['section_background'] = trim((string) ($settings['section_background'] ?? ''));

        return $result;
    }

    /**
     * Sla Google Reviews-instellingen op voor een tenant (website-pagina of configuraties).
     *
     * @param  array<string, mixed>  $fields  place_id, business_name, cache_hours, count, min_stars, section_title, section_background
     */
    public function persistSettingsForCompany(int $companyId, array $fields): void
    {
        if ($companyId < 1) {
            return;
        }

        $old = $this->reviewSettings($companyId);

        if (array_key_exists('place_id', $fields)) {
            \App\Models\GeneralSetting::set('google_reviews_place_id', trim((string) $fields['place_id']), $companyId);
        }
        if (array_key_exists('business_name', $fields)) {
            \App\Models\GeneralSetting::set('google_reviews_business_name', trim((string) $fields['business_name']), $companyId);
        }
        if (array_key_exists('cache_hours', $fields) && $fields['cache_hours'] !== null && $fields['cache_hours'] !== '') {
            $hours = max(1, min(168, (int) $fields['cache_hours']));
            \App\Models\GeneralSetting::set('google_reviews_cache_hours', (string) $hours, $companyId);
        }
        if (array_key_exists('count', $fields) && $fields['count'] !== null && $fields['count'] !== '') {
            $count = max(1, min(5, (int) $fields['count']));
            \App\Models\GeneralSetting::set('google_reviews_count', (string) $count, $companyId);
        }
        if (array_key_exists('min_stars', $fields) && $fields['min_stars'] !== null && $fields['min_stars'] !== '') {
            $minStars = max(1, min(5, (int) $fields['min_stars']));
            \App\Models\GeneralSetting::set('google_reviews_min_stars', (string) $minStars, $companyId);
        }
        if (array_key_exists('section_title', $fields)) {
            \App\Models\GeneralSetting::set('google_reviews_section_title', trim((string) $fields['section_title']), $companyId);
        }
        if (array_key_exists('section_background', $fields)) {
            $bg = self::normalizeHexColor((string) ($fields['section_background'] ?? ''));
            \App\Models\GeneralSetting::set('google_reviews_section_background', $bg, $companyId);
        }

        $new = $this->reviewSettings($companyId);
        try {
            Cache::forget('google_reviews_'.md5($old['place_id'].'|'.$old['business_name'].'|'.$companyId));
            Cache::forget('google_reviews_'.md5($new['place_id'].'|'.$new['business_name'].'|'.$companyId));
        } catch (\Throwable) {
            // Negeer cachefouten
        }
    }

    /**
     * @return array{place_id: string, business_name: string, cache_hours: int, count: int, min_stars: int, section_title: string, section_background: string}
     */
    private function reviewSettings(?int $forCompanyId): array
    {
        $placeId = trim((string) \App\Models\GeneralSetting::get('google_reviews_place_id', '', $forCompanyId));
        $businessName = trim((string) \App\Models\GeneralSetting::get('google_reviews_business_name', '', $forCompanyId));
        $cacheHours = (int) \App\Models\GeneralSetting::get('google_reviews_cache_hours', '24', $forCompanyId);
        $cacheHours = max(1, min(168, $cacheHours));
        $count = max(1, min(5, (int) \App\Models\GeneralSetting::get('google_reviews_count', '5', $forCompanyId)));
        $minStars = max(1, min(5, (int) \App\Models\GeneralSetting::get('google_reviews_min_stars', '1', $forCompanyId)));
        $sectionTitle = trim((string) \App\Models\GeneralSetting::get('google_reviews_section_title', '', $forCompanyId));
        $sectionBackground = trim((string) \App\Models\GeneralSetting::get('google_reviews_section_background', '', $forCompanyId));
        $sectionBackground = $sectionBackground !== '' ? self::normalizeHexColor($sectionBackground) : '';

        return [
            'place_id' => $placeId,
            'business_name' => $businessName,
            'cache_hours' => $cacheHours,
            'count' => $count,
            'min_stars' => $minStars,
            'section_title' => $sectionTitle,
            'section_background' => $sectionBackground,
        ];
    }

    /**
     * Zoekt via Places API (New) Text Search op bedrijfsnaam en geeft het Place ID van het eerste resultaat terug, of null.
     */
    protected function resolvePlaceIdFromBusinessName(string $businessName): string
    {
        $apiKey = config('maps.api_key') ?: (app(EnvService::class)->getGoogleMapsApiKey() ?: '');
        if ($apiKey === null || $apiKey === '') {
            Log::warning('Google Reviews: geen API key voor Text Search (bedrijfsnaam).');

            return '';
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'places.id',
        ])->post(self::PLACES_SEARCH_TEXT_URL, [
            'textQuery' => $businessName,
            'languageCode' => 'nl',
            'regionCode' => 'NL',
        ]);

        if (! $response->successful()) {
            Log::warning('Google Reviews: Text Search mislukt.', [
                'query' => $businessName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return '';
        }

        $data = $response->json();
        $places = $data['places'] ?? [];
        if (! is_array($places) || count($places) === 0) {
            Log::info('Google Reviews: geen resultaten voor bedrijfsnaam.', ['query' => $businessName]);

            return '';
        }

        $first = $places[0];
        $id = $first['id'] ?? '';
        if ($id === '' && isset($first['name']) && is_string($first['name'])) {
            if (stripos($first['name'], 'places/') === 0) {
                $id = trim(substr($first['name'], 7));
            }
        }

        return is_string($id) ? $id : '';
    }

    /**
     * @return array{place_name: string, rating: float, user_rating_count: int, place_id: string, reviews: array, write_review_url: string}
     */
    protected function fetchPlaceAndReviews(string $placeId): array
    {
        $apiKey = config('maps.api_key') ?: (app(EnvService::class)->getGoogleMapsApiKey() ?: '');
        if ($apiKey === null || $apiKey === '') {
            Log::warning('Google Reviews: geen API key geconfigureerd (Maps/Places).');

            return ['place_name' => '', 'rating' => 0.0, 'user_rating_count' => 0, 'place_id' => $placeId, 'reviews' => [], 'write_review_url' => sprintf(self::WRITE_REVIEW_URL, $placeId)];
        }

        $url = sprintf(self::PLACES_API_URL, $placeId);
        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,reviews',
        ])->get($url, [
            'languageCode' => 'nl',
        ]);

        if (! $response->successful()) {
            Log::warning('Google Reviews: Places API request mislukt.', [
                'place_id' => $placeId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['place_name' => '', 'rating' => 0.0, 'user_rating_count' => 0, 'place_id' => $placeId, 'reviews' => [], 'write_review_url' => sprintf(self::WRITE_REVIEW_URL, $placeId)];
        }

        $data = $response->json();
        $placeNameRaw = $data['displayName'] ?? '';
        $placeName = is_string($placeNameRaw) ? $placeNameRaw : (is_array($placeNameRaw) ? ($placeNameRaw['text'] ?? '') : '');
        $rating = isset($data['rating']) ? (float) $data['rating'] : 0.0;
        $userRatingCount = isset($data['userRatingCount']) ? (int) $data['userRatingCount'] : 0;

        // Places API (New) retourneert maximaal 5 reviews; user_rating_count is het totaal aantal beoordelingen (kan hoger zijn).
        $reviews = $data['reviews'] ?? [];
        if (! is_array($reviews)) {
            $reviews = [];
        }

        if (count($reviews) === 0 && ($rating > 0 || $userRatingCount > 0)) {
            Log::info('Google Reviews: Places API gaf 0 reviews terug voor place_id.', [
                'place_id' => $placeId,
                'user_rating_count' => $userRatingCount,
                'rating' => $rating,
            ]);
        }

        $out = [];
        foreach ($reviews as $r) {
            $authorRaw = $r['authorAttribution']['displayName'] ?? $r['name'] ?? 'Anoniem';
            $author = is_string($authorRaw) ? $authorRaw : (is_array($authorRaw) ? ($authorRaw['text'] ?? (string) reset($authorRaw)) : 'Anoniem');
            $revRating = isset($r['rating']) ? (int) $r['rating'] : 0;
            $textRaw = $r['text'] ?? null;
            $text = $this->extractLocalizedText($textRaw);
            if ($text === '' && isset($r['originalText'])) {
                $text = $this->extractLocalizedText($r['originalText']);
            }
            $timeRaw = $r['relativePublishTimeDescription'] ?? '';
            $relativeTime = is_string($timeRaw) ? $timeRaw : (is_array($timeRaw) ? ($timeRaw['text'] ?? (string) reset($timeRaw)) : '');
            $out[] = [
                'author_name' => $author,
                'rating' => $revRating,
                'text' => $text,
                'time' => $relativeTime,
                'profile_photo_url' => $r['authorAttribution']['photoUri'] ?? null,
            ];
        }

        return [
            'place_name' => $placeName,
            'rating' => $rating,
            'user_rating_count' => $userRatingCount,
            'place_id' => $placeId,
            'reviews' => $out,
            'write_review_url' => sprintf(self::WRITE_REVIEW_URL, $placeId),
        ];
    }

    /**
     * Haalt de tekst uit een Places API LocalizedText veld (object met "text" en "languageCode").
     */
    protected function extractLocalizedText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['text']) && is_string($value['text'])) {
            return $value['text'];
        }
        if (is_array($value)) {
            return (string) reset($value);
        }

        return '';
    }
}
