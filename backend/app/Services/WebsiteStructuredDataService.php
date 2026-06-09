<?php

namespace App\Services;

use App\Models\Company;
use App\Models\WebsitePage;
use Illuminate\Support\Str;

class WebsiteStructuredDataService
{
    /**
     * JSON-LD @graph voor Organization + WebSite + WebPage (Google & AI-crawlers).
     *
     * @param  array<string, mixed>  $branding  Output van WebsiteBuilderService::getSiteBranding()
     * @param  array<string, mixed>|null  $homeSections
     * @return array<string, mixed>
     */
    public function buildForRenderedPage(
        WebsitePage $page,
        array $branding,
        ?array $homeSections,
        ?string $pageUrl = null,
    ): array {
        $pageUrl = $pageUrl ?? url()->current();

        $pageTitle = filled(trim((string) ($page->title ?? '')))
            ? trim($page->title)
            : ($branding['site_name'] ?? config('app.name', 'Nexa'));

        $pageDescription = filled(trim((string) ($page->meta_description ?? '')))
            ? trim($page->meta_description)
            : Str::limit(strip_tags((string) ($page->content ?? '')), 160, '…');
        if ($pageDescription === '') {
            $pageDescription = trim((string) ($branding['site_description'] ?? ''));
        }

        $company = null;
        if ($page->company_id) {
            $company = Company::find($page->company_id);
        }

        return $this->buildGraph(
            $page,
            $branding,
            $homeSections,
            $pageUrl,
            $pageTitle,
            $pageDescription,
            $company,
        );
    }

    /**
     * @param  array<string, mixed>  $branding
     * @param  array<string, mixed>|null  $homeSections
     * @return array<string, mixed>
     */
    public function buildGraph(
        WebsitePage $page,
        array $branding,
        ?array $homeSections,
        string $pageUrl,
        string $pageTitle,
        string $pageDescription,
        ?Company $company = null,
    ): array {
        $baseUrl = $this->normalizeBaseUrl($pageUrl);
        $orgId = $baseUrl.'/#organization';
        $websiteId = $baseUrl.'/#website';
        $webPageId = rtrim($pageUrl, '/').'#webpage';

        $organization = $this->buildOrganizationNode(
            $orgId,
            $baseUrl,
            $branding,
            $homeSections,
            $company
        );

        $website = $this->compactNode([
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $baseUrl,
            'name' => $branding['site_name'] ?? config('app.name', 'Nexa'),
            'description' => $this->cleanText($branding['site_description'] ?? ''),
            'publisher' => ['@id' => $orgId],
            'inLanguage' => $this->localeTag(),
        ]);

        $webPage = $this->compactNode([
            '@type' => 'WebPage',
            '@id' => $webPageId,
            'url' => $pageUrl,
            'name' => $this->cleanText($pageTitle),
            'description' => $this->cleanText($pageDescription),
            'isPartOf' => ['@id' => $websiteId],
            'about' => ['@id' => $orgId],
            'inLanguage' => $this->localeTag(),
            'dateModified' => optional($page->updated_at)->toAtomString(),
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([$organization, $website, $webPage])),
        ];
    }

