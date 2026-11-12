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
        $pageUrl = $pageUrl ?? \App\Support\WebsiteSeoMeta::canonicalUrl();

        $pageTitle = filled(trim((string) ($page->title ?? '')))
            ? trim($page->title)
            : \App\Support\WebsiteSeoMeta::brandLabel($branding);

        $pageDescription = \App\Support\WebsiteSeoMeta::resolveDescription(
            $page->meta_description ?? null,
            $page->content ?? null,
            $branding['site_description'] ?? null,
            \App\Support\WebsiteSeoMeta::brandLabel($branding),
        );

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

        $websiteName = \App\Support\WebsiteSeoMeta::brandLabel($branding);
        $siteDescRaw = trim((string) ($branding['site_description'] ?? ''));
        $websiteDescription = \App\Support\WebsiteSeoMeta::isUsableDescription($siteDescRaw)
            ? \App\Support\WebsiteSeoMeta::resolveDescription($siteDescRaw)
            : null;

        $isTaxi = $this->isTaxiContext($company, $branding, $websiteName);
        $isHome = in_array(strtolower(trim((string) ($page->slug ?? ''))), ['', 'home'], true)
            || ($page->page_type ?? '') === 'home';

        $website = $this->compactNode([
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $baseUrl,
            'name' => $websiteName !== '' ? $websiteName : (config('app.name', 'Nexa')),
            'description' => $websiteDescription,
            'publisher' => ['@id' => $orgId],
            'inLanguage' => $this->localeTag(),
            'potentialAction' => $this->compactNode([
                '@type' => 'ReserveAction',
                'target' => $this->compactNode([
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $baseUrl.'/',
                    'inLanguage' => $this->localeTag(),
                    'actionPlatform' => [
                        'http://schema.org/DesktopWebPlatform',
                        'http://schema.org/MobileWebPlatform',
                    ],
                ]),
                'name' => $isTaxi ? 'Taxi boeken' : 'Start',
            ]),
        ]);

        $webPageTypes = ['WebPage'];
        if ($isHome && $isTaxi) {
            $webPageTypes[] = 'CollectionPage';
        }

        $webPage = $this->compactNode([
            '@type' => count($webPageTypes) === 1 ? $webPageTypes[0] : $webPageTypes,
            '@id' => $webPageId,
            'url' => $pageUrl,
            'name' => $this->cleanText($pageTitle),
            'description' => $this->cleanText($pageDescription),
            'isPartOf' => ['@id' => $websiteId],
            'about' => ['@id' => $orgId],
            'inLanguage' => $this->localeTag(),
            'dateModified' => optional($page->updated_at)->toAtomString(),
            'primaryImageOfPage' => $this->primaryImage($branding, $homeSections),
        ]);

        $graph = [$organization, $website, $webPage];

        if ($isTaxi) {
            $graph[] = $this->compactNode([
                '@type' => 'TaxiService',
                '@id' => $baseUrl.'/#taxiservice',
                'name' => $websiteName !== '' ? $websiteName : 'Taxi',
                'url' => $baseUrl.'/',
                'description' => $websiteDescription
                    ?: $this->cleanText($pageDescription)
                    ?: 'Taxi boeken en bestellen via de website.',
                'provider' => ['@id' => $orgId],
                'areaServed' => $this->compactNode([
                    '@type' => 'Country',
                    'name' => 'Netherlands',
                ]),
                'availableChannel' => $this->compactNode([
                    '@type' => 'ServiceChannel',
                    'serviceUrl' => $baseUrl.'/',
                    'availableLanguage' => 'Dutch',
                ]),
            ]);
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($graph)),
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
        $isTaxi = $this->isTaxiContext($company, $branding, (string) $name);
        $type = $address !== null ? 'LocalBusiness' : 'Organization';

        $node = [
            '@type' => $type,
            '@id' => $orgId,
            'name' => $this->cleanText($name),
            'url' => $website,
            'description' => $description !== null ? $this->cleanText($description) : null,
            'logo' => $logo,
            'image' => $logo,
            'email' => $email,
            'telephone' => $phone ? $this->cleanText($phone) : null,
            'knowsAbout' => $isTaxi ? ['Taxi', 'Taxivervoer', 'Taxi boeken', 'Taxi bestellen'] : null,
            'areaServed' => $isTaxi ? $this->compactNode([
                '@type' => 'Country',
                'name' => 'Netherlands',
            ]) : null,
            'additionalType' => $isTaxi ? 'https://schema.org/TaxiService' : null,
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
     * @param  array<string, mixed>  $branding
     */
    private function isTaxiContext(?Company $company, array $branding, string $nameHint = ''): bool
    {
        if ($company !== null) {
            try {
                if ($company->hasTaxiModule()) {
                    return true;
                }
            } catch (\Throwable) {
                // Module-relatie niet beschikbaar in unit tests zonder DB.
            }
        }

        $blob = mb_strtolower(implode(' ', array_filter([
            $nameHint,
            (string) ($branding['site_name'] ?? ''),
            (string) ($branding['logo_alt'] ?? ''),
            (string) ($branding['site_description'] ?? ''),
            (string) ($company?->name ?? ''),
        ])));

        return str_contains($blob, 'taxi');
    }

    /**
     * @param  array<string, mixed>  $branding
     * @param  array<string, mixed>|null  $homeSections
     */
    private function primaryImage(array $branding, ?array $homeSections): ?array
    {
        $url = $this->firstNonEmpty([
            $branding['logo_url'] ?? null,
            $this->footerLogo($homeSections),
        ]);
        if ($url === null) {
            return null;
        }

        return $this->compactNode([
            '@type' => 'ImageObject',
            'url' => $url,
        ]);
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
