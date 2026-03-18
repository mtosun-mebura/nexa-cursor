<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Collection;

/**
 * Registry van front-end componenten. Componenten worden uit config gelezen
 * en zijn alleen in code aanpasbaar (niet via beheer).
 */
class FrontendComponentService
{
    protected ?Collection $components = null;

    public function all(): Collection
    {
        if ($this->components === null) {
            $items = config('frontend_components.components', []);
            $this->components = collect($items)->map(fn ($c) => (object) $c);
        }
        return $this->components;
    }

    public function getById(string $id): ?object
    {
        $id = trim((string) $id);
        if ($id === '') {
            return null;
        }
        $found = $this->all()->first(function ($c) use ($id) {
            return strcasecmp($c->id ?? '', $id) === 0;
        });
        return $found ?: null;
    }

    /** Componenten gegroepeerd per module_name (voor overzichtspagina). */
    public function groupedByModule(): Collection
    {
        return $this->all()->groupBy('module_name');
    }

    /**
     * Componenten die op een pagina toegevoegd kunnen worden.
     * Geef de module-key van de pagina (bijv. uit $page->module_name). Alleen componenten
     * van die module worden getoond. Bij null (kernpagina) worden alle componenten getoond.
     * Componenten met available_on_all_pages (bijv. Google Reviews) worden altijd getoond.
     */
    public function availableForPage(?string $pageModuleName = null): Collection
    {
        $all = $this->all();
        if ($pageModuleName === null || $pageModuleName === '') {
            return $all;
        }
        $module = Module::where('installed', true)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($pageModuleName))])
            ->first();
        if (!$module || !$module->display_name) {
            return $all->filter(fn ($c) => false);
        }
        $displayName = trim($module->display_name);
        $forModule = $all->filter(fn ($c) => trim((string) ($c->module_name ?? '')) === $displayName);
        $global = $all->filter(fn ($c) => !empty($c->available_on_all_pages));
        return $forModule->merge($global)->unique('id')->values();
    }

    /** Controleer of een section_order key een component-key is (component:module.key). */
    public static function isComponentKey(string $key): bool
    {
        return str_starts_with($key, 'component:') && strlen($key) > 9;
    }

    /** Component-id uit section key halen (component:nexa.recente_vacatures -> nexa.recente_vacatures). */
    public static function componentIdFromKey(string $key): ?string
    {
        if (! self::isComponentKey($key)) {
            return null;
        }
        return substr($key, 9);
    }
}
