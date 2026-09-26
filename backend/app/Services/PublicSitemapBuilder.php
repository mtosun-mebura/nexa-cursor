<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Bouwt een Google-compatibele XML-sitemap voor de huidige host (platform of tenant).
 */
class PublicSitemapBuilder
{
    public function __construct(
        protected WebsiteBuilderService $websiteBuilder,
    ) {}

    /**
     * @return list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    public function urls(?int $companyId = null): array
    {
        $companyId ??= GeneralSetting::resolveScopeCompanyId();
        $byLoc = [];

        foreach ($this->staticPublicEntries($companyId) as $entry) {
            $byLoc[$entry['loc']] = $entry;
        }

        try {
            $pages = $this->websiteBuilder
                ->loadAllPagesForAdminIndex($companyId, $companyId !== null)
                ->filter(fn ($page) => $page instanceof WebsitePage && (bool) $page->is_active);

            foreach ($pages as $page) {
                $loc = $this->publicUrlForPage($page);
                if ($loc === null) {
                    continue;
                }
                $byLoc[$loc] = [
                    'loc' => $loc,
                    'lastmod' => optional($page->updated_at)?->toAtomString(),
                    'changefreq' => $this->changeFreqForSlug((string) $page->slug),
                    'priority' => $this->priorityForSlug((string) $page->slug),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Sitemap: websitepagina\'s konden niet worden geladen', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($byLoc === []) {
            $byLoc[url('/')] = [
                'loc' => url('/'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];
        }

        $urls = array_values($byLoc);
        usort($urls, function (array $a, array $b): int {
            $prio = ((float) $b['priority']) <=> ((float) $a['priority']);
            if ($prio !== 0) {
                return $prio;
            }

            return strcmp($a['loc'], $b['loc']);
        });

        return $urls;
    }

    public function toXml(?int $companyId = null): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->urls($companyId) as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$this->escape((string) $entry['loc']).'</loc>';
            if (! empty($entry['lastmod'])) {
                $lines[] = '    <lastmod>'.$this->escape((string) $entry['lastmod']).'</lastmod>';
            }
            $lines[] = '    <changefreq>'.$this->escape((string) $entry['changefreq']).'</changefreq>';
            $lines[] = '    <priority>'.$this->escape((string) $entry['priority']).'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    private function staticPublicEntries(?int $companyId): array
    {
        $entries = [];
        $candidates = [
            ['route' => 'home', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['route' => 'starten', 'changefreq' => 'weekly', 'priority' => '0.8', 'central_only' => true],
            ['route' => 'about', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['route' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.7'],
            // Centrale NEXA-/Skillmatching-pagina's horen niet in de tenant-sitemap
            ['route' => 'help', 'changefreq' => 'monthly', 'priority' => '0.4', 'central_only' => true],
            ['route' => 'privacy', 'changefreq' => 'yearly', 'priority' => '0.3', 'central_only' => true],
            ['route' => 'terms', 'changefreq' => 'yearly', 'priority' => '0.3', 'central_only' => true],
            ['route' => 'disclaimer', 'changefreq' => 'yearly', 'priority' => '0.2', 'central_only' => true],
        ];

        $isCentral = $companyId === null;

        foreach ($candidates as $candidate) {
            if (! empty($candidate['central_only']) && ! $isCentral) {
                continue;
            }
            $name = $candidate['route'];
            if (! Route::has($name)) {
                continue;
            }
            if ($name === 'about' && ! $this->websiteBuilder->getAboutPage()) {
                continue;
            }
            if ($name === 'contact' && ! $this->websiteBuilder->getContactPage()) {
                continue;
            }
            try {
                $loc = route($name, [], true);
            } catch (\Throwable) {
                continue;
            }
            $entries[] = [
                'loc' => $loc,
                'lastmod' => now()->toAtomString(),
                'changefreq' => $candidate['changefreq'],
                'priority' => $candidate['priority'],
            ];
        }

        return $entries;
    }

    private function publicUrlForPage(WebsitePage $page): ?string
    {
        if (WebsitePage::isCentralMarketingWelcomeSlug((string) $page->slug)) {
            return null;
        }

        $slug = strtolower(trim((string) $page->slug));
        if ($slug === '' || $slug === 'home') {
            return url('/');
        }

        // Skip slugs that mirror dedicated routes already in the static list
        if (in_array($slug, ['privacy', 'voorwaarden', 'terms', 'disclaimer', 'contact', 'about', 'help', 'starten'], true)) {
            if (Route::has($slug === 'voorwaarden' ? 'terms' : $slug) || Route::has($slug)) {
                return null;
            }
        }

        return url('/'.$slug);
    }

    private function changeFreqForSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || $slug === 'home') {
            return 'daily';
        }
        if (in_array($slug, ['privacy', 'voorwaarden', 'terms', 'disclaimer'], true)) {
            return 'yearly';
        }

        return 'weekly';
    }

    private function priorityForSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || $slug === 'home') {
            return '1.0';
        }
        if (in_array($slug, ['contact', 'prijzen', 'pricing', 'starten'], true)) {
            return '0.8';
        }
        if (in_array($slug, ['privacy', 'voorwaarden', 'terms', 'disclaimer'], true)) {
            return '0.3';
        }

        return '0.6';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
