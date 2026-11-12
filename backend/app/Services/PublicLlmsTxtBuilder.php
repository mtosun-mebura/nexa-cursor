<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Support\WebsiteSeoMeta;

/**
 * Bouwt llms.txt voor de huidige host (platform of tenant).
 *
 * @see https://llmstxt.org/
 */
class PublicLlmsTxtBuilder
{
    public function __construct(
        protected PublicSitemapBuilder $sitemapBuilder,
        protected WebsiteBuilderService $websiteBuilder,
    ) {}

    public function toText(?int $companyId = null): string
    {
        $companyId ??= GeneralSetting::resolveScopeCompanyId();
        $branding = $this->websiteBuilder->getSiteBranding(
            forCompanyId: $companyId,
        );
        $name = WebsiteSeoMeta::brandLabel($branding);
        if ($name === '') {
            $name = (string) config('app.name', 'Nexa');
        }

        $description = WebsiteSeoMeta::resolveDescription(
            null,
            null,
            $branding['site_description'] ?? null,
            $name,
        );

        $base = rtrim(url('/'), '/');
        $isTaxi = $this->looksLikeTaxiBrand($name, (string) ($branding['site_description'] ?? ''));

        $lines = [
            '# '.$name,
            '',
            '> '.$description,
            '',
        ];

        if ($isTaxi) {
            $lines[] = '## Taxi';
            $lines[] = '';
            $lines[] = '- Boek of bestel een taxi online via '.$base.'/';
            $lines[] = '- '.$name.' biedt taxivervoer: boeken, ritten en contact via de website.';
            $lines[] = '- Zoekwoorden: Taxi, taxi bestellen, taxi boeken, taxicentrale.';
            $lines[] = '';
        }

        $lines[] = '## Pagina\'s';
        $lines[] = '';

        $seen = [];
        foreach ($this->sitemapBuilder->urls($companyId) as $entry) {
            $loc = (string) ($entry['loc'] ?? '');
            if ($loc === '' || isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $label = $path === '/' || $path === ''
                ? 'Home'
                : $this->labelFromPath((string) $path);
            $lines[] = '- ['.$label.']('.$loc.')';
        }

        $lines[] = '';
        $lines[] = '## Optioneel';
        $lines[] = '';
        $lines[] = '- Sitemap: '.$base.'/sitemap.xml';
        $lines[] = '- Robots: '.$base.'/robots.txt';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function looksLikeTaxiBrand(string $name, string $description): bool
    {
        $blob = mb_strtolower($name.' '.$description);

        return str_contains($blob, 'taxi');
    }

    private function labelFromPath(string $path): string
    {
        $slug = trim($path, '/');
        if ($slug === '') {
            return 'Home';
        }
        $parts = explode('/', $slug);
        $last = (string) end($parts);
        $label = str_replace(['-', '_'], ' ', $last);

        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }
}
