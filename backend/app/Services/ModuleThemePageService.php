<?php

namespace App\Services;

use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\WebsitePage;

/**
 * Bij wisselen van thema voor een module: huidige pagina's op inactief,
 * pagina's van het gekozen thema weer actief; ontbrekende home-pagina aanmaken
 * met dezelfde opzet als de referentie (bestaande home van de module of defaults).
 */
class ModuleThemePageService
{
    /**
     * Na themawissel: oude-thema-pagina's op inactief, nieuwe op actief,
     * en zorg dat er een home-pagina voor het nieuwe thema is (zelfde opzet als referentie).
     */
    public function syncPagesForModuleThemeChange(Module $module, ?int $oldThemeId, ?int $newThemeId): void
    {
        $moduleName = $module->name;
        $moduleNameLower = strtolower((string) $moduleName);

        if ($oldThemeId !== null) {
            WebsitePage::where('frontend_theme_id', $oldThemeId)
                ->whereRaw('LOWER(module_name) = ?', [$moduleNameLower])
                ->update(['is_active' => false]);
        }

        if ($newThemeId === null) {
            return;
        }

        $theme = FrontendTheme::find($newThemeId);
        if (!$theme) {
            return;
        }

        WebsitePage::where('frontend_theme_id', $newThemeId)
            ->whereRaw('LOWER(module_name) = ?', [$moduleNameLower])
            ->update(['is_active' => true]);

        $this->ensureHomePageForModuleTheme($module, $theme);
    }

    /**
     * Zorg dat er een home-pagina bestaat voor deze module + thema.
     * Zo niet: aanmaken met home_sections uit een referentie (bestaande home van de module of defaults).
     */
    public function ensureHomePageForModuleTheme(Module $module, FrontendTheme $theme): WebsitePage
    {
        $moduleNameLower = strtolower((string) $module->name);
        $existing = WebsitePage::whereRaw('LOWER(module_name) = ?', [$moduleNameLower])
            ->where('frontend_theme_id', $theme->id)
            ->where('page_type', 'home')
            ->first();

        if ($existing) {
            return $existing;
        }

        $referenceHome = WebsitePage::whereRaw('LOWER(module_name) = ?', [$moduleNameLower])
            ->where('page_type', 'home')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $homeSections = $referenceHome && $referenceHome->home_sections
            ? $referenceHome->home_sections
            : WebsitePage::defaultHomeSectionsForTheme($theme->slug);

        $slug = $this->uniqueHomeSlugForModuleTheme($module, $theme);

        return WebsitePage::create([
            'slug' => $slug,
            'title' => 'Home',
            'content' => $referenceHome?->content,
            'meta_description' => $referenceHome?->meta_description,
            'home_sections' => $homeSections,
            'page_type' => 'home',
            'module_name' => $module->name,
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /**
     * Unieke slug voor een module+thema home (globale slug-uniekheid).
     */
    private function uniqueHomeSlugForModuleTheme(Module $module, FrontendTheme $theme): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($module->name)));
        $themeSlug = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', trim($theme->slug)));
        $slug = ($base !== '' ? $base . '-' : '') . 'home-' . $themeSlug;
        $slug = preg_replace('/\-+/', '-', trim($slug, '-'));

        $original = $slug;
        $n = 0;
        while (WebsitePage::where('slug', $slug)->exists()) {
            $slug = $original . '-' . (++$n);
        }

        return $slug;
    }
}
