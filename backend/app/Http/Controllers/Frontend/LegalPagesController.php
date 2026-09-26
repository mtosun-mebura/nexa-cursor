<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use App\Services\GoogleSeoSettingsService;
use App\Services\WebsiteBuilderService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function starten(): View
    {
        return view('frontend.pages.starten', array_merge($this->layoutData(), [
            'videoUrl' => route('starten.video', [
                'v' => is_file(public_path('videos/nexa-starten.mp4'))
                    ? filemtime(public_path('videos/nexa-starten.mp4'))
                    : time(),
            ]),
            'adminLoginUrl' => \App\Services\TenantWelcomeEmailTemplateService::ADMIN_LOGIN_URL,
            'handleidingUrl' => \App\Services\TenantWelcomeEmailTemplateService::HANDLEIDING_URL,
            'chapters' => [
                ['time' => 0, 'title' => 'Welkom', 'summary' => 'De echte route, van mail tot de apps.'],
                ['time' => 16, 'title' => 'Welkomstmail', 'summary' => 'Klik op Open de admin. Geen wachtwoord in de mail.'],
                ['time' => 36, 'title' => 'Eerste keer inloggen', 'summary' => 'Code aanvragen, invoeren en zelf een wachtwoord kiezen.'],
                ['time' => 55, 'title' => 'Inlogcode aanvragen', 'summary' => 'Vul je e-mail in en vraag de eenmalige code aan.'],
                ['time' => 84, 'title' => 'Handleiding', 'summary' => 'Na het inloggen land je hier, in het adminpaneel.'],
                ['time' => 103, 'title' => 'Dark Mode', 'summary' => 'Zet Dark Mode aan via je profielfoto rechtsboven.'],
                ['time' => 150, 'title' => 'Dashboard en menu', 'summary' => 'Startscherm, menuitems en wat er bij jouw pakket hoort.'],
                ['time' => 185, 'title' => 'Bedrijf en gebruikers', 'summary' => 'Gegevens checken en collega’s aanmaken.'],
                ['time' => 240, 'title' => 'Chauffeur-app', 'summary' => 'Inloggen, ritten, betalen, factuur, navigatie en profiel op je telefoon.'],
                ['time' => 423, 'title' => 'Profiel', 'summary' => 'Gegevens, themakleur, ritgeluid, handleiding en uitloggen.'],
                ['time' => 451, 'title' => 'Contract-app', 'summary' => 'Inloggen, vandaag, afmelden, planning en de ophaalroute op de kaart.'],
            ],
        ]));
    }

    public function startenVideo(): BinaryFileResponse
    {
        $path = public_path('videos/nexa-starten.mp4');
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutData(): array
    {
        $seoNoindex = ! \App\Support\Tenancy\CentralDomains::isCentral((string) request()->getHost());

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
                'seoNoindex' => $seoNoindex,
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
            'seoNoindex' => $seoNoindex,
        ];
    }
}
