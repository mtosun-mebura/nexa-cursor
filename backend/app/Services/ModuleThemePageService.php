<?php

namespace App\Services;

use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\WebsitePage;

/**
 * Bij wisselen van thema voor een module: huidige pagina's op inactief,
 * pagina's van het gekozen thema weer actief; ontbrekende home-pagina aanmaken.
 * Gebruikt de module-DB wanneer de module een eigen database heeft.
 */
class ModuleThemePageService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    private function pageQuery(Module $module): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->moduleDb->supportsModuleDatabases()) {
            return WebsitePage::on($this->moduleDb->getModuleConnectionName($module->name));
        }
        return WebsitePage::query()->whereRaw('LOWER(module_name) = ?', [strtolower((string) $module->name)]);
    }

    /**
     * Na themawissel: oude-thema-pagina's op inactief, nieuwe op actief,
     * en zorg dat er een home-pagina voor het nieuwe thema is (zelfde opzet als referentie).
     */
    public function syncPagesForModuleThemeChange(Module $module, ?int $oldThemeId, ?int $newThemeId): void
    {
        $query = $this->pageQuery($module);
        if ($oldThemeId !== null) {
            (clone $query)->where('frontend_theme_id', $oldThemeId)
                ->whereNull('company_id')
                ->update(['is_active' => false]);
        }
        if ($newThemeId === null) {
            return;
        }
        $theme = FrontendTheme::find($newThemeId);
        if (!$theme) {
            return;
        }
        (clone $query)->where('frontend_theme_id', $newThemeId)
            ->whereNull('company_id')
            ->update(['is_active' => true]);
        $this->ensureHomePageForModuleTheme($module, $theme);
    }

    /**
     * Zorg dat er een home-pagina bestaat voor deze module + thema.
     * Zo niet: aanmaken met home_sections uit een referentie (bestaande home van de module of defaults).
     */
    public function ensureHomePageForModuleTheme(Module $module, FrontendTheme $theme): WebsitePage
    {
        $query = $this->pageQuery($module);
        $existing = (clone $query)->where('frontend_theme_id', $theme->id)->where('page_type', 'home')->first();
        if ($existing) {
            return $existing;
        }
        $referenceHome = (clone $query)->where('page_type', 'home')->orderBy('sort_order')->orderBy('id')->first();
        $homeSections = $referenceHome && $referenceHome->home_sections
            ? $referenceHome->home_sections
            : WebsitePage::defaultHomeSectionsForTheme($theme->slug);
        $slug = $this->uniqueHomeSlugForModuleTheme($module, $theme, $query);
        $conn = $this->moduleDb->supportsModuleDatabases()
            ? $this->moduleDb->getModuleConnectionName($module->name)
            : null;
        $companyId = $referenceHome?->company_id !== null && $referenceHome->company_id !== ''
            ? (int) $referenceHome->company_id
            : null;
        $data = [
            'slug' => $slug,
            'title' => 'Home',
            'content' => $referenceHome?->content,
            'meta_description' => $referenceHome?->meta_description,
            'home_sections' => $homeSections,
            'page_type' => 'home',
            'module_name' => $module->name,
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => WebsitePage::nextSortOrderForTenant($conn, $companyId),
        ];
        return $conn !== null ? WebsitePage::on($conn)->create($data) : WebsitePage::create($data);
    }

    private function uniqueHomeSlugForModuleTheme(Module $module, FrontendTheme $theme, \Illuminate\Database\Eloquent\Builder $query): string
    {
        $slug = 'home';
        if (!(clone $query)->where('slug', $slug)->where('frontend_theme_id', $theme->id)->exists()) {
            return $slug;
        }
        $n = 1;
        while ((clone $query)->where('slug', 'home-' . $n)->where('frontend_theme_id', $theme->id)->exists()) {
            $n++;
        }
        return 'home-' . $n;
    }
}