    /**
     * @param  array<string, mixed>  $branding
     * @param  array<string, mixed>|null  $homeSections
     * @return array<string, mixed>
     */
    private function buildOrganizationNode(
        string $orgId,
        string $baseUrl,
        array $branding,
        ?array $homeSections,
        ?Company $company,
    ): array {
        $name = $company?->name
            ?: ($branding['site_name'] ?? config('app.name', 'Nexa'));

        $description = $this->firstNonEmpty([
            $company?->description,
            $branding['site_description'] ?? null,
            $this->footerTagline($homeSections),
        ]);

        $logo = $this->firstNonEmpty([
            $branding['logo_url'] ?? null,
            $this->footerLogo($homeSections),
        ]);

        $email = $this->firstNonEmpty([
            $company?->email,
            $company?->contact_email,
        ]);

        $phone = $company?->phone;

        $website = $this->firstNonEmpty([
            $company?->website,
            $baseUrl,
        ]);

        $address = $this->resolvePostalAddress($company, $homeSections);
        $type = $address !== null ? 'LocalBusiness' : 'Organization';

        $node = [
            '@type' => $type,
            '@id' => $orgId,
            'name' => $this->cleanText($name),
            'url' => $website,
            'description' => $description !== null ? $this->cleanText($description) : null,
            'logo' => $logo,
            'email' => $email,
            'telephone' => $phone ? $this->cleanText($phone) : null,
        ];

        if ($address !== null) {
            $node['address'] = $address;
            if ($company?->latitude !== null && $company?->longitude !== null) {
                $node['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (float) $company->latitude,
                    'longitude' => (float) $company->longitude,
                ];
            }
        }

        return $this->compactNode($node);
    }

    /**
     * @param  array<string, mixed>|null  $homeSections
     * @return array<string, mixed>|null
     */
    private function resolvePostalAddress(?Company $company, ?array $homeSections): ?array
    {
        if ($company !== null) {
            $streetLine = trim(implode(' ', array_filter([
                $company->street,
                $company->house_number,
                $company->house_number_extension,
            ])));
            if ($streetLine !== '' || ($company->postal_code ?? '') !== '' || ($company->city ?? '') !== '') {
                return $this->compactNode([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $streetLine !== '' ? $streetLine : null,
                    'postalCode' => $company->postal_code ?: null,
                    'addressLocality' => $company->city ?: null,
                    'addressCountry' => $company->country ?: 'NL',
                ]);
            }
        }

        $footer = is_array($homeSections['footer'] ?? null) ? $homeSections['footer'] : [];
        $street = trim((string) ($footer['map_street'] ?? ''));
        $house = trim((string) ($footer['map_huisnummer'] ?? ''));
        $postcode = trim((string) ($footer['map_postcode'] ?? ''));
        $city = trim((string) ($footer['map_city'] ?? ''));
        $streetLine = trim($street.($house !== '' ? ' '.$house : ''));

        if ($streetLine === '' && $postcode === '' && $city === '') {
            return null;
        }

        return $this->compactNode([
            '@type' => 'PostalAddress',
            'streetAddress' => $streetLine !== '' ? $streetLine : null,
            'postalCode' => $postcode !== '' ? $postcode : null,
            'addressLocality' => $city !== '' ? $city : null,
            'addressCountry' => 'NL',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $homeSections
     */
    private function footerTagline(?array $homeSections): ?string
    {
        $tagline = $homeSections['footer']['tagline'] ?? null;
        if (! is_string($tagline) || trim(strip_tags($tagline)) === '') {
            return null;
        }

        return Str::limit(trim(strip_tags($tagline)), 300, '…');
    }

    /**
     * @param  array<string, mixed>|null  $homeSections
     */
    private function footerLogo(?array $homeSections): ?string
    {
        $url = $homeSections['footer']['logo_url'] ?? null;

        return is_string($url) && trim($url) !== '' ? trim($url) : null;
    }

    /**
     * @param  list<string|null>  $candidates
     */
    private function firstNonEmpty(array $candidates): ?string
    {
        foreach ($candidates as $value) {
            if (! is_string($value)) {
                continue;
            }
            $trimmed = trim($value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function normalizeBaseUrl(string $pageUrl): string
    {
        $parsed = parse_url($pageUrl);
        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return rtrim(url('/'), '/');
        }

        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        return $parsed['scheme'].'://'.$parsed['host'].$port;
    }

    private function localeTag(): string
    {
        $locale = str_replace('_', '-', app()->getLocale());

        return $locale !== '' ? $locale : 'nl-NL';
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function compactNode(array $node): array
    {
        $out = [];
        foreach ($node as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value) && $value === []) {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }
}
