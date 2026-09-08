<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostcodeLookupService
{
    public const SETTING_KEY = 'POSTCODE_PDOK_FALLBACK';

    public const PDOK_URL = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';

    public const PDOK_LEGACY_URL = 'https://geodata.nationaalgeoregister.nl/locatieserver/v3/free';

    public const OPENPOSTCODE_URL = 'https://openpostcode.nl/api/address';

    public const GOOGLE_GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    public const GOOGLE_FINDPLACE_URL = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';

    public const GOOGLE_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

    public static function pdokFallbackEnabled(): bool
    {
        $raw = GeneralSetting::get(self::SETTING_KEY, '1');

        return $raw === null || $raw === '' || $raw === '1' || $raw === 1 || $raw === true;
    }

    /**
     * @return array{
     *     success: bool,
     *     street?: string,
     *     house_number?: string,
     *     postal_code?: string,
     *     city?: string,
     *     country?: string,
     *     latitude?: float|null,
     *     longitude?: float|null,
     *     source?: string,
     *     message?: string
     * }
     */
    public function lookup(string $postcode, string $huisnummer): array
    {
        $postcode = strtoupper(preg_replace('/\s+/', '', $postcode) ?? '');
        $huisnummer = trim($huisnummer);

        if (! preg_match('/^[1-9][0-9]{3}[A-Z]{2}$/', $postcode) || $huisnummer === '') {
            return [
                'success' => false,
                'message' => 'Ongeldig postcode formaat. Gebruik formaat: 1234AB',
            ];
        }

        $primary = $this->fromOpenPostcode($postcode, $huisnummer);
        if ($this->hasStreet($primary)) {
            return $primary;
        }

        if (self::pdokFallbackEnabled()) {
            $pdok = $this->fromPdok($postcode, $huisnummer);
            if ($this->hasStreet($pdok)) {
                return $pdok;
            }
        }

        $google = $this->fromGoogle($postcode, $huisnummer);
        if ($this->hasStreet($google)) {
            return $google;
        }

        if (($primary['success'] ?? false) && trim((string) ($primary['city'] ?? '')) !== '') {
            return $primary;
        }

        if (($google['success'] ?? false) && trim((string) ($google['city'] ?? '')) !== '') {
            return $google;
        }

        return [
            'success' => false,
            'message' => 'Kon het adres niet vinden. Controleer de postcode en het huisnummer.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function hasStreet(array $result): bool
    {
        return ! empty($result['success']) && trim((string) ($result['street'] ?? '')) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function fromOpenPostcode(string $postcode, string $huisnummer): array
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get(self::OPENPOSTCODE_URL, [
                    'postcode' => $postcode,
                    'huisnummer' => $huisnummer,
                ]);
            if (! $response->successful()) {
                return [];
            }
            $data = $response->json();
            if (! is_array($data)) {
                return [];
            }
            $street = trim((string) ($data['straat'] ?? ''));
            $city = trim((string) ($data['woonplaats'] ?? ''));
            if ($street === '' && $city === '') {
                return [];
            }

            return [
                'success' => true,
                'street' => $street,
                'house_number' => (string) ($data['huisnummer'] ?? $huisnummer),
                'postal_code' => (string) ($data['postcode'] ?? $postcode),
                'city' => $city,
                'country' => 'Nederland',
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'source' => 'openpostcode',
            ];
        } catch (\Throwable $e) {
            Log::debug('OpenPostcode API error', [
                'error' => $e->getMessage(),
                'postcode' => $postcode,
            ]);

            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fromPdok(string $postcode, string $huisnummer): array
    {
        $parsed = $this->parseHouseNumber($huisnummer);
        $query = 'postcode:'.strtolower($postcode).' and huisnummer:'.$parsed['number'];
        if ($parsed['letter'] !== '') {
            $query .= ' and huisletter:'.strtolower($parsed['letter']);
        }

        $adres = $this->requestPdok(self::PDOK_URL, $query, 'type:adres', $postcode, $huisnummer, true);
        if ($adres === null) {
            $adres = $this->requestPdok(self::PDOK_LEGACY_URL, $query, 'type:adres', $postcode, $huisnummer, true) ?? [];
        }
        if ($this->hasStreet($adres)) {
            return $adres;
        }

        $postcodeQuery = 'postcode:'.strtolower($postcode);
        $byPostcode = $this->requestPdok(self::PDOK_URL, $postcodeQuery, 'type:postcode', $postcode, $huisnummer, false);
        if ($byPostcode === null) {
            $byPostcode = $this->requestPdok(self::PDOK_LEGACY_URL, $postcodeQuery, 'type:postcode', $postcode, $huisnummer, false) ?? [];
        }
        if ($this->hasStreet($byPostcode)) {
            return $byPostcode;
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestPdok(
        string $url,
        string $query,
        string $filter,
        string $postcode,
        string $huisnummer,
        bool $useDocHouseNumber,
    ): ?array {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'NEXA-Suite/1.0 (postcode-lookup)'])
                ->acceptJson()
                ->get($url, [
                    'q' => $query,
                    'rows' => 1,
                    'fq' => $filter,
                ]);
            if (! $response->successful()) {
                return null;
            }
            $docs = $response->json('response.docs');
            if (! is_array($docs) || $docs === []) {
                return [];
            }
            $doc = is_array($docs[0] ?? null) ? $docs[0] : [];
            $street = trim((string) ($doc['straatnaam'] ?? ''));
            if ($street === '') {
                $weergave = trim((string) ($doc['weergavenaam'] ?? ''));
                if (preg_match('/^(.+?),\s*\d{4}/u', $weergave, $match) === 1) {
                    $street = trim($match[1]);
                }
            }
            $city = trim((string) ($doc['woonplaatsnaam'] ?? ''));
            if ($street === '') {
                return [];
            }
            [$lng, $lat] = $this->parsePoint((string) ($doc['centroide_ll'] ?? ''));

            return [
                'success' => true,
                'street' => $street,
                'house_number' => $useDocHouseNumber
                    ? (string) ($doc['huis_nlt'] ?? $doc['huisnummer'] ?? $huisnummer)
                    : $huisnummer,
                'postal_code' => strtoupper((string) ($doc['postcode'] ?? $postcode)),
                'city' => $city !== '' ? $city : '',
                'country' => 'Nederland',
                'latitude' => $lat,
                'longitude' => $lng,
                'source' => 'pdok',
            ];
        } catch (\Throwable $e) {
            Log::debug('PDOK Locatieserver error', [
                'error' => $e->getMessage(),
                'url' => $url,
                'postcode' => $postcode,
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fromGoogle(string $postcode, string $huisnummer): array
    {
        $apiKey = trim((string) app(EnvService::class)->getGoogleMapsApiKey());
        if ($apiKey === '') {
            return [];
        }

        $pretty = $this->prettyPostcode($postcode);
        $parsed = $this->googleFromGeocode($apiKey, $pretty.' '.$huisnummer.', Nederland', $postcode, $huisnummer);
        if ($this->hasStreet($parsed)) {
            return $this->withoutInternalGoogleMeta($parsed);
        }

        $cityOnly = [];
        if (($parsed['success'] ?? false) && trim((string) ($parsed['city'] ?? '')) !== '') {
            $cityOnly = $parsed;
        } else {
            $parsed = $this->googleFromGeocode($apiKey, $huisnummer.', '.$pretty.', Nederland', $postcode, $huisnummer);
            if ($this->hasStreet($parsed)) {
                return $this->withoutInternalGoogleMeta($parsed);
            }
            if (($parsed['success'] ?? false) && trim((string) ($parsed['city'] ?? '')) !== '') {
                $cityOnly = $parsed;
            }
        }

        $foundPostal = strtoupper(preg_replace('/\s+/', '', (string) ($cityOnly['_found_postal'] ?? '')) ?? '');
        if ($foundPostal === $postcode) {
            $places = $this->googleFromPlaces($apiKey, $pretty.' '.$huisnummer.' Nederland', $postcode, $huisnummer);
            if ($this->hasStreet($places)) {
                return $this->withoutInternalGoogleMeta($places);
            }
        }

        return $this->withoutInternalGoogleMeta($cityOnly);
    }

    /**
     * @return array<string, mixed>
     */
    private function googleFromGeocode(string $apiKey, string $query, string $postcode, string $huisnummer): array
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get(self::GOOGLE_GEOCODE_URL, [
                'address' => $query,
                'key' => $apiKey,
                'language' => 'nl',
                'region' => 'nl',
                'components' => 'country:NL',
            ]);
            if (! $response->successful()) {
                return [];
            }
            $payload = $response->json();
            if (! is_array($payload) || ($payload['status'] ?? '') !== 'OK') {
                return [];
            }
            $results = $payload['results'] ?? [];
            if (! is_array($results)) {
                return [];
            }

            foreach ($results as $result) {
                if (! is_array($result)) {
                    continue;
                }
                $parsed = $this->parseGoogleResult($result, $postcode, $huisnummer, 'google');
                if ($this->hasStreet($parsed) || (($parsed['success'] ?? false) && trim((string) ($parsed['city'] ?? '')) !== '')) {
                    return $parsed;
                }
            }

            return [];
        } catch (\Throwable $e) {
            Log::debug('Google Geocoding postcode lookup error', [
                'error' => $e->getMessage(),
                'postcode' => $postcode,
            ]);

            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function googleFromPlaces(string $apiKey, string $query, string $postcode, string $huisnummer): array
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get(self::GOOGLE_FINDPLACE_URL, [
                'input' => $query,
                'inputtype' => 'textquery',
                'fields' => 'place_id,formatted_address',
                'language' => 'nl',
                'key' => $apiKey,
            ]);
            if (! $response->successful()) {
                return [];
            }
            $payload = $response->json();
            if (! is_array($payload) || ($payload['status'] ?? '') !== 'OK') {
                return [];
            }
            $candidates = $payload['candidates'] ?? [];
            if (! is_array($candidates) || $candidates === []) {
                return [];
            }
            $candidate = is_array($candidates[0] ?? null) ? $candidates[0] : [];
            $placeId = trim((string) ($candidate['place_id'] ?? ''));
            if ($placeId === '') {
                return $this->parseGoogleFormattedAddress(
                    (string) ($candidate['formatted_address'] ?? ''),
                    $postcode,
                    $huisnummer
                );
            }

            $details = Http::timeout(8)->acceptJson()->get(self::GOOGLE_DETAILS_URL, [
                'place_id' => $placeId,
                'fields' => 'address_component,formatted_address,geometry',
                'language' => 'nl',
                'key' => $apiKey,
            ]);
            if ($details->successful()) {
                $detailPayload = $details->json();
                $result = is_array($detailPayload) ? ($detailPayload['result'] ?? null) : null;
                if (is_array($result)) {
                    $parsed = $this->parseGoogleResult($result, $postcode, $huisnummer, 'google');
                    if ($this->hasStreet($parsed) || (($parsed['success'] ?? false) && trim((string) ($parsed['city'] ?? '')) !== '')) {
                        return $parsed;
                    }
                }
            }

            return $this->parseGoogleFormattedAddress(
                (string) ($candidate['formatted_address'] ?? ''),
                $postcode,
                $huisnummer
            );
        } catch (\Throwable $e) {
            Log::debug('Google Places postcode lookup error', [
                'error' => $e->getMessage(),
                'postcode' => $postcode,
            ]);

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function parseGoogleResult(array $result, string $postcode, string $huisnummer, string $source): array
    {
        $components = $result['address_components'] ?? [];
        if (! is_array($components)) {
            $components = [];
        }

        $street = $this->googleComponent($components, 'route');
        $city = $this->googleComponent($components, 'locality');
        if ($city === '') {
            $city = $this->googleComponent($components, 'postal_town');
        }
        $foundPostal = $this->googleComponent($components, 'postal_code');
        if ($foundPostal === '') {
            $foundPostal = $this->googleComponent($components, 'postal_code_prefix');
        }
        $country = strtoupper($this->googleComponent($components, 'country'));
        $streetNumber = $this->googleComponent($components, 'street_number');

        if ($country !== '' && $country !== 'NL' && strcasecmp($country, 'Nederland') !== 0) {
            return [];
        }
        if ($foundPostal !== '' && ! $this->postalMatches($postcode, $foundPostal)) {
            return [];
        }
        if ($street === '' && $city === '') {
            return [];
        }
        if ($foundPostal === '' && $street === '') {
            return [];
        }

        $location = $result['geometry']['location'] ?? [];
        $lat = is_array($location) && is_numeric($location['lat'] ?? null) ? (float) $location['lat'] : null;
        $lng = is_array($location) && is_numeric($location['lng'] ?? null) ? (float) $location['lng'] : null;

        return [
            'success' => true,
            'street' => $street,
            'house_number' => $streetNumber !== '' ? $streetNumber : $huisnummer,
            'postal_code' => $postcode,
            'city' => $city,
            'country' => 'Nederland',
            'latitude' => $lat,
            'longitude' => $lng,
            'source' => $source,
            '_found_postal' => $foundPostal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseGoogleFormattedAddress(string $formatted, string $postcode, string $huisnummer): array
    {
        $formatted = trim($formatted);
        if ($formatted === '') {
            return [];
        }
        $pretty = $this->prettyPostcode($postcode);
        $pattern = '/,\s*([^,]+),\s*'.preg_quote($pretty, '/').'\s+([^,]+)/iu';
        if (preg_match($pattern, ', '.$formatted, $match) !== 1) {
            $compact = substr($postcode, 0, 4).substr($postcode, 4);
            $pattern = '/,\s*([^,]+),\s*'.preg_quote($compact, '/').'\s+([^,]+)/iu';
            if (preg_match($pattern, ', '.$formatted, $match) !== 1) {
                return [];
            }
        }

        $street = trim($match[1]);
        $city = trim(preg_replace('/,.*$/', '', $match[2]) ?? '');
        if ($street === '' || $city === '') {
            return [];
        }

        return [
            'success' => true,
            'street' => $street,
            'house_number' => $huisnummer,
            'postal_code' => $postcode,
            'city' => $city,
            'country' => 'Nederland',
            'latitude' => null,
            'longitude' => null,
            'source' => 'google',
            '_found_postal' => $postcode,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function withoutInternalGoogleMeta(array $result): array
    {
        unset($result['_found_postal']);

        return $result;
    }

    /**
     * @param  array<int, mixed>  $components
     */
    private function googleComponent(array $components, string $type): string
    {
        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }
            $types = $component['types'] ?? [];
            if (! is_array($types) || ! in_array($type, $types, true)) {
                continue;
            }

            return trim((string) ($component['long_name'] ?? ''));
        }

        return '';
    }

    private function postalMatches(string $requested, string $found): bool
    {
        $requested = strtoupper(preg_replace('/\s+/', '', $requested) ?? '');
        $found = strtoupper(preg_replace('/\s+/', '', $found) ?? '');
        if ($found === '' || $requested === '') {
            return false;
        }
        if ($found === $requested) {
            return true;
        }

        return strlen($found) === 4 && str_starts_with($requested, $found);
    }

    private function prettyPostcode(string $postcode): string
    {
        return substr($postcode, 0, 4).' '.substr($postcode, 4);
    }

    /**
     * @return array{number: string, letter: string}
     */
    private function parseHouseNumber(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/^(\d+)\s*([A-Za-z])\b/u', $raw, $match) === 1) {
            return ['number' => $match[1], 'letter' => strtoupper($match[2])];
        }
        if (preg_match('/^(\d+)/', $raw, $match) === 1) {
            return ['number' => $match[1], 'letter' => ''];
        }

        return ['number' => $raw, 'letter' => ''];
    }

    /**
     * @return array{0: float|null, 1: float|null}
     */
    private function parsePoint(string $point): array
    {
        if (preg_match('/POINT\(\s*([+-]?\d+(?:\.\d+)?)\s+([+-]?\d+(?:\.\d+)?)\s*\)/i', $point, $match) !== 1) {
            return [null, null];
        }

        return [(float) $match[1], (float) $match[2]];
    }
}
