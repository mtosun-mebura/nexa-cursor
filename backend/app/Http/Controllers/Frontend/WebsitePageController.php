<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\WebsitePage;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Toont website-pagina's (website builder) met het actieve thema.
 * Gebruikt wanneer er een actieve WebsitePage is voor home, about, contact of een slug.
 */
class WebsitePageController extends Controller
{
    /** Reserverde slugs die niet als custom pagina mogen worden getoond. */
    private const RESERVED_SLUGS = [
        'about', 'contact', 'home', 'login', 'register', 'logout',
        'jobs', 'dashboard', 'profile', 'matches', 'agenda', 'help', 'privacy', 'terms',
        'vacature-matching', 'favorites', 'verify-email', 'admin', 'storage', 'file',
        'demo1', 'demo2', 'demo3', 'demo4', 'demo5', 'demo6', 'demo7', 'demo8', 'demo9', 'demo10',
    ];

    public function __construct(
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * Toon de geconfigureerde homepagina (website builder).
     */
    public function showHome(Request $request): View
    {
        $page = $this->websiteBuilder->getHomePage();
        if (!$page) {
            abort(404);
        }
        return $this->renderPage($page);
    }

    /**
     * Toon de geconfigureerde about-pagina.
     */
    public function showAbout(): View
    {
        $page = $this->websiteBuilder->getAboutPage();
        if (!$page) {
            abort(404);
        }
        return $this->renderPage($page);
    }

    /**
     * Toon de geconfigureerde contactpagina (met eventueel formulier).
     */
    public function showContact(): View
    {
        $page = $this->websiteBuilder->getContactPage();
        if (!$page) {
            abort(404);
        }
        return $this->renderPage($page, true);
    }

    /**
     * Toon een pagina op basis van slug (custom of module).
     */
    public function showBySlug(string $slug): View
    {
        if (in_array(strtolower($slug), self::RESERVED_SLUGS, true)) {
            abort(404);
        }
        $page = $this->websiteBuilder->getPageBySlug($slug);
        if (!$page) {
            abort(404);
        }
        return $this->renderPage($page);
    }

    /**
     * Render een WebsitePage met het actieve thema-layout.
     *
     * @param bool $showContactForm Of het contactformulier onder de content getoond moet worden (voor page_type contact)
     */
    protected function renderPage(WebsitePage $page, bool $showContactForm = false): View
    {
        $theme = $this->websiteBuilder->getThemeForPage($page);
        $menuPages = $this->websiteBuilder->getActiveMenuPages();
        $branding = $this->websiteBuilder->getSiteBranding();

        $themeSlug = $theme ? $theme->slug : 'modern';
        $themeSettings = $theme ? $theme->getSettings() : [];

        $jobs = collect();
        $isHomePage = $page->page_type === 'home' || $page->slug === 'home';
        if ($isHomePage) {
            $rotationKey = floor(now()->timestamp / (2 * 3600));
            $jobs = Cache::remember("home_jobs_rotation_{$rotationKey}", 7200, function () {
                return Vacancy::with(['company', 'category'])
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('published_at', '<=', now())
                                ->orWhereNull('published_at')
                                ->orWhereNull('publication_date');
                        });
                    })
                    ->orderBy('published_at', 'desc')
                    ->limit(6)
                    ->get();
            });
        }

        $themeHasHomeSections = in_array($themeSlug, ['modern', 'atom-v2', 'nextly-template', 'next-landing-vpn'], true);
        $useThemeHomeLayout = $themeHasHomeSections && (
            !empty($page->home_sections) || $page->page_type === 'home' || $page->slug === 'home'
        );
        $homeSections = $useThemeHomeLayout ? $page->getHomeSections() : [];
        // Atom v2: laad thema-styles op alle paginatypes zodat about/contact/custom dezelfde weergave hebben als home
        $loadAtomV2Styles = ($themeSlug === 'atom-v2');

        return view('frontend.website.page', [
            'page' => $page,
            'theme' => $theme,
            'themeSlug' => $themeSlug,
            'themeSettings' => $themeSettings,
            'menuPages' => $menuPages,
            'branding' => $branding,
            'showContactForm' => $showContactForm && $page->page_type === 'contact',
            'jobs' => $jobs,
            'useModernHomeLayout' => $useThemeHomeLayout,
            'homeSections' => $homeSections,
            'loadAtomV2Styles' => $loadAtomV2Styles,
        ]);
    }
}
