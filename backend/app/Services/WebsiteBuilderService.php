<?php

namespace App\Services;

use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\WebsitePage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class WebsiteBuilderService
{
    public function __construct(
        protected ModuleManager $moduleManager
    ) {}

    /**
     * Bepaalt het thema voor een website-pagina: pagina-eigen thema, anders thema van de module, anders standaardthema.
     */
    public function getThemeForPage(WebsitePage $page): ?FrontendTheme
    {
        if ($page->frontend_theme_id) {
            $theme = FrontendTheme::find($page->frontend_theme_id);
            if ($theme) {
                return $theme;
            }
        }
        if ($page->module_name) {
            $module = Module::where('name', $page->module_name)->first();
            if ($module && $module->frontend_theme_id) {
                $theme = FrontendTheme::find($module->frontend_theme_id);
                if ($theme) {
                    return $theme;
                }
            }
        }
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
     */
    protected function getBrandingModule(): ?Module
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
     * Homepagina voor de startpagina: actieve home die het actieve thema gebruikt.
     * Als meerdere modules hetzelfde thema gebruiken: voorkeur voor de branding-module's home.
     * Anders de kern-home (geen module).
     */
    public function getHomePage(): ?WebsitePage
    {
        $activeTheme = $this->getActiveTheme();
        if ($activeTheme) {
            $candidates = WebsitePage::active()
                ->where('page_type', 'home')
                ->where('frontend_theme_id', $activeTheme->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            if ($candidates->isNotEmpty()) {
                $brandingModule = $this->getBrandingModule();
                if ($brandingModule) {
                    $preferred = $candidates->first(fn (WebsitePage $p) => $p->module_name !== null && strcasecmp($p->module_name, $brandingModule->name) === 0);
                    if ($preferred) {
                        return $preferred;
                    }
                }
                return $candidates->first();
            }
        }
        return WebsitePage::active()
            ->forModule(null)
            ->where('page_type', 'home')
            ->orderBy('sort_order')
            ->first();
    }

    public function getAboutPage(): ?WebsitePage
    {
        return WebsitePage::active()
            ->forModule(null)
            ->where('page_type', 'about')
            ->orderBy('sort_order')
            ->first();
    }

    public function getContactPage(): ?WebsitePage
    {
        return WebsitePage::active()
            ->forModule(null)
            ->where('page_type', 'contact')
            ->orderBy('sort_order')
            ->first();
    }

    public function getPageBySlug(string $slug): ?WebsitePage
    {
        $page = WebsitePage::active()
            ->where('slug', $slug)
            ->first();

        if (!$page) {
            return null;
        }

        if ($page->module_name !== null && !$this->moduleManager->isActive($page->module_name)) {
            return null;
        }

        return $page;
    }

    /**
     * Pagina's voor het menu (core + custom + actieve module-pagina's), gesorteerd op sort_order.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getActiveMenuPages(): Collection
    {
        $activeModules = $this->moduleManager->getActiveModules();
        $activeModuleNames = array_map(fn ($m) => $m->getName(), $activeModules);

        return WebsitePage::active()
            ->orderBy('sort_order')
            ->get()
            ->filter(function (WebsitePage $page) use ($activeModuleNames) {
                if ($page->module_name === null) {
                    return true;
                }
                return in_array($page->module_name, $activeModuleNames, true);
            });
    }

    /**
     * Pagina's voor het staging-menu: alleen voor gegeven module, of alle actieve menu-pagina's.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getMenuPagesForStaging(?string $moduleName): Collection
    {
        if ($moduleName !== null && $moduleName !== '') {
            return WebsitePage::active()
                ->forModule($moduleName)
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
