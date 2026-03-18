<?php

namespace App\Services;

use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\WebsitePage;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class WebsiteBuilderService
{
    public function __construct(
        protected ModuleManager $moduleManager,
        protected ?ModuleDatabaseService $moduleDb = null
    ) {
        $this->moduleDb = $this->moduleDb ?? app(ModuleDatabaseService::class);
    }

    /** Query WebsitePage op de juiste connection: module-DB als actieve module die eigen DB heeft, anders default. */
    private function websitePageQuery(?string $forModuleName = null): \Illuminate\Database\Eloquent\Builder
    {
        if ($forModuleName !== null && $this->moduleDb && $this->moduleDb->supportsModuleDatabases()) {
            return WebsitePage::on($this->moduleDb->getModuleConnectionName($forModuleName));
        }
        return WebsitePage::query();
    }

    /**
     * Bepaalt het thema waarmee een website-pagina wordt getoond: altijd het actieve thema.
     * Pagina-inhoud (componenten) blijft gelijk; alleen de styling past zich aan het actieve thema aan.
     */
    public function getThemeForPage(WebsitePage $page): ?FrontendTheme
    {
        return $this->getActiveTheme();
    }

    /**
     * Logo-, favicon-, sitenaam en omschrijving voor de website layout.
     * Sitenaam en omschrijving komen van het actieve module-config (indien ingevuld), anders van algemene instellingen.
     *
     * @return array{logo_url: ?string, favicon_url: ?string, site_name: string, site_description: string, dashboard_link_label: string, dashboard_link_visible: bool}
     */
    public function getSiteBranding(): array
    {
        $logoPath = GeneralSetting::get('logo');
        $logoUrl = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoUrl = $this->publicFileUrl(ltrim($logoPath, '/'));
        }
        $logoDarkUrl = null;
        $logoMode = GeneralSetting::get('logo_mode', 'single');
        if ($logoMode === 'light_dark') {
            $logoDarkPath = GeneralSetting::get('logo_dark');
            if ($logoDarkPath && Storage::disk('public')->exists($logoDarkPath)) {
                $logoDarkUrl = $this->publicFileUrl(ltrim($logoDarkPath, '/'));
            }
        }

        $faviconPath = GeneralSetting::get('favicon');
        $faviconUrl = null;
        if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
            $faviconUrl = $this->publicFileUrl(ltrim($faviconPath, '/'));
        }

        $siteName = GeneralSetting::get('site_name', config('app.name', 'Nexa'));
        $siteDescription = GeneralSetting::get('site_description', '');
        $dashboardLinkLabel = GeneralSetting::get('dashboard_link_label', 'Mijn Nexa');
        $dashboardLinkVisible = GeneralSetting::get('dashboard_link_visible', '1') === '1';
        $brandingModule = $this->getBrandingModule();
        if ($brandingModule) {
            $config = $brandingModule->configuration ?? [];
            if (!empty($config['app_name'])) {
                $siteName = $config['app_name'];
            }
            if (isset($config['app_description']) && (string) $config['app_description'] !== '') {
                $siteDescription = (string) $config['app_description'];
            }
            if (isset($config['dashboard_link_label']) && (string) $config['dashboard_link_label'] !== '') {
                $dashboardLinkLabel = (string) $config['dashboard_link_label'];
            }
            if (isset($config['dashboard_link_visible'])) {
                $dashboardLinkVisible = $config['dashboard_link_visible'] === '1' || $config['dashboard_link_visible'] === true;
            }
        }

        return [
            'logo_url' => $logoUrl,
            'logo_dark_url' => $logoDarkUrl,
            'favicon_url' => $faviconUrl,
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'dashboard_link_label' => $dashboardLinkLabel,
            'dashboard_link_visible' => $dashboardLinkVisible,
        ];
    }

    /**
     * Publieke URL voor een bestand in storage/app/public (bruikbaar voor niet-ingelogde bezoekers).
     * Gebruikt de /file/ route zodat logo/favicon altijd laden.
     */
    public function publicFileUrl(string $path): string
    {
        $path = str_replace(['../', '..'], '', $path);
        $encoded = str_replace('/', '--', trim($path, '/'));
        return url('/file/' . $encoded);
    }

    /**
     * Zet een opgeslagen storage-URL (relatief of volledig) om naar een werkende weergave-URL via /file/.
     * Gebruik overal waar img src of background-image uit de database komt (bv. /storage/vehicles/..., http://.../storage/...).
     */
    public function storageUrlToDisplayUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }
        $u = trim((string) $url);
        $path = null;
        if (str_starts_with($u, '/storage/')) {
            $path = preg_replace('#^/storage/#', '', $u);
        } elseif (preg_match('#^https?://[^/]+/storage/(.+)$#', $u, $m)) {
            $path = preg_replace('/[#?].*$/', '', $m[1]);
        }
        if ($path !== null) {
            return $this->publicFileUrl($path);
        }
        if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
            return $u;
        }
        return url($u);
    }

    /**
     * Module waarvan de website op localhost getoond wordt (branding, menu, home).
     * Prefereert het aan het actieve thema gekoppelde module (active_module_id), anders
     * een actieve module die het actieve thema gebruikt én minstens één actieve home heeft.
     */
    public function getBrandingModule(): ?Module
    {
        $activeTheme = $this->getActiveTheme();
        if (! $activeTheme) {
            return Module::where('active', true)->first();
        }

        // Als voor dit thema een module is vastgezet (bijv. na "Website tonen" in staging), die gebruiken
        if ($activeTheme->active_module_id) {
            $pinned = Module::find($activeTheme->active_module_id);
            if ($pinned && $pinned->active) {
                $name = $pinned->name;
                if ($name !== null && $name !== '') {
                    $hasHome = $this->moduleDb && $this->moduleDb->supportsModuleDatabases()
                        ? $this->websitePageQuery($name)->active()->where('page_type', 'home')->exists()
                        : WebsitePage::query()->where('module_name', $name)->active()->where('page_type', 'home')->exists();
                    if ($hasHome) {
                        return $pinned;
                    }
                }
            }
        }

        $candidates = Module::where('frontend_theme_id', $activeTheme->id)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $module) {
            $name = $module->name;
            if ($name === null || $name === '') {
                continue;
            }
            $hasHome = $this->moduleDb && $this->moduleDb->supportsModuleDatabases()
                ? $this->websitePageQuery($name)->active()->where('page_type', 'home')->exists()
                : WebsitePage::query()->where('module_name', $name)->active()->where('page_type', 'home')->exists();
            if ($hasHome) {
                return $module;
            }
        }

        return $candidates->first() ?? Module::where('active', true)->first();
    }

    public function getActiveTheme(): ?FrontendTheme
    {
        return FrontendTheme::getActive();
    }

    /**
     * Module om te tonen voor staging/startpagina voor dit thema: vastgezette module (active_module_id)
     * als die een home heeft, anders eerste actieve module met thema en home.
     */
    public function getStagingModuleNameForTheme(int $themeId): ?string
    {
        $theme = FrontendTheme::find($themeId);
        if ($theme && $theme->active_module_id) {
            $pinned = Module::find($theme->active_module_id);
            if ($pinned && $pinned->active) {
                $name = $pinned->name;
                if ($name !== null && $name !== '') {
                    $hasHome = $this->moduleDb && $this->moduleDb->supportsModuleDatabases()
                        ? $this->websitePageQuery($name)->active()->where('page_type', 'home')->exists()
                        : WebsitePage::query()->where('module_name', $name)->active()->where('page_type', 'home')->exists();
                    if ($hasHome) {
                        return $name;
                    }
                }
            }
        }
        return $this->getFirstModuleNameWithWebsiteForTheme($themeId);
    }

    /**
     * Eerste actieve module die dit thema gebruikt én minstens één actieve home-pagina heeft.
     * Voor staging-URL "Website tonen" per thema.
     */
    public function getFirstModuleNameWithWebsiteForTheme(int $themeId): ?string
    {
        $candidates = Module::where('frontend_theme_id', $themeId)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $module) {
            $name = $module->name;
            if ($name === null || $name === '') {
                continue;
            }
            $hasHome = $this->moduleDb && $this->moduleDb->supportsModuleDatabases()
                ? $this->websitePageQuery($name)->active()->where('page_type', 'home')->exists()
                : WebsitePage::query()->where('module_name', $name)->active()->where('page_type', 'home')->exists();
            if ($hasHome) {
                return $name;
            }
        }

        return $candidates->first()?->name;
    }

    /**
     * Modulenaam (key) van de actieve frontend-module (branding), voor filtering in o.a. componenten-overzicht.
     */
    public function getActiveModuleName(): ?string
    {
        $module = $this->getBrandingModule();
        return $module ? $module->name : null;
    }

    /**
     * Homepagina voor de startpagina: uit module-DB als actieve module die eigen DB heeft, anders hoofddatabase.
     * Geen filter op thema: er is één homepagina (per module); we tonen die in het actieve thema.
     */
    public function getHomePage(): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $query = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'home')
            ->orderBy('sort_order')
            ->orderBy('id');
        $page = $query->first();
        if ($page !== null) {
            return $page;
        }
        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'home')
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * About-pagina: eerst uit actieve/branding module, anders uit core (module_name null).
     */
    public function getAboutPage(): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'about')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
        if ($page !== null) {
            return $page;
        }
        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'about')
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Contactpagina: eerst uit actieve/branding module (frontend pagina's), anders uit core.
     * Zo overruleert de contactpagina uit de module de statische Nexa Skillmatching contactpagina.
     */
    public function getContactPage(): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'contact')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
        if ($page !== null) {
            return $page;
        }
        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'contact')
            ->orderBy('sort_order')
            ->first();
    }

    public function getPageBySlug(string $slug): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->websitePageQuery($moduleName)->active()
            ->where('slug', $slug)
            ->first();
        if (!$page && $moduleName !== null) {
            $page = $this->websitePageQuery(null)->active()
                ->where('slug', $slug)
                ->first();
        }
        if (!$page) {
            return null;
        }
        if ($page->module_name !== null && !$this->moduleManager->isActive($page->module_name)) {
            return null;
        }
        return $page;
    }

    /**
     * Pagina's voor het hoofdmenu: alle actieve pagina's voor de huidige module in sort_order.
     * Als er een module met eigen DB is: module-pagina's + ontbrekende home/about/contact uit core,
     * zodat o.a. Contact altijd in het menu staat als die alleen in de hoofddatabase bestaat.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getActiveMenuPages(): Collection
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;

        $corePages = $this->websitePageQuery(null)->active()
            ->whereNull('module_name')
            ->showInMenu()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasModuleDb = $moduleName !== null && $moduleName !== ''
            && $this->moduleDb
            && $this->moduleDb->supportsModuleDatabases();

        if (! $hasModuleDb) {
            return $corePages->values();
        }

        $modulePages = $this->websitePageQuery($moduleName)->active()
            ->showInMenu()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $coreTypes = ['home', 'about', 'contact'];
        $moduleHasType = $modulePages->keyBy('page_type');

        foreach ($corePages as $core) {
            if (in_array($core->page_type, $coreTypes, true)
                && ! $moduleHasType->has($core->page_type)) {
                $modulePages->push($core);
            }
        }

        return $modulePages->sortBy(['sort_order', 'id'])->values();
    }

    /**
     * Pagina's voor het staging-menu: alleen voor gegeven module (uit module-DB), of alle actieve menu-pagina's.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getMenuPagesForStaging(?string $moduleName): Collection
    {
        if ($moduleName !== null && $moduleName !== '') {
            return $this->websitePageQuery($moduleName)->active()
                ->showInMenu()
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();
        }
        return $this->getActiveMenuPages();
    }

    /**
     * Contact-emailtemplate ID uit instellingen (GeneralSetting).
     */
    public function getContactEmailTemplateId(): ?int
    {
        $id = GeneralSetting::get('contact_email_template_id');
        if ($id === null || $id === '') {
            return null;
        }
        return (int) $id;
    }
}
