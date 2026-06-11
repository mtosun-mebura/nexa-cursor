<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSearchConsoleService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API_BASE = 'https://www.googleapis.com/webmasters/v3';

    private const SCOPE = 'https://www.googleapis.com/auth/webmasters';

    public function __construct(
        protected GoogleSeoSettingsService $seoSettings,
    ) {}

    /**
     * @return array{ok: bool, message: string, sites?: list<string>}
     */
    public function testConnection(?int $companyId): array
    {
        if (! $this->seoSettings->isSearchConsoleEnabled($companyId)) {
            return ['ok' => false, 'message' => 'Schakel eerst “Search Console API-koppeling” in.'];
        }

        if (! $this->seoSettings->hasServiceAccount($companyId)) {
            return ['ok' => false, 'message' => 'Plak een Google Cloud service account JSON en sla op.'];
        }

        $property = $this->seoSettings->propertySiteUrl($companyId);
        if ($property === null) {
            return ['ok' => false, 'message' => 'Vul een Search Console property in (bijv. sc-domain:example.com of https://www.example.com/).'];
        }

        try {
            $token = $this->accessToken($companyId);
            $response = Http::withToken($token)->timeout(20)->get(self::API_BASE.'/sites');

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->formatApiError($response->status(), $response->json()),
                ];
            }

            $entries = $response->json('siteEntry') ?? [];
            $sites = [];
            foreach ($entries as $entry) {
                if (is_array($entry) && ! empty($entry['siteUrl'])) {
                    $sites[] = (string) $entry['siteUrl'];
                }
            }

            $normalizedProperty = $this->normalizeSiteUrl($property);
            $hasProperty = collect($sites)->contains(fn ($s) => $this->normalizeSiteUrl($s) === $normalizedProperty);

            if (! $hasProperty) {
                return [
                    'ok' => false,
                    'message' => 'API werkt, maar property “'.$property.'” staat niet in Search Console voor dit service account. Voeg het service account toe als gebruiker in Search Console.',
                    'sites' => $sites,
                ];
            }

            return [
                'ok' => true,
                'message' => 'Koppeling geslaagd. Property “'.$property.'” is bereikbaar via de Search Console API.',
                'sites' => $sites,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Koppeling mislukt: '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function submitSitemap(?int $companyId): array
    {
        if (! $this->seoSettings->isSearchConsoleEnabled($companyId)) {
            return ['ok' => false, 'message' => 'Search Console API-koppeling staat uit.'];
        }

        $property = $this->seoSettings->propertySiteUrl($companyId);
        if ($property === null) {
            return ['ok' => false, 'message' => 'Geen Search Console property ingesteld.'];
        }

        $sitemapUrl = $this->seoSettings->sitemapPublicUrl($companyId);

        try {
            $token = $this->accessToken($companyId);
            $siteEncoded = rawurlencode($property);
            $feedEncoded = rawurlencode($sitemapUrl);

            $response = Http::withToken($token)
                ->timeout(20)
                ->put(self::API_BASE.'/sites/'.$siteEncoded.'/sitemaps/'.$feedEncoded);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'ok' => true,
                    'message' => 'Sitemap ingediend bij Google: '.$sitemapUrl,
                ];
            }

            return [
                'ok' => false,
                'message' => $this->formatApiError($response->status(), $response->json()),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Sitemap indienen mislukt: '.$e->getMessage()];
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function accessToken(?int $companyId): string
    {
        $credentials = $this->seoSettings->readServiceAccountJson($companyId);
        if ($credentials === null) {
            throw new \RuntimeException('Geen service account geconfigureerd.');
        }

        $jwt = $this->createSignedJwt($credentials);
        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->formatApiError($response->status(), $response->json()));
        }

        $token = trim((string) $response->json('access_token', ''));
        if ($token === '') {
            throw new \RuntimeException('Geen access token ontvangen van Google.');
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function createSignedJwt(array $credentials): string
    {
        $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
        $privateKey = (string) ($credentials['private_key'] ?? '');
        if ($clientEmail === '' || $privateKey === '') {
            throw new \RuntimeException('Service account JSON mist client_email of private_key.');
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $input = $header.'.'.$payload;
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new \RuntimeException('Kon private key uit service account niet laden.');
        }

        $signature = '';
        if (! openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('JWT ondertekenen mislukt.');
        }

        return $input.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function formatApiError(int $status, ?array $body): string
    {
        $message = is_array($body) ? (string) ($body['error']['message'] ?? $body['error_description'] ?? '') : '';
        if ($message === '') {
            return 'Google API-fout (HTTP '.$status.').';
        }

        return 'Google API: '.$message;
    }

    private function normalizeSiteUrl(string $url): string
    {
        $url = trim($url);
        if (Str::startsWith($url, 'sc-domain:')) {
            return strtolower($url);
        }

        return rtrim(strtolower($url), '/').'/';
    }
}
