<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    /**
     * Demo-pagina voor staging wanneer er geen actieve pagina's zijn.
     * Niet opgeslagen; getHomeSections() gebruikt de defaults van het thema.
     *
     * @return WebsitePage
     */
    public static function demoPageForTheme(FrontendTheme $theme, ?string $moduleName): WebsitePage
    {
        $page = new self([
            'title' => 'Home',
            'slug' => 'home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => $moduleName,
        ]);
        $page->exists = false;
        $page->setRelation('theme', $theme);

        return $page;
    }
    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_description',
        'home_sections',
        'page_type',
        'module_name',
        'frontend_theme_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'home_sections' => 'array',
    ];

    public const PAGE_TYPES = ['home', 'about', 'contact', 'custom', 'module'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForModule($query, ?string $moduleName)
    {
        if ($moduleName === null) {
            return $query->whereNull('module_name');
        }
        return $query->where('module_name', $moduleName);
    }

    public function scopeCorePages($query)
    {
        return $query->whereIn('page_type', ['home', 'about', 'contact', 'custom'])
            ->whereNull('module_name');
    }

    public function theme()
    {
        return $this->belongsTo(FrontendTheme::class, 'frontend_theme_id');
    }

    /**
     * Geeft de blokken-array terug als content geldige Editor.js JSON is; anders null (legacy HTML).
     *
     * @return array<int, array{id?: string, type: string, data: array}>|null
     */
    public function getContentBlocks(): ?array
    {
        if (!$this->content || !is_string($this->content)) {
            return null;
        }
        $trimmed = trim($this->content);
        if ($trimmed === '' || ($trimmed[0] !== '[' && $trimmed[0] !== '{')) {
            return null;
        }
        $decoded = json_decode($this->content, true);
        if (!is_array($decoded) || !isset($decoded['blocks']) || !is_array($decoded['blocks'])) {
            return null;
        }
        return $decoded['blocks'];
    }

    /**
     * Standaardwaarden voor home-secties (Modern thema).
     */
    public static function defaultHomeSections(): array
    {
        return [
            'hero' => [
                'title' => 'Vind je droombaan met AI',
                'title_highlight' => 'droombaan',
                'subtitle' => 'Ons geavanceerde AI-platform matcht jouw vaardigheden met de perfecte vacatures van topbedrijven. Start vandaag nog je carrière.',
                'cta_primary_text' => 'Gratis account aanmaken',
                'cta_primary_url' => '/register',
                'cta_primary_bg' => '',
                'cta_primary_border' => '',
                'cta_primary_text_color' => '',
                'cta_secondary_text' => 'Vacatures bekijken',
                'cta_secondary_url' => '/jobs',
                'cta_secondary_bg' => '',
                'cta_secondary_border' => '',
                'cta_secondary_text_color' => '',
                'overlay' => true,
            ],
            'stats' => [
                ['value' => '10,000+', 'label' => 'Actieve vacatures'],
                ['value' => '5,000+', 'label' => 'Succesvolle matches'],
                ['value' => '500+', 'label' => 'Partner bedrijven'],
                ['value' => '95%', 'label' => 'Match accuracy'],
            ],
            'why_nexa' => [
                'title' => 'Waarom kiezen voor Nexa?',
                'subtitle' => 'Onze geavanceerde AI-technologie maakt het vinden van de perfecte baan eenvoudiger dan ooit.',
            ],
            'features' => [
                'section_title' => 'Wat Wij Bieden',
                'items' => [
                    [
                        'title' => 'AI-Powered Matching',
                        'description' => 'Onze geavanceerde algoritmes analyseren je vaardigheden en vinden de perfecte match met 95% accuracy.',
                        'icon' => 'light-bulb',
                        'icon_size' => 'medium',
                    ],
                    [
                        'title' => 'Snelle Resultaten',
                        'description' => 'Vind relevante vacatures in seconden. Ons platform filtert en rangschikt resultaten op basis van jouw profiel.',
                        'icon' => 'bolt',
                        'icon_size' => 'medium',
                    ],
                ],
            ],
            'cta' => [
                'title' => 'Klaar om je carrière te starten?',
                'subtitle' => 'Sluit je aan bij duizenden professionals die hun droombaan hebben gevonden.',
                'cta_primary_text' => 'Gratis account aanmaken',
                'cta_primary_url' => '/register',
                'cta_primary_bg' => '',
                'cta_primary_border' => '',
                'cta_primary_text_color' => '',
                'cta_secondary_text' => 'Vacatures bekijken',
                'cta_secondary_url' => '/jobs',
                'cta_secondary_bg' => '',
                'cta_secondary_border' => '',
                'cta_secondary_text_color' => '',
            ],
            'carousel' => [
                'items' => [],
            ],
            'cards_ronde_hoeken' => [
                'items' => [
                    ['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left'],
                ],
            ],
            'footer' => [
                'tagline' => 'Ontdek de perfecte match tussen jouw vaardigheden en vacatures. Ons AI-platform helpt je de ideale baan te vinden.',
                'logo_url' => '',
                'logo_alt' => '',
                'logo_height' => 12,
                'quick_links_title' => 'Snelle Links',
                'quick_links' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Vacatures', 'url' => '/jobs'],
                    ['label' => 'Over Ons', 'url' => '/over-ons'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ],
                'support_links_title' => 'Ondersteuning',
                'support_links' => [
                    ['label' => 'Help & FAQ', 'url' => '/help'],
                    ['label' => 'Privacy', 'url' => '/privacy'],
                    ['label' => 'Voorwaarden', 'url' => '/voorwaarden'],
                    ['label' => 'Cookies', 'url' => '/privacy#cookies'],
                ],
            ],
            'copyright' => '© {year} Nexa Skillmatching. Alle rechten voorbehouden.',
            'section_order' => ['hero', 'stats', 'why_nexa', 'features', 'component:nexa.recente_vacatures', 'cta'],
            'visibility' => [
                'hero' => true,
                'hero_title' => true,
                'hero_subtitle' => true,
                'hero_cta' => true,
                'stats' => true,
                'stats_0' => true,
                'stats_1' => true,
                'stats_2' => true,
                'stats_3' => true,
                'why_nexa' => true,
                'why_nexa_title' => true,
                'why_nexa_subtitle' => true,
                'features' => true,
                'features_section_title' => true,
                'features_item_0' => true,
                'features_item_1' => true,
                'cta' => true,
                'cta_title' => true,
                'cta_subtitle' => true,
                'cta_buttons' => true,
                'footer' => true,
                'footer_tagline' => true,
                'footer_logo' => true,
                'footer_quick_links' => true,
                'footer_support_links' => true,
            ],
        ];
    }

    /**
     * Beschikbare sectietypes om toe te voegen, per thema (zonder component:xxx).
     * Gebruikt in admin voor de "Sectie toevoegen" dropdown.
     *
     * @return array<int, array{type: string, label: string}>
     */
    public static function getAvailableHomeSectionTypesForTheme(string $themeSlug): array
    {
        $themeSlug = strtolower(trim($themeSlug));
        $defaults = [
            'hero' => 'Hero (banner)',
            'stats' => 'Stats (4 cijfers)',
            'why_nexa' => 'Waarom Nexa',
            'features' => 'Wat Wij Bieden',
            'cta' => 'CTA',
            'carousel' => 'Carousel',
            'cards_ronde_hoeken' => 'Cards ronde hoeken',
        ];
        $byTheme = [
            'atom-v2' => [
                ['type' => 'hero', 'label' => 'Hero (banner)'],
                ['type' => 'why_nexa', 'label' => 'Over ons'],
                ['type' => 'features', 'label' => 'Onze diensten'],
                ['type' => 'stats', 'label' => 'Stats (4 cijfers)'],
                ['type' => 'cta', 'label' => 'CTA'],
                ['type' => 'carousel', 'label' => 'Carousel'],
                ['type' => 'cards_ronde_hoeken', 'label' => 'Cards ronde hoeken'],
            ],
            'nextly-template' => [
                ['type' => 'hero', 'label' => 'Hero (banner)'],
                ['type' => 'why_nexa', 'label' => 'Voordelen / Over ons'],
                ['type' => 'features', 'label' => 'Wat wij bieden'],
                ['type' => 'stats', 'label' => 'Stats (4 cijfers)'],
                ['type' => 'cta', 'label' => 'CTA'],
                ['type' => 'carousel', 'label' => 'Carousel'],
                ['type' => 'cards_ronde_hoeken', 'label' => 'Cards ronde hoeken'],
            ],
            'next-landing-vpn' => [
                ['type' => 'hero', 'label' => 'Hero (banner)'],
                ['type' => 'features', 'label' => 'Kenmerken'],
                ['type' => 'cta', 'label' => 'CTA'],
                ['type' => 'carousel', 'label' => 'Carousel'],
                ['type' => 'cards_ronde_hoeken', 'label' => 'Cards ronde hoeken'],
            ],
        ];
        if (isset($byTheme[$themeSlug])) {
            return $byTheme[$themeSlug];
        }
        return [
            ['type' => 'hero', 'label' => $defaults['hero']],
            ['type' => 'stats', 'label' => $defaults['stats']],
            ['type' => 'why_nexa', 'label' => $defaults['why_nexa']],
            ['type' => 'features', 'label' => $defaults['features']],
            ['type' => 'cta', 'label' => $defaults['cta']],
            ['type' => 'carousel', 'label' => $defaults['carousel']],
            ['type' => 'cards_ronde_hoeken', 'label' => $defaults['cards_ronde_hoeken']],
        ];
    }

    /**
     * Standaard home-secties per thema (zelfde structuur als defaultHomeSections).
     * Atom v2: hero, why_nexa (about), features (services), stats, cta.
     * Nextly Template: hero, why_nexa, features (benefits), cta.
     * Next Landing VPN: hero, features, cta.
     */
    public static function defaultHomeSectionsForTheme(string $themeSlug): array
    {
        $base = self::defaultHomeSections();
        $themeSlug = strtolower(trim($themeSlug));

        switch ($themeSlug) {
            case 'atom-v2':
                $base['section_order'] = ['hero', 'why_nexa', 'features', 'stats', 'cta', 'carousel'];
                $base['hero']['title'] = 'Welkom bij Nexa';
                $base['hero']['title_highlight'] = 'Nexa';
                $base['hero']['subtitle'] = 'Ontdek hoe ons platform jouw carrière vooruit helpt.';
                $base['hero']['background_image_url'] = '';
                $base['hero']['author_image_url'] = '';
                $base['why_nexa']['title'] = 'Over ons';
                $base['why_nexa']['subtitle'] = 'Wij verbinden talent met kansen.';
                $base['features']['section_title'] = 'Onze diensten';
                break;
            case 'nextly-template':
                $base['section_order'] = ['hero', 'why_nexa', 'features', 'stats', 'cta', 'carousel'];
                $base['hero']['title'] = 'Waarom kiezen voor ons';
                $base['hero']['title_highlight'] = 'ons';
                $base['hero']['subtitle'] = 'Een korte introductie over wat wij bieden.';
                $base['why_nexa']['title'] = 'Voordelen';
                $base['why_nexa']['subtitle'] = 'Ontdek de voordelen van ons platform.';
                $base['features']['section_title'] = 'Wat wij bieden';
                break;
            case 'next-landing-vpn':
                $base['section_order'] = ['hero', 'features', 'cta', 'carousel'];
                $base['hero']['title'] = 'Jouw carrière begint hier';
                $base['hero']['title_highlight'] = 'hier';
                $base['hero']['subtitle'] = 'Match met de beste vacatures via ons AI-platform.';
                $base['features']['section_title'] = 'Kenmerken';
                break;
            default:
                break;
        }

        return $base;
    }

    /**
     * Minimale secties voor niet-home paginatypes (about, contact, custom, module).
     * Standaard alleen: Hero-banner, footer en copyright. Rest handmatig toevoegen.
     */
    public static function defaultPageSectionsForNonHome(string $themeSlug): array
    {
        $base = self::defaultHomeSectionsForTheme($themeSlug);
        $base['section_order'] = ['hero'];
        $base['visibility'] = [
            'hero' => true,
            'hero_title' => true,
            'hero_subtitle' => true,
            'hero_cta' => true,
            'footer' => true,
            'footer_tagline' => true,
            'footer_logo' => true,
            'footer_quick_links' => true,
            'footer_support_links' => true,
        ];

        return $base;
    }

    private const HOME_SECTION_BASE_TYPES = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];

    private static function homeSectionBaseType(string $sectionKey): ?string
    {
        if (in_array($sectionKey, self::HOME_SECTION_BASE_TYPES, true)) {
            return $sectionKey;
        }
        $base = preg_replace('/_\d+$/', '', $sectionKey);

        return in_array($base, self::HOME_SECTION_BASE_TYPES, true) ? $base : null;
    }

    /**
     * Home-secties voor weergave: opgeslagen data gemerged met defaults.
     * Ondersteunt dynamische sectie-keys (hero_2, features_2, etc.) uit section_order.
     */
    public function getHomeSections(): array
    {
        $stored = $this->home_sections ?? [];
        $themeSlug = $this->theme?->slug ?? 'modern';
        $isHome = $this->page_type === 'home' || $this->slug === 'home';
        $defaults = $isHome
            ? self::defaultHomeSectionsForTheme($themeSlug)
            : self::defaultPageSectionsForNonHome($themeSlug);

        $sectionOrder = $stored['section_order'] ?? $defaults['section_order'];
        if (is_string($sectionOrder) && $sectionOrder !== '') {
            $sectionOrder = array_values(array_filter(array_map('trim', explode(',', $sectionOrder)), fn ($key) => $key !== ''));
        }
        if (! is_array($sectionOrder)) {
            $sectionOrder = $defaults['section_order'];
        }
        // Alleen geldige string-keys behouden (hero, stats, component:..., etc.)
        $sectionOrder = array_values(array_filter($sectionOrder, fn ($k) => is_string($k) && $k !== ''));
        // Normaliseer component-keys naar "component:id" (lowercase, geen dubbele prefix); verwijder duplicaten
        $sectionOrder = array_map(function ($key) {
            if (is_string($key) && str_starts_with(strtolower($key), 'component:')) {
                $rest = preg_replace('/^component:+/i', '', $key);
                return $rest !== '' ? 'component:' . $rest : $key;
            }
            return $key;
        }, $sectionOrder);
        $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));
        $sectionOrder = array_values($sectionOrder);
        // Als alle content-secties hetzelfde type zijn (bijv. alleen hero) terwijl het thema meerdere types heeft, gebruik dan de thema-default (herstel foutieve data)
        $contentKeys = array_filter($sectionOrder, fn ($k) => is_string($k) && ! str_starts_with(strtolower($k), 'component:'));
        $baseTypes = array_filter(array_map([self::class, 'homeSectionBaseType'], $contentKeys));
        $defaultContentCount = count(array_filter($defaults['section_order'], fn ($k) => is_string($k) && ! str_starts_with(strtolower($k), 'component:')));
        if (count($baseTypes) > 0 && count(array_unique($baseTypes)) === 1 && $defaultContentCount > 1) {
            $sectionOrder = $defaults['section_order'];
        }
        // Voeg ontbrekende keys uit default toe op hun standaardpositie (bijv. component tussen features en cta)
        $missing = array_diff($defaults['section_order'], $sectionOrder);
        if (! empty($missing)) {
            foreach (array_values($missing) as $key) {
                $pos = array_search($key, $defaults['section_order'], true);
                if ($pos !== false) {
                    array_splice($sectionOrder, $pos, 0, [$key]);
                }
            }
            $sectionOrder = array_values($sectionOrder);
        }

        $sections = [];
        foreach ($sectionOrder as $sectionKey) {
            if (str_starts_with($sectionKey, 'component:')) {
                continue;
            }
            $baseType = self::homeSectionBaseType($sectionKey);
            if ($baseType === null) {
                continue;
            }
            $sections[$sectionKey] = $this->mergeOneHomeSection($stored, $sectionKey, $baseType, $defaults);
        }

        $footer = array_merge($defaults['footer'], $stored['footer'] ?? []);
        if (! empty($stored['footer']['quick_links']) && is_array($stored['footer']['quick_links'])) {
            $footer['quick_links'] = array_values($stored['footer']['quick_links']);
        }
        if (! empty($stored['footer']['support_links']) && is_array($stored['footer']['support_links'])) {
            $footer['support_links'] = array_values($stored['footer']['support_links']);
        }
        $copyright = $stored['copyright'] ?? $defaults['copyright'];
        $visibility = array_merge($defaults['visibility'], $stored['visibility'] ?? []);

        foreach ($sections as $sectionKey => $data) {
            if (! array_key_exists($sectionKey, $visibility)) {
                $visibility[$sectionKey] = true;
            }
            if ($data && isset($data['items']) && is_array($data['items'])) {
                foreach (array_keys($data['items']) as $i) {
                    $vk = $sectionKey === 'features' ? 'features_item_'.$i : $sectionKey.'_item_'.$i;
                    if (! array_key_exists($vk, $visibility)) {
                        $visibility[$vk] = true;
                    }
                }
            }
        }

        return array_merge($sections, [
            'footer' => $footer,
            'copyright' => $copyright,
            'section_order' => $sectionOrder,
            'visibility' => $visibility,
        ]);
    }

    private function mergeOneHomeSection(array $stored, string $sectionKey, string $baseType, array $defaults): array
    {
        $raw = $stored[$sectionKey] ?? [];
        if (! is_array($raw)) {
            $raw = [];
        }
        switch ($baseType) {
            case 'hero':
                return array_merge($defaults['hero'], $raw);
            case 'stats':
                $stats = $stored[$sectionKey] ?? $defaults['stats'];
                return is_array($stats) ? array_values($stats) : $defaults['stats'];
            case 'why_nexa':
                return array_merge($defaults['why_nexa'], $raw);
            case 'features':
                $out = array_merge($defaults['features'], $raw);
                if (! empty($raw['items']) && is_array($raw['items'])) {
                    $out['items'] = array_values($raw['items']);
                }
                return $out;
            case 'cta':
                return array_merge($defaults['cta'], $raw);
            case 'carousel':
                $items = $raw['items'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }
                return [
                    'items' => array_values($items),
                ];
            case 'cards_ronde_hoeken':
                $items = $raw['items'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }
                $defItems = $defaults['cards_ronde_hoeken']['items'] ?? [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']];
                $out = array_values(array_map(function ($row) {
                    $fontSize = isset($row['font_size']) ? max(10, min(24, (int) $row['font_size'])) : 14;
                    $fontStyle = isset($row['font_style']) && in_array($row['font_style'], ['normal', 'bold', 'italic'], true) ? $row['font_style'] : 'normal';
                    $cardSize = isset($row['card_size']) && in_array($row['card_size'], ['small', 'normal', 'large', 'max'], true) ? $row['card_size'] : 'normal';
                    $textAlign = isset($row['text_align']) && in_array($row['text_align'], ['left', 'center', 'right'], true) ? $row['text_align'] : 'left';
                    return [
                        'image_url' => isset($row['image_url']) ? trim((string) $row['image_url']) : '',
                        'text' => isset($row['text']) ? trim((string) $row['text']) : '',
                        'font_size' => $fontSize,
                        'font_style' => $fontStyle,
                        'card_size' => $cardSize,
                        'text_align' => $textAlign,
                    ];
                }, $items));
                if (empty($out)) {
                    $out = $defItems;
                }
                return ['items' => $out];
            default:
                return [];
        }
    }
}
