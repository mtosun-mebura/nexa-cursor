<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\WebsitePage;
use App\Services\GoogleReviewsService;
use App\Services\GoogleSeoSettingsService;
use App\Services\ModuleDatabaseService;
use App\Services\WebsiteBuilderService;
use App\Services\WebsiteStructuredDataService;
use App\Support\ModuleSchemaAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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
        'marketing',
        'demo1', 'demo2', 'demo3', 'demo4', 'demo5', 'demo6', 'demo7', 'demo8', 'demo9', 'demo10',
        \App\Models\WebsitePage::CENTRAL_WELCOME_SLUG,
    ];

    public function __construct(
        protected WebsiteBuilderService $websiteBuilder,
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * Toon de geconfigureerde homepagina (website builder).
     */
    public function showHome(Request $request): View
    {
        $page = $this->websiteBuilder->getHomePage();
        if (! $page) {
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
        if (! $page) {
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
        if (! $page) {
            abort(404);
        }

        return $this->renderPage($page, true);
    }

    /**
     * Toon een pagina op basis van slug (custom of module).
     */
    /**
     * Centrale NEXA-welkom (website builder), alleen bedoeld voor route / op niet-tenant hosts.
     */
    public function showCentralWelcome(WebsitePage $page): View
    {
        return $this->renderPage($page);
    }

    public function showBySlug(string $slug): View
    {
        if (in_array(strtolower($slug), self::RESERVED_SLUGS, true)) {
            abort(404);
        }
        $page = $this->websiteBuilder->getPageBySlug($slug);
        if (! $page) {
            abort(404);
        }

        return $this->renderPage($page);
    }

    /**
     * Render een WebsitePage met het actieve thema-layout.
     *
     * @param  bool  $showContactForm  Of het contactformulier onder de content getoond moet worden (voor page_type contact)
     */
    protected function renderPage(WebsitePage $page, bool $showContactForm = false): View
    {
        $theme = $this->websiteBuilder->getThemeForPage($page);
        $menuPages = $this->websiteBuilder->getActiveMenuPagesForWebsitePage($page);
        $branding = $this->websiteBuilder->getSiteBrandingForWebsitePage($page);

        $themeSlug = $theme ? $theme->slug : 'modern';
        $themeSettings = $theme ? $theme->getSettings($page->company) : [];

        $jobs = collect();
        $isHomePage = $page->page_type === 'home' || $page->slug === 'home';
        $moduleName = $page->module_name ?? null;
        $isSkillmatchingModule = $moduleName !== null && strtolower((string) $moduleName) === 'skillmatching';
        if ($isHomePage && $isSkillmatchingModule) {
            $rotationKey = floor(now()->timestamp / (2 * 3600));
            $jobs = Cache::remember("home_jobs_rotation_{$rotationKey}", 7200, function () {
                if (! ModuleSchemaAvailability::vacanciesTableExists()) {
                    return collect();
                }

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

        $themeHasHomeSections = \App\Models\FrontendTheme::usesHomeSections($themeSlug);
        $isRenderingHome = $this->websiteBuilder->isSiteHomePage($page);
        $useThemeHomeLayout = $themeHasHomeSections && (
            ! empty($page->home_sections) || $isRenderingHome
        );
        // Gebruik secties van de huidige pagina als die eigen home_sections heeft (o.a. modulepagina's);
        // anders bij home de home-secties, anders secties van de hoofdpagina (footer e.d.).
        $homePage = $themeHasHomeSections ? $this->websiteBuilder->getHomePage() : null;
        $useCurrentPageSections = $useThemeHomeLayout && ($isRenderingHome || ! empty($page->home_sections));
        $homeSections = $useCurrentPageSections
            ? $page->getHomeSections()
            : ($homePage ? $homePage->getHomeSections() : []);
        $homeSections = $this->websiteBuilder->applyInheritedHomeFooter($homeSections, $page);
        $templateConnection = null;
        $moduleName = $page->module_name;
        if ($moduleName && $this->moduleDb->supportsModuleDatabases()) {
            $connName = $this->moduleDb->getModuleConnectionName($moduleName);
            if (Config::has("database.connections.{$connName}")) {
                $templateConnection = $connName;
            }
        }
        $emailTemplateBySectionKey = WebsitePage::emailTemplatesBySectionKeyForHomeSections($homeSections, $templateConnection);
        // Atom v2: laad thema-styles op alle paginatypes zodat about/contact/custom dezelfde weergave hebben als home
        $loadAtomV2Styles = ($themeSlug === 'atom-v2');
        $googleMapsApiKey = $this->websiteBuilder->resolveGoogleMapsApiKeyForPage($page);
        $googleMapsMapId = $this->websiteBuilder->resolveGoogleMapsMapIdForPage($page);
        $whatsappWidget = $this->websiteBuilder->resolveWhatsappWidgetForPage($page);

        $reviewsCompanyId = GoogleReviewsService::resolveCompanyIdForWebsitePage($page);
        $googleReviews = $useThemeHomeLayout
            ? app(GoogleReviewsService::class)->getReviews($reviewsCompanyId)
            : [];

        try {
            $infoRequestFormFields = \App\Models\InfoRequestFormField::ordered()->get();
        } catch (\Throwable $e) {
            $infoRequestFormFields = collect();
        }

        $structuredDataGraph = app(WebsiteStructuredDataService::class)->buildForRenderedPage(
            $page,
            $branding,
            is_array($homeSections) ? $homeSections : null,
        );

        $seoCompanyId = $page->company_id ? (int) $page->company_id : \App\Models\GeneralSetting::resolveScopeCompanyId();
        $seoTracking = app(GoogleSeoSettingsService::class)->trackingConfigForCompany($seoCompanyId);

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
            'emailTemplateBySectionKey' => $emailTemplateBySectionKey,
            'infoRequestFormFields' => $infoRequestFormFields,
            'loadAtomV2Styles' => $loadAtomV2Styles,
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsMapId' => $googleMapsMapId,
            'googleReviews' => $googleReviews,
            'whatsappWidget' => $whatsappWidget,
            'structuredDataGraph' => $structuredDataGraph,
            'seoTracking' => $seoTracking,
        ]);
    }
}
