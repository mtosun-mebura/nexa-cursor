<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
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
            $excluded = $this->excludedComponentIdLookup();
            $items = array_values(array_filter($items, function ($item) use ($excluded) {
                if (! is_array($item)) {
                    return false;
                }
                $id = strtolower(trim((string) ($item['id'] ?? '')));

                return $id !== '' && ! isset($excluded[$id]);
            }));
            $this->components = collect($items)->map(fn ($c) => (object) $c);
        }

        return $this->components;
    }

    /** Section-order keys voor uitgefaseerde componenten (niet meer toevoegen / tonen). */
    public static function removedComponentSectionKeys(): array
    {
        return array_map(
            fn (string $id) => 'component:'.ltrim($id, ':'),
            config('frontend_components.excluded_component_ids', [])
        );
    }

    public function isAllowedComponentSectionKey(string $key): bool
    {
        if (! $this->isPersistableComponentSectionKey($key)) {
            return false;
        }
        $componentId = self::componentIdFromKey(self::normalizeComponentSectionKey($key));

        return $componentId !== null && $this->getById($componentId) !== null;
    }

    /**
     * Of een component-key in section_order bewaard mag blijven (ook legacy/ontdekte ids zonder registry-match).
     */
    public function isPersistableComponentSectionKey(string $key): bool
    {
        $key = self::normalizeComponentSectionKey($key);
        if (! self::isComponentKey($key)) {
            return false;
        }
        $componentId = self::componentIdFromKey($key);
        if ($componentId === null || trim($componentId) === '') {
            return false;
        }

        return ! isset($this->excludedComponentIdLookup()[strtolower(trim($componentId))]);
    }

    /** @return array<string, string> oude section_order key => canonieke key */
    public static function legacyComponentSectionKeyMap(): array
    {
        return [
            'component:nexa.google_reviews' => 'component:website.google_reviews',
            'component:taxiroyaal.tarieven' => 'component:taxi.tarieven',
            'component:taxiroyaal.boekingsmodule' => 'component:taxi.boekingsmodule',
        ];
    }

    public static function normalizeComponentSectionKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return $key;
        }
        if (str_starts_with(strtolower($key), 'component:')) {
            $rest = preg_replace('/^component:+/i', '', $key);
            $key = $rest !== '' ? 'component:'.$rest : $key;
        }

        return self::legacyComponentSectionKeyMap()[$key] ?? $key;
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

    public function componentThemeSlug(object $c): ?string
    {
        $slug = strtolower(trim((string) ($c->theme_slug ?? '')));

        return $slug !== '' ? $slug : null;
    }

    public function catalogGroupKey(object $c): string
    {
        $themeName = trim((string) ($c->theme_name ?? ''));
        if ($themeName !== '') {
            return 'Thema: '.$themeName;
        }
        $moduleName = trim((string) ($c->module_name ?? ''));

        return $moduleName !== '' ? $moduleName : 'Algemeen';
    }

    /**
     * Standaard inhoud voor thema-componenten (editor, demo en frontend-fallback).
     *
     * @return array<string, mixed>
     */
    public function defaultSectionData(string $componentId): array
    {
        return match (strtolower(trim($componentId))) {
            'landwind.faq' => [
                'eyebrow' => 'FAQ',
                'title' => 'Veelgestelde vragen',
                'subtitle' => 'Antwoorden op de vragen die ondernemers het vaakst stellen.',
                'items' => [
                    ['question' => 'Hoe snel is de website live?', 'answer' => 'Na koppeling van het Landwind-thema vult u de teksten in de pagina-editor. De FAQ, logo’s en overige blokken staan direct klaar.'],
                    ['question' => 'Kan ik teksten zelf aanpassen?', 'answer' => 'Ja. Elke vraag en elk antwoord is bewerkbaar in de sectie-editor, zonder code te wijzigen.'],
                    ['question' => 'Werkt dit op mobiel?', 'answer' => 'De accordion is opgebouwd met Tailwind en Flowbite-patronen: één kolom, grote tikvlakken en donkere modus.'],
                    ['question' => 'Voor welk thema is dit blok?', 'answer' => 'Dit FAQ-blok komt uit Landwind, maar u sleept het op elke websitepagina — ook als de tenant een ander thema heeft.'],
                ],
            ],
            'landwind.trusted_by' => [
                'eyebrow' => 'Used by',
                'title' => 'Vertrouwd door groeibedrijven',
                'subtitle' => 'Merken die hun boekingen en klanten via het platform laten lopen.',
                'items' => [
                    ['name' => 'Northline'],
                    ['name' => 'Riviera Cars'],
                    ['name' => 'Stadstaxi Groep'],
                    ['name' => 'Aether Mobility'],
                    ['name' => 'Volt Transfer'],
                ],
            ],
            'play.team' => [
                'eyebrow' => 'Ons team',
                'title' => 'Mensen achter de rit',
                'subtitle' => 'Dispatch, chauffeur-begeleiding en klantenservice — in één overzicht.',
                'items' => [
                    ['name' => 'Lara Vermeer', 'role' => 'Operations lead', 'initials' => 'LV', 'image_url' => '/frontend-themes/play-tailwind/assets/images/team/team-01.png'],
                    ['name' => 'Jamal El Idrissi', 'role' => 'Chauffeur coach', 'initials' => 'JE', 'image_url' => '/frontend-themes/play-tailwind/assets/images/team/team-02.png'],
                    ['name' => 'Sofie Bakker', 'role' => 'Klantenservice', 'initials' => 'SB', 'image_url' => '/frontend-themes/play-tailwind/assets/images/team/team-03.png'],
                ],
            ],
            'play.video_spotlight' => [
                'eyebrow' => 'In beeld',
                'title' => 'Zie hoe een rit binnenkomt',
                'subtitle' => 'Korte uitleg van boeking tot chauffeur-acceptatie.',
                'image_url' => '/frontend-themes/play-tailwind/assets/images/hero/hero-image.jpg',
                'video_url' => '',
                'cta_label' => 'Bekijk de demo',
            ],
            'vue_material.elevated_cards' => [
                'eyebrow' => 'Material cards',
                'title' => 'Drie stappen naar een live site',
                'subtitle' => 'Verhoogde kaarten met icoon — het signatuurblok van Vue Material Kit.',
                'items' => [
                    ['title' => 'Blok plaatsen', 'text' => 'Sleep de kaarten op elke pagina. Het blijft een Vue Material Kit-blok, ook bij een ander thema.', 'accent' => '#e91e63'],
                    ['title' => 'Inhoud vullen', 'text' => 'Pas titel, tekst en accentkleur per kaart aan. Elevatie en ronde iconen blijven staan.', 'accent' => '#7c4dff'],
                    ['title' => 'Publiceren', 'text' => 'De kaarten volgen uw merkkleur en werken in light én dark mode.', 'accent' => '#00bcd4'],
                ],
            ],
            'vue_material.quote_cards' => [
                'eyebrow' => 'Quotes',
                'title' => 'Wat klanten teruggeven',
                'subtitle' => 'Material quote-kaarten met grote aanhalingstekens — geen Google-reviews-carousel.',
                'items' => [
                    ['quote' => 'De site voelt als een product, niet als een template.', 'author' => 'Eva Hendriks', 'role' => 'Directeur, Hendriks Vervoer', 'image_url' => '/frontend-themes/vue-material-kit/src/assets/img/ivana.jpg'],
                    ['quote' => 'Boeken gaat nu via de website; de telefoon blijft vrij voor uitzonderingen.', 'author' => 'Thomas de Wit', 'role' => 'Eigenaar, De Wit Taxi', 'image_url' => '/frontend-themes/vue-material-kit/src/assets/img/team-2.jpg'],
                    ['quote' => 'We wisselden van thema zonder de pagina’s opnieuw te bouwen.', 'author' => 'Nadia Karimi', 'role' => 'Office manager', 'image_url' => '/frontend-themes/vue-material-kit/src/assets/img/marie.jpg'],
                ],
            ],
            'landwind.feature_checklist' => [
                'eyebrow' => 'Werkwijze',
                'title' => 'Van boeking tot chauffeur in één scherm',
                'subtitle' => 'Het Landwind-blok met productshot en vinklijst — geen Features-grid.',
                'image_url' => '/frontend-themes/landwind/images/feature-1.png',
                'items' => [
                    ['text' => 'Klant vult ophaal- en bestemming in op de site'],
                    ['text' => 'Dispatch ziet de rit direct in het overzicht'],
                    ['text' => 'Chauffeur accepteert vanaf de telefoon'],
                    ['text' => 'Status terug naar de klant, zonder nabelen'],
                ],
            ],
            'landwind.stats_strip' => [
                'eyebrow' => 'In cijfers',
                'title' => 'Groei die je kunt meten',
                'subtitle' => 'Vier kerngetallen op een rij, met count-up bij scroll.',
                'items' => [
                    ['value' => '500', 'suffix' => '+', 'label' => 'Actieve ritten per week'],
                    ['value' => '12', 'suffix' => 'k', 'label' => 'Boekingen dit jaar'],
                    ['value' => '98', 'suffix' => '%', 'label' => 'Op tijd aangekomen'],
                    ['value' => '24', 'suffix' => '/7', 'label' => 'Online boekbaar'],
                ],
            ],
            'play.about_overlap' => [
                'eyebrow' => 'Over ons',
                'title' => 'Een dispatch die rustig blijft bij drukte',
                'subtitle' => 'Twee foto’s over elkaar — het Play Tailwind about-blok.',
                'body' => 'Chauffeurs, planning en klantenservice werken vanuit hetzelfde ritoverzicht. De website is het voorportaal; de app is de werkvloer.',
                'image_url' => '/frontend-themes/play-tailwind/assets/images/about/about-image-01.jpg',
                'image_url_2' => '/frontend-themes/play-tailwind/assets/images/about/about-image-02.jpg',
                'cta_label' => 'Meer over het team',
                'cta_url' => '#',
            ],
            'play.blog_preview' => [
                'eyebrow' => 'Inzichten',
                'title' => 'Uit de praktijk',
                'subtitle' => 'Korte stukken over boekingen, chauffeurs en groei.',
                'items' => [
                    ['title' => 'Minder telefoon, meer ritten via de site', 'excerpt' => 'Hoe een taxi-ondernemer de avonddrukte naar online boekingen verschuift.', 'date' => '12 mei 2026', 'image_url' => '/frontend-themes/play-tailwind/assets/images/blog/blog-01.jpg', 'url' => '#'],
                    ['title' => 'Dispatch in één overzicht', 'excerpt' => 'Wat verandert er als chauffeurs ritten zelf claimen vanaf de telefoon.', 'date' => '28 apr 2026', 'image_url' => '/frontend-themes/play-tailwind/assets/images/blog/blog-02.jpg', 'url' => '#'],
                    ['title' => 'Thema wisselen zonder opnieuw bouwen', 'excerpt' => 'Pagina’s blijven staan; alleen het jasje van de site verandert.', 'date' => '9 mrt 2026', 'image_url' => '/frontend-themes/play-tailwind/assets/images/blog/blog-03.jpg', 'url' => '#'],
                ],
            ],
            'play.contact_split' => [
                'eyebrow' => 'Contact',
                'title' => 'Plan een kennismaking',
                'subtitle' => 'Vragen over onboarding, thema’s of een ritproef? Stuur een bericht.',
                'image_url' => '',
                'address' => 'Weena 505, Rotterdam',
                'phone' => '010 123 4567',
                'email' => 'hallo@nexataxi.nl',
                'hours' => 'Ma–vr 08:00–18:00',
                'cta_label' => 'Verstuur bericht',
            ],
            'vue_material.stats_counters' => [
                'eyebrow' => 'Impact',
                'title' => 'Cijfers die meetellen',
                'subtitle' => 'Material-tegels met count-up — anders dan de Landwind-cijferstrip.',
                'items' => [
                    ['value' => '4.9', 'decimals' => '1', 'suffix' => '', 'label' => 'Klantbeoordeling'],
                    ['value' => '180', 'decimals' => '0', 'suffix' => '+', 'label' => 'Chauffeurs live'],
                    ['value' => '32', 'decimals' => '0', 'suffix' => 'k', 'label' => 'Ritten dit kwartaal'],
                    ['value' => '11', 'decimals' => '0', 'suffix' => ' min', 'label' => 'Gem. aannametijd'],
                ],
            ],
            'vue_material.info_pills' => [
                'eyebrow' => 'Ontdek',
                'title' => 'Kies wat je wilt uitleggen',
                'subtitle' => 'Filled pills wisselen het paneel — geen FAQ-accordion.',
                'items' => [
                    ['label' => 'Boeken', 'title' => 'De klant start op de website', 'text' => 'Ophaaladres, bestemming en tijdstip gaan in één flow. Geen terugbellen voor de standaardrit.'],
                    ['label' => 'Dispatch', 'title' => 'De rit landt bij de juiste chauffeur', 'text' => 'Aanbiedingen gaan naar beschikbare chauffeurs. Wie accepteert, krijgt navigatie en klantgegevens.'],
                    ['label' => 'Opvolging', 'title' => 'Status blijft zichtbaar', 'text' => 'Van onderweg tot aankomst: de klant ziet waar de rit staat, zonder de centrale te belasten.'],
                ],
            ],
            'vue_material.author_header' => [
                'eyebrow' => 'Auteur',
                'title' => 'Lara Vermeer',
                'subtitle' => 'Operations lead',
                'bio' => 'Bouwt de dagelijkse ritstroom: van websiteboeking tot chauffeur in de app. Dit blok hoort bij Vue Material Kit — geen teamgrid.',
                'image_url' => '/frontend-themes/vue-material-kit/src/assets/img/team-2.jpg',
                'items' => [
                    ['label' => 'LinkedIn', 'url' => '#'],
                    ['label' => 'E-mail', 'url' => 'mailto:lara@nexataxi.nl'],
                    ['label' => 'Website', 'url' => '#'],
                ],
            ],
            default => [],
        };
    }

    /**
     * Componenten die op een pagina toegevoegd kunnen worden.
     * Geef de module-key van de pagina (canoniek zoals in modules.name, bijv. taxi).
     * Alleen componenten van die module plus available_on_all_pages. Leeg/null = geen modulefilter.
     * Thema-componenten (Landwind, Play, Vue Material) zijn op elk thema sleepbaar; $tenantThemeSlug sorteert het eigen thema vooraan.
     */
    public function availableForPage(?string $pageModuleName = null, ?string $tenantThemeSlug = null): Collection
    {
        $all = $this->all();
        $effective = trim((string) ($pageModuleName ?? ''));
        if ($effective === '') {
            return $this->sortForPagePalette($all, $tenantThemeSlug);
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

        return $this->sortForPagePalette($forModule->merge($global)->unique('id')->values(), $tenantThemeSlug);
    }

    /**
     * @param  Collection<int, object>  $items
     * @return Collection<int, object>
     */
    private function sortForPagePalette(Collection $items, ?string $tenantThemeSlug): Collection
    {
        $prefer = strtolower(trim((string) $tenantThemeSlug));
        $themeRank = [
            'landwind' => 1,
            'play-tailwind' => 2,
            'vue-material-kit' => 3,
        ];

        return $items->sortBy(function ($c) use ($prefer, $themeRank) {
            $theme = strtolower(trim((string) ($c->theme_slug ?? '')));
            $name = strtolower(trim((string) ($c->name ?? '')));
            if ($theme === '') {
                return '0-'.$name;
            }
            $preferred = ($prefer !== '' && $theme === $prefer) ? 0 : 1;
            $order = $themeRank[$theme] ?? 9;

            return sprintf('1-%d-%02d-%s-%s', $preferred, $order, $theme, $name);
        })->values();
    }

    private const COMPONENT_KEY_PREFIX = 'component:';

    /** Controleer of een section_order key een component-key is (component:module.key). */
    public static function isComponentKey(string $key): bool
    {
        return str_starts_with($key, self::COMPONENT_KEY_PREFIX)
            && strlen($key) > strlen(self::COMPONENT_KEY_PREFIX);
    }

    /** Component-id uit section key halen (component:nexa.recente_vacatures -> nexa.recente_vacatures). */
    public static function componentIdFromKey(string $key): ?string
    {
        if (! self::isComponentKey($key)) {
            return null;
        }

        return substr($key, strlen(self::COMPONENT_KEY_PREFIX));
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

        $excludedBasenames = array_fill_keys(
            array_map('strtolower', config('frontend_components.excluded_discovered_basenames', [])),
            true
        );

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
            if (isset($excludedBasenames[strtolower($basename)])) {
                continue;
            }
            $view = 'frontend.website.components.'.$basename;
            $viewKey = strtolower($view);
            if (isset($existingViewsLookup[$viewKey])) {
                continue;
            }
            $autoId = 'website.'.str_replace('-', '_', $basename);
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

    /** @return array<string, true> */
    private function excludedComponentIdLookup(): array
    {
        $ids = config('frontend_components.excluded_component_ids', []);

        return array_fill_keys(array_map('strtolower', array_map('strval', $ids)), true);
    }
}
