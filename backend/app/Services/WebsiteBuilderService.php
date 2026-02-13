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
     * Logo-, favicon- en sitenaam voor de website layout (zelfde logica als coming soon).
     *
     * @return array{logo_url: ?string, favicon_url: ?string, site_name: string}
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

        return [
            'logo_url' => $logoUrl,
            'favicon_url' => $faviconUrl,
            'site_name' => GeneralSetting::get('site_name', config('app.name', 'Nexa')),
            'dashboard_link_label' => GeneralSetting::get('dashboard_link_label', 'Mijn Nexa'),
        ];
    }

    public function getActiveTheme(): ?FrontendTheme
    {
        return FrontendTheme::getActive();
    }

    public function getHomePage(): ?WebsitePage
    {
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
