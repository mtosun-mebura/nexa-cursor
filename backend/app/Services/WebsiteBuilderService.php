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
        $this->moduleDb = $this->moduleDb ?? (app()->bound(ModuleDatabaseService::class) ? app(ModuleDatabaseService::class) : null);
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
            $logoUrl = '/storage/' . ltrim($logoPath, '/');
        }
        $logoDarkUrl = null;
        $logoMode = GeneralSetting::get('logo_mode', 'single');
        if ($logoMode === 'light_dark' && $logoUrl) {
            $logoDarkPath = GeneralSetting::get('logo_dark');
            if ($logoDarkPath && Storage::disk('public')->exists($logoDarkPath)) {
                $logoDarkUrl = '/storage/' . ltrim($logoDarkPath, '/');
            }
        }

        $faviconPath = GeneralSetting::get('favicon');
        $faviconUrl = null;
        if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
            $faviconUrl = '/storage/' . ltrim($faviconPath, '/');
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
     * Module waarvan de applicatienaam/omschrijving gebruikt wordt voor branding (meta, header, logo alt).
     * Eerst de module die het actieve thema gebruikt, anders de eerste actieve module.
     * Publiek voor o.a. AdminMiddleware (automatisch tenant kiezen).
     */
    public function getBrandingModule(): ?Module
    {
        $activeTheme = $this->getActiveTheme();
        if ($activeTheme) {
            $module = Module::where('frontend_theme_id', $activeTheme->id)->where('active', true)->first();
            if ($module) {
                return $module;
            }
        }
        return Module::where('active', true)->first();
    }

    public function getActiveTheme(): ?FrontendTheme
    {
        return FrontendTheme::getActive();
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

    public function getAboutPage(): ?WebsitePage
    {
        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'about')
            ->orderBy('sort_order')
            ->first();
    }

    public function getContactPage(): ?WebsitePage
    {
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
     * Pagina's voor het menu: core uit hoofddatabase + actieve module-pagina's uit module-DB, gesorteerd op sort_order.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getActiveMenuPages(): Collection
    {
        $activeModules = $this->moduleManager->getActiveModules();
        $activeModuleNames = array_map(fn ($m) => $m->getName(), $activeModules);
        $pages = $this->websitePageQuery(null)->active()
            ->whereNull('module_name')
            ->orderBy('sort_order')
            ->get();
        if ($this->moduleDb && $this->moduleDb->supportsModuleDatabases()) {
            foreach ($activeModules as $mod) {
                $name = $mod->getName();
                if ($name === null || $name === '') {
                    continue;
                }
                $fromModule = $this->websitePageQuery($name)->active()
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->get();
                $pages = $pages->merge($fromModule);
            }
        } else {
            $fromDefault = WebsitePage::active()
                ->whereNotNull('module_name')
                ->whereIn('module_name', $activeModuleNames)
                ->orderBy('sort_order')
                ->get();
            $pages = $pages->merge($fromDefault);
        }
        return $pages->sortBy('sort_order')->values();
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
