<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
            $items = $this->appendDiscoveredComponents($items);
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
     * Canonieke module-key voor een component (config module_key, of afgeleid uit id zoals taxi.* / nexa.*).
     */
    public function componentModuleKey(object $c): ?string
    {
        $explicit = trim((string) ($c->module_key ?? ''));
        if ($explicit !== '') {
            return strtolower($explicit);
        }
        $id = trim((string) ($c->id ?? ''));
        if (str_starts_with($id, 'taxi.')) {
            return 'taxi';
        }
        if (str_starts_with($id, 'nexa.')) {
            return 'skillmatching';
        }

        return null;
    }

    /**
     * Componenten die op een pagina toegevoegd kunnen worden.
     * Geef de module-key van de pagina (canoniek zoals in modules.name, bijv. taxi).
     * Alleen componenten van die module plus available_on_all_pages. Leeg/null = geen filter (alle componenten).
     */
    public function availableForPage(?string $pageModuleName = null): Collection
    {
        $all = $this->all();
        $effective = trim((string) ($pageModuleName ?? ''));
        if ($effective === '') {
            return $all;
        }
        $module = Module::where('installed', true)
            ->whereRaw('LOWER(name) = ?', [strtolower($effective)])
            ->first();
        if (! $module) {
            return $all->filter(fn ($c) => false);
        }
        $moduleNameLower = strtolower(trim((string) $module->name));
        $displayName = trim((string) ($module->display_name ?? ''));

        $forModule = $all->filter(function ($c) use ($moduleNameLower, $displayName) {
            if (! empty($c->available_on_all_pages)) {
                return false;
            }
            $ck = $this->componentModuleKey($c);
            if ($ck !== null) {
                return $ck === $moduleNameLower;
            }
            if ($displayName !== '') {
                return trim((string) ($c->module_name ?? '')) === $displayName;
            }

            return false;
        });
        $global = $all->filter(fn ($c) => ! empty($c->available_on_all_pages));

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

    /**
     * Voeg component-views automatisch toe als ze ontbreken in config.
     */
    private function appendDiscoveredComponents(array $configured): array
    {
        $byId = collect($configured)
            ->filter(fn ($item) => is_array($item) && ! empty($item['id']))
            ->keyBy(fn ($item) => strtolower((string) $item['id']))
            ->all();
        $existingViews = collect($configured)
            ->filter(fn ($item) => is_array($item) && ! empty($item['view']))
            ->map(fn ($item) => strtolower(trim((string) $item['view'])))
            ->filter()
            ->values()
            ->all();
        $existingViewsLookup = array_fill_keys($existingViews, true);

        $componentsDir = resource_path('views/frontend/website/components');
        if (! File::isDirectory($componentsDir)) {
            return array_values($byId);
        }

        $files = File::files($componentsDir);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (! str_ends_with($filename, '.blade.php')) {
                continue;
            }

            $basename = Str::before($filename, '.blade.php');
            $view = 'frontend.website.components.' . $basename;
            $viewKey = strtolower($view);
            if (isset($existingViewsLookup[$viewKey])) {
                continue;
            }
            $autoId = 'website.' . str_replace('-', '_', $basename);
            $idKey = strtolower($autoId);
            if (isset($byId[$idKey])) {
                continue;
            }

            $label = str($basename)->replace(['-', '_'], ' ')->title()->toString();
            $byId[$idKey] = [
                'id' => $autoId,
                'name' => $label,
                'module_name' => 'Algemeen',
                'view' => $view,
                'description' => 'Automatisch ontdekt component (nog niet expliciet geconfigureerd).',
                'available_on_all_pages' => true,
            ];
            $existingViewsLookup[$viewKey] = true;
        }

        return array_values($byId);
    }
}
