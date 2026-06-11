<?php

namespace App\Http\Controllers;

use App\Models\WebsitePage;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected WebsiteBuilderService $websiteBuilder,
    ) {}

    public function index(): Response
    {
        $companyId = \App\Models\GeneralSetting::resolveScopeCompanyId();
        $pages = $this->websiteBuilder->loadAllPagesForAdminIndex($companyId, $companyId !== null)
            ->filter(fn ($page) => $page instanceof WebsitePage && $page->is_active);

        $urls = [];
        foreach ($pages as $page) {
            $loc = $this->publicUrlForPage($page);
            if ($loc !== null) {
                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => optional($page->updated_at)->toAtomString(),
                ];
            }
        }

        if ($urls === []) {
            $urls[] = ['loc' => url('/'), 'lastmod' => now()->toAtomString()];
        }

        return response()
            ->view('frontend.sitemap.index', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
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

        return url('/'.$slug);
    }
}
