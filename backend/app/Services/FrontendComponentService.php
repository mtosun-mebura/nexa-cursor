<?php

namespace App\Services;

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
        return $this->all()->firstWhere('id', $id);
    }

    /** Componenten gegroepeerd per module_name (voor overzichtspagina). */
    public function groupedByModule(): Collection
    {
        return $this->all()->groupBy('module_name');
    }

    /** Componenten die op een pagina (bijv. home) toegevoegd kunnen worden; optioneel gefilterd op module. */
    public function availableForPage(?string $moduleName = null): Collection
    {
        $all = $this->all();
        if ($moduleName !== null && $moduleName !== '') {
            return $all->where('module_name', $moduleName);
        }
        return $all;
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
