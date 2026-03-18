<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Haalt Google Place-reviews op via Places API (New) en cached ze.
 * Configuratie: GeneralSetting google_reviews_place_id, google_reviews_business_name, google_reviews_cache_hours.
 * Place ID of bedrijfsnaam: vul Place ID in (bijv. ChIJ...) of alleen bedrijfsnaam; bij bedrijfsnaam wordt Text Search gebruikt om het eerste resultaat te vinden.
 * API key: zelfde als Maps (config('maps.api_key')); Places API moet ingeschakeld zijn.
 */
class GoogleReviewsService
{
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
     * Returns place info + reviews. Structure: ['place_name' => string, 'rating' => float, 'user_rating_count' => int, 'place_id' => string, 'reviews' => array, 'write_review_url' => string]
     * Backwards compatible: if only reviews are needed, use $result['reviews'] (always an array).
     * Gebruikt Place ID indien ingevuld, anders zoekt op bedrijfsnaam (google_reviews_business_name) via Text Search.
     */
    public function getReviews(): array
    {
        $placeId = \App\Models\GeneralSetting::get('google_reviews_place_id', '');
        $placeId = trim((string) $placeId);
        $businessName = trim((string) \App\Models\GeneralSetting::get('google_reviews_business_name', ''));

        $resolvedPlaceId = '';
        if ($placeId !== '' && self::looksLikePlaceId($placeId)) {
            $resolvedPlaceId = self::normalizePlaceId($placeId);
        } elseif ($businessName !== '') {
            $resolvedPlaceId = $this->resolvePlaceIdFromBusinessName($businessName);
        }

        if ($resolvedPlaceId === '') {
            return ['place_name' => '', 'rating' => 0.0, 'user_rating_count' => 0, 'place_id' => '', 'reviews' => [], 'write_review_url' => ''];
        }

        $cacheHours = (int) \App\Models\GeneralSetting::get('google_reviews_cache_hours', '24');
        $cacheHours = max(1, min(168, $cacheHours));
        $cacheKey = 'google_reviews_' . md5($placeId . '|' . $businessName);

        $result = Cache::remember($cacheKey, $cacheHours * 3600, function () use ($resolvedPlaceId) {
            return $this->fetchPlaceAndReviews($resolvedPlaceId);
        });

        $minStars = (int) \App\Models\GeneralSetting::get('google_reviews_min_stars', '1');
        $minStars = max(1, min(5, $minStars));
        $result['reviews'] = array_values(array_filter($result['reviews'], function ($r) use ($minStars) {
            return ((int) ($r['rating'] ?? 0)) >= $minStars;
        }));

        // Places API (New) levert max 5 reviews; instelling bepaalt hoeveel we tonen (1–5).
        $count = (int) \App\Models\GeneralSetting::get('google_reviews_count', '5');
        $count = max(1, min(5, $count));
        $result['reviews'] = array_slice($result['reviews'], 0, $count);

        return $result;
    }

    /**
     * Haalt dezelfde place + reviews op als getReviews() maar zonder min_stars- en count-filter.
     * Handig om te tellen hoeveel reviews er zijn met bv. minimaal 3 sterren (Places API levert max 5).
     *
     * @return array{place_name: string, rating: float, user_rating_count: int, place_id: string, reviews: array, write_review_url: string}
     */
    public function getPlaceAndReviewsUnfiltered(): array
    {
        $placeId = \App\Models\GeneralSetting::get('google_reviews_place_id', '');
        $placeId = trim((string) $placeId);
        $businessName = trim((string) \App\Models\GeneralSetting::get('google_reviews_business_name', ''));

        $resolvedPlaceId = '';
        if ($placeId !== '' && self::looksLikePlaceId($placeId)) {
            $resolvedPlaceId = self::normalizePlaceId($placeId);
        } elseif ($businessName !== '') {
            $resolvedPlaceId = $this->resolvePlaceIdFromBusinessName($businessName);
        }

        if ($resolvedPlaceId === '') {
            return ['place_name' => '', 'rating' => 0.0, 'user_rating_count' => 0, 'place_id' => '', 'reviews' => [], 'write_review_url' => ''];
        }

        $cacheHours = (int) \App\Models\GeneralSetting::get('google_reviews_cache_hours', '24');
        $cacheHours = max(1, min(168, $cacheHours));
        $cacheKey = 'google_reviews_' . md5($placeId . '|' . $businessName);

        return Cache::remember($cacheKey, $cacheHours * 3600, function () use ($resolvedPlaceId) {
            return $this->fetchPlaceAndReviews($resolvedPlaceId);
        });
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

        if (!$response->successful()) {
            Log::warning('Google Reviews: Text Search mislukt.', [
                'query' => $businessName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return '';
        }

        $data = $response->json();
        $places = $data['places'] ?? [];
        if (!is_array($places) || count($places) === 0) {
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

        if (!$response->successful()) {
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
        if (!is_array($reviews)) {
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
