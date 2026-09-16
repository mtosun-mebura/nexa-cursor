<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use App\Services\GoogleSeoSettingsService;
use App\Services\WebsiteBuilderService;
use Illuminate\View\View;

class LegalPagesController extends Controller
{
    public function __construct(
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    public function terms(): View
    {
        return view('frontend.pages.terms', $this->layoutData());
    }

    public function disclaimer(): View
    {
        return view('frontend.pages.disclaimer', $this->layoutData());
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutData(): array
    {
        $page = $this->websiteBuilder->getCentralMarketingWelcomePage()
            ?? $this->websiteBuilder->getHomePage();

        if ($page instanceof WebsitePage) {
            $theme = $this->websiteBuilder->getThemeForPage($page);
            $themeSlug = $theme ? $theme->slug : 'modern';
            $homePage = FrontendTheme::usesHomeSections($themeSlug)
                ? ($this->websiteBuilder->getHomePage() ?? $page)
                : $page;
            $homeSections = $this->websiteBuilder->applyInheritedHomeFooter(
                $homePage->getHomeSections(),
                $page
            );
            $companyId = $page->company_id ? (int) $page->company_id : GeneralSetting::resolveScopeCompanyId();

            return [
                'page' => $page,
                'theme' => $theme,
                'themeSlug' => $themeSlug,
                'themeSettings' => $theme ? $theme->getSettings($page->company) : [],
                'menuPages' => $this->websiteBuilder->getActiveMenuPagesForWebsitePage($page),
                'branding' => $this->websiteBuilder->getSiteBrandingForWebsitePage($page),
                'homeSections' => $homeSections,
                'loadAtomV2Styles' => $themeSlug === 'atom-v2',
                'googleMapsApiKey' => $this->websiteBuilder->resolveGoogleMapsApiKeyForPage($page),
                'googleMapsMapId' => $this->websiteBuilder->resolveGoogleMapsMapIdForPage($page),
                'whatsappWidget' => $this->websiteBuilder->resolveWhatsappWidgetForPage($page),
                'structuredDataGraph' => [],
                'seoTracking' => app(GoogleSeoSettingsService::class)->trackingConfigForCompany($companyId),
            ];
        }

        return [
            'page' => null,
            'theme' => null,
            'themeSlug' => 'modern',
            'themeSettings' => [],
            'menuPages' => $this->websiteBuilder->getActiveMenuPages(),
            'branding' => $this->websiteBuilder->getSiteBranding(),
            'homeSections' => $this->websiteBuilder->getHomeFooterSections(),
            'loadAtomV2Styles' => false,
            'googleMapsApiKey' => '',
            'googleMapsMapId' => '',
            'whatsappWidget' => ['enabled' => false, 'phone' => '', 'message' => ''],
            'structuredDataGraph' => [],
            'seoTracking' => [],
        ];
    }
}
