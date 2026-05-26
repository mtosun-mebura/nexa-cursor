<?php

namespace App\Models;

use App\Services\FrontendComponentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsitePage extends Model
{
    /**
     * Gereserveerde slug voor de marketing-landingspagina op het centrale domein (geen tenant).
     * Niet bedoeld als publieke URL; alleen bewerkbaar via Admin → Welkom / website-pagina's.
     */
    public const CENTRAL_WELCOME_SLUG = 'nexa-centraal-welkom';

    public static function isCentralMarketingWelcomeSlug(?string $slug): bool
    {
        return $slug !== null && $slug !== '' && strtolower($slug) === strtolower(self::CENTRAL_WELCOME_SLUG);
    }

    /**
     * Demo-pagina voor staging wanneer er geen actieve pagina's zijn.
     * Niet opgeslagen; getHomeSections() gebruikt de defaults van het thema.
     */
    public static function demoPageForTheme(FrontendTheme $theme, ?string $moduleName): WebsitePage
    {
        $page = new self([
            'title' => 'Home',
            'slug' => 'home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => $moduleName,
            'show_in_menu' => true,
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
        'company_id',
        'is_active',
        'show_in_menu',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
        'home_sections' => 'array',
    ];

    public const PAGE_TYPES = ['home', 'about', 'contact', 'custom', 'module'];

    /**
     * Volgende menu-/paginavolgorde voor een tenant (company_id) binnen één database-connection.
     */
    public static function nextSortOrderForTenant(?string $connection = null, ?int $companyId = null): int
    {
        $connection = $connection ?? (string) config('database.default');
        $query = static::on($connection)->newQuery();

        if (static::tableHasColumnOnConnection($connection, 'company_id')) {
            if ($companyId !== null) {
                $query->where('company_id', $companyId);
            } else {
                $query->whereNull('company_id');
            }
        }

        if (! static::tableHasColumnOnConnection($connection, 'sort_order')) {
            return 1;
        }

        $max = $query->max('sort_order');

        return max(0, (int) $max) + 1;
    }

    public static function tableHasColumnOnConnection(string $connection, string $column): bool
    {
        $table = (new static)->getTable();
        if (Schema::connection($connection)->hasColumn($table, $column)) {
            return true;
        }
        if (Schema::connection($connection)->getConnection()->getDriverName() !== 'pgsql') {
            return false;
        }
        try {
            $row = DB::connection($connection)->selectOne(
                'SELECT 1 AS x FROM information_schema.columns WHERE table_schema = ANY (current_schemas(true)) AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            );

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Alleen pagina's die in het menu getoond moeten worden.
     * Werkt ook als de kolom show_in_menu nog niet bestaat (bijv. module-DB niet gemigreerd).
     */
    public function scopeShowInMenu($query)
    {
        $connectionName = $query->getConnection()->getName();
        if (static::tableHasColumnOnConnection($connectionName, 'show_in_menu')) {
            return $query->where($this->getTable().'.show_in_menu', true);
        }

        return $query;
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Geeft de blokken-array terug als content geldige Editor.js JSON is; anders null (legacy HTML).
     *
     * @return array<int, array{id?: string, type: string, data: array}>|null
     */
    public function getContentBlocks(): ?array
    {
        if (! $this->content || ! is_string($this->content)) {
            return null;
        }
        $trimmed = trim($this->content);
        if ($trimmed === '' || ($trimmed[0] !== '[' && $trimmed[0] !== '{')) {
            return null;
        }
        $decoded = json_decode($this->content, true);
        if (! is_array($decoded) || ! isset($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            return null;
        }

        return $decoded['blocks'];
    }

    /**
     * Standaardwaarden voor home-secties (Metronic thema).
     */
    /**
     * Standaard ingeklapte admin-kaarten voor alle secties in section_order + footer + copyright.
     *
     * @param  array<int, string>  $sectionOrder
     * @return array<int, string>
     */
    public static function defaultAdminCollapsedKeys(array $sectionOrder): array
    {
        $keys = array_values(array_filter($sectionOrder, static fn ($k) => is_string($k) && $k !== ''));

        foreach (['footer', 'copyright'] as $fixed) {
            if (! in_array($fixed, $keys, true)) {
                $keys[] = $fixed;
            }
        }

        return $keys;
    }

    public static function defaultHomeSections(): array
    {
        $sectionOrder = ['hero', 'why_nexa', 'features', 'stats', 'cta'];

        return [
            'hero' => [
                'title' => 'Vind je droombaan met AI',
                'title_highlight' => 'droombaan',
                'title_highlight_color' => '',
                'subtitle_color' => '',
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
                'items' => [
                    ['value' => '10,000+', 'label' => 'Actieve vacatures', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'],
                    ['value' => '5,000+', 'label' => 'Succesvolle matches', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'],
                    ['value' => '500+', 'label' => 'Partner bedrijven', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'],
                    ['value' => '95%', 'label' => 'Match accuracy', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'],
                ],
                'background' => '',
                'background_image' => '',
            ],
            'why_nexa' => [
                'title' => 'Waarom kiezen voor Nexa?',
                'subtitle_color' => '',
                'subtitle' => 'Onze geavanceerde AI-technologie maakt het vinden van de perfecte baan eenvoudiger dan ooit.',
            ],
            'features' => [
                'section_title' => 'Kenmerken',
                'items' => [
                    [
                        'title' => 'AI-Powered Matching',
                        'description' => 'Onze geavanceerde algoritmes analyseren je vaardigheden en vinden de perfecte match met 95% accuracy.',
                        'icon' => 'light-bulb',
                        'icon_size' => 'medium',
                        'icon_align' => 'center',
                    ],
                    [
                        'title' => 'Snelle Resultaten',
                        'description' => 'Vind relevante vacatures in seconden. Ons platform filtert en rangschikt resultaten op basis van jouw profiel.',
                        'icon' => 'bolt',
                        'icon_size' => 'medium',
                        'icon_align' => 'center',
                    ],
                ],
            ],
            'cta' => [
                'title' => 'Klaar om je carrière te starten?',
                'subtitle_color' => '',
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
                'interval_seconds' => 5,
            ],
            'cards_ronde_hoeken' => [
                'cards_per_row' => 4,
                'items' => [
                    ['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left', 'image_padding' => 2, 'image_bg_color' => '', 'text_color' => ''],
                ],
            ],
            'featured_services' => [
                'title' => 'Diensten',
                'subtitle' => 'Onze diensten in het kort.',
                'title_font_size_px' => 24,
                'subtitle_font_size_px' => 18,
                'item_title_font_size_px' => 18,
                'item_description_font_size_px' => 14,
                'blocks_per_row' => 3,
                'blocks_row_width_percent' => 100,
                'block_size' => 'medium',
                'block_align' => 'center',
                'icon_size' => 'medium',
                'icon_align' => 'center',
                'card_bg_color' => '',
                'animation_speed' => 'slow',
                'items' => [
                    ['icon' => 'briefcase', 'title' => 'Business Collaboration', 'description' => 'Morbi sagittis hendrerit nulla ultricies. Cras in diam ipsum, elementum pretium hendrerit ultricies.'],
                    ['icon' => 'cog-6-tooth', 'title' => 'Engineering & Services', 'description' => 'Proin scelerisque magna at porttitor tristique.'],
                    ['icon' => 'user-group', 'title' => 'Consulting', 'description' => 'Samen werken we aan het beste resultaat.'],
                ],
            ],
            'email_template' => [
                'title' => 'Informatie aanvragen',
                'template_id' => null,
            ],
            'text_block' => [
                'content' => '',
                'alignment' => 'left',
                'side_component_key' => '',
                'side_template_id' => null,
                'image_url' => '',
                'width_percent' => 100,
            ],
            'footer' => [
                'tagline' => 'Ontdek de perfecte match tussen jouw vaardigheden en vacatures. Ons AI-platform helpt je de ideale baan te vinden.',
                'logo_url' => '',
                'logo_alt' => '',
                'logo_height' => 12,
                'logo_align' => 'left',
                'quick_links_align' => 'left',
                'quick_links_title' => 'Snelle Links',
                'quick_links' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Vacatures', 'url' => '/jobs'],
                    ['label' => 'Over Ons', 'url' => '/over-ons'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ],
                'support_links_align' => 'left',
                'support_links_title' => 'Ondersteuning',
                'support_links' => [
                    ['label' => 'Help & FAQ', 'url' => '/help'],
                    ['label' => 'Privacy', 'url' => '/privacy'],
                    ['label' => 'Voorwaarden', 'url' => '/voorwaarden'],
                    ['label' => 'Cookies', 'url' => '/privacy#cookies'],
                ],
                'map_postcode' => '',
                'map_huisnummer' => '',
                'map_street' => '',
                'map_city' => '',
                'map_city_only' => false,
                'map_lat' => null,
                'map_lng' => null,
                'map_size' => 'normal',
                'map_zoom' => 17,
                'map_show_address_balloon' => false,
            ],
            'copyright' => '© {year} Nexa Skillmatching. Alle rechten voorbehouden.',
            'section_order' => $sectionOrder,
            'admin_collapsed' => self::defaultAdminCollapsedKeys($sectionOrder),
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
                'featured_services' => true,
                'cta' => true,
                'cta_title' => true,
                'cta_subtitle' => true,
                'cta_buttons' => true,
                'footer' => true,
                'footer_tagline' => true,
                'footer_logo' => true,
                'footer_quick_links' => true,
                'footer_support_links' => true,
                'footer_social' => true,
                'footer_map' => true,
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
            'features' => 'Kenmerken',
            'cta' => 'CTA',
            'carousel' => 'Carousel',
            'cards_ronde_hoeken' => 'Cards ronde hoeken',
            'featured_services' => 'Dienstenblok (scroll-animatie)',
            'email_template' => 'E-mailtemplate (informatieaanvraag)',
            'text_block' => 'Tekstblok (rich text + component)',
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
                ['type' => 'featured_services', 'label' => 'Dienstenblok (scroll-animatie)'],
                ['type' => 'email_template', 'label' => 'E-mailtemplate (informatieaanvraag)'],
                ['type' => 'text_block', 'label' => 'Tekstblok (rich text + component)'],
            ],
            'nextly-template' => [
                ['type' => 'hero', 'label' => 'Hero (banner)'],
                ['type' => 'why_nexa', 'label' => 'Voordelen / Over ons'],
                ['type' => 'features', 'label' => 'Wat wij bieden'],
                ['type' => 'stats', 'label' => 'Stats (4 cijfers)'],
                ['type' => 'cta', 'label' => 'CTA'],
                ['type' => 'carousel', 'label' => 'Carousel'],
                ['type' => 'cards_ronde_hoeken', 'label' => 'Cards ronde hoeken'],
                ['type' => 'featured_services', 'label' => 'Dienstenblok (scroll-animatie)'],
                ['type' => 'email_template', 'label' => 'E-mailtemplate (informatieaanvraag)'],
                ['type' => 'text_block', 'label' => 'Tekstblok (rich text + component)'],
            ],
            'next-landing-vpn' => [
                ['type' => 'hero', 'label' => 'Hero (banner)'],
                ['type' => 'features', 'label' => 'Kenmerken'],
                ['type' => 'cta', 'label' => 'CTA'],
                ['type' => 'carousel', 'label' => 'Carousel'],
                ['type' => 'cards_ronde_hoeken', 'label' => 'Cards ronde hoeken'],
                ['type' => 'featured_services', 'label' => 'Dienstenblok (scroll-animatie)'],
                ['type' => 'email_template', 'label' => 'E-mailtemplate (informatieaanvraag)'],
                ['type' => 'text_block', 'label' => 'Tekstblok (rich text + component)'],
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
            ['type' => 'featured_services', 'label' => $defaults['featured_services']],
            ['type' => 'email_template', 'label' => $defaults['email_template']],
            ['type' => 'text_block', 'label' => $defaults['text_block']],
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

        $base['admin_collapsed'] = self::defaultAdminCollapsedKeys($base['section_order'] ?? []);

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
        $base['admin_collapsed'] = self::defaultAdminCollapsedKeys($base['section_order']);
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
            'footer_social' => true,
            'footer_map' => true,
        ];

        return $base;
    }

    private const HOME_SECTION_BASE_TYPES = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block'];

    private static function homeSectionBaseType(string $sectionKey): ?string
    {
        if (in_array($sectionKey, self::HOME_SECTION_BASE_TYPES, true)) {
            return $sectionKey;
        }
        $base = preg_replace('/_\d+$/', '', $sectionKey);

        return in_array($base, self::HOME_SECTION_BASE_TYPES, true) ? $base : null;
    }

    /**
     * E-mailtemplate-modellen per home_sections-sleutel (standalone sectie en tekstblok "component naast tekst").
     *
     * @param  array<string, mixed>  $homeSections
     * @return array<string, EmailTemplate|null>
     */
    public static function emailTemplatesBySectionKeyForHomeSections(array $homeSections, ?string $templateDbConnection = null): array
    {
        $map = [];
        $connectionConfigured = $templateDbConnection !== null && $templateDbConnection !== ''
            && Config::has("database.connections.{$templateDbConnection}");

        $findTemplate = static function (int $templateId) use ($connectionConfigured, $templateDbConnection): ?EmailTemplate {
            if ($templateId <= 0) {
                return null;
            }
            $template = EmailTemplate::find($templateId);
            if (! $template && $connectionConfigured) {
                $template = EmailTemplate::on($templateDbConnection)->find($templateId);
            }

            return $template;
        };

        $resolveOne = static function (string $sectionKey) use (&$map, $homeSections, $findTemplate): void {
            if (array_key_exists($sectionKey, $map)) {
                return;
            }
            $base = preg_replace('/_\d+$/', '', $sectionKey);
            if ($base !== 'email_template') {
                return;
            }
            $raw = $homeSections[$sectionKey] ?? null;
            if (! is_array($raw)) {
                $map[$sectionKey] = null;

                return;
            }
            $tid = $raw['template_id'] ?? null;
            if ($tid === null || $tid === '') {
                $map[$sectionKey] = null;

                return;
            }
            $map[$sectionKey] = $findTemplate((int) $tid);
        };

        $isEmailFormSideKey = static function (string $sectionKey): bool {
            if (preg_replace('/_\d+$/', '', $sectionKey) === 'email_template') {
                return true;
            }
            if (! FrontendComponentService::isComponentKey($sectionKey)) {
                return false;
            }
            $componentId = FrontendComponentService::componentIdFromKey(
                FrontendComponentService::normalizeComponentSectionKey($sectionKey)
            );

            return strtolower((string) $componentId) === 'website.email_template_section';
        };

        $assignSideTemplate = static function (string $sideKey, ?int $templateId) use (&$map, $findTemplate): void {
            if ($templateId === null || $templateId <= 0) {
                return;
            }
            if (($map[$sideKey] ?? null) !== null) {
                return;
            }
            $map[$sideKey] = $findTemplate($templateId);
        };

        foreach ($homeSections['section_order'] ?? [] as $sectionKey) {
            if (! is_string($sectionKey)) {
                continue;
            }
            if (preg_replace('/_\d+$/', '', $sectionKey) === 'email_template') {
                $resolveOne($sectionKey);
            }
        }

        foreach ($homeSections as $sk => $data) {
            if (! is_string($sk) || ! is_array($data)) {
                continue;
            }
            if (preg_replace('/_\d+$/', '', $sk) !== 'text_block') {
                continue;
            }
            $sideKey = isset($data['side_component_key']) && is_string($data['side_component_key'])
                ? trim($data['side_component_key'])
                : '';
            if ($sideKey === '' || ! $isEmailFormSideKey($sideKey)) {
                continue;
            }
            if (preg_replace('/_\d+$/', '', $sideKey) === 'email_template') {
                $resolveOne($sideKey);
            }
            $sideTemplateId = isset($data['side_template_id']) && is_numeric($data['side_template_id'])
                ? (int) $data['side_template_id']
                : null;
            $assignSideTemplate($sideKey, $sideTemplateId);
            if (($map[$sideKey] ?? null) === null && FrontendComponentService::isComponentKey($sideKey)) {
                $rawSide = $homeSections[$sideKey] ?? null;
                $tid = is_array($rawSide) ? ($rawSide['template_id'] ?? null) : null;
                if ($tid !== null && $tid !== '') {
                    $assignSideTemplate($sideKey, (int) $tid);
                }
            }
            if (($map[$sideKey] ?? null) === null) {
                $companyId = app()->bound('resolved_tenant_id') ? (int) app('resolved_tenant_id') : null;
                if ($companyId) {
                    $candidates = EmailTemplate::query()
                        ->where('company_id', $companyId)
                        ->where('is_active', true)
                        ->where('type', 'informatieaanvraag')
                        ->get();
                    if ($candidates->count() === 1) {
                        $map[$sideKey] = $candidates->first();
                    }
                }
            }
        }

        $skipRootKeys = ['section_order', 'visibility', 'admin_collapsed', 'footer', 'copyright'];
        foreach ($homeSections as $sk => $data) {
            if (! is_string($sk) || in_array($sk, $skipRootKeys, true) || ! is_array($data)) {
                continue;
            }
            if (preg_replace('/_\d+$/', '', $sk) === 'email_template') {
                $resolveOne($sk);
            }
        }

        return $map;
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

                return $rest !== '' ? 'component:'.$rest : $key;
            }

            return $key;
        }, $sectionOrder);
        $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));
        $legacyTaxiComponentKeys = [
            'component:taxiroyaal.tarieven' => 'component:taxi.tarieven',
            'component:taxiroyaal.boekingsmodule' => 'component:taxi.boekingsmodule',
        ];
        $sectionOrder = array_map(static fn ($k) => $legacyTaxiComponentKeys[$k] ?? $k, $sectionOrder);
        $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));
        $sectionOrder = array_values($sectionOrder);
        // Als alle content-secties hetzelfde type zijn (bijv. alleen hero) terwijl het thema meerdere types heeft, gebruik dan de thema-default (herstel foutieve data). Overslaan als de gebruiker componenten heeft toegevoegd.
        $hasComponentKeys = count(array_filter($sectionOrder, fn ($k) => is_string($k) && str_starts_with(strtolower($k), 'component:'))) > 0;
        if (! $hasComponentKeys) {
            $contentKeys = array_filter($sectionOrder, fn ($k) => is_string($k) && ! str_starts_with(strtolower($k), 'component:'));
            $baseTypes = array_filter(array_map([self::class, 'homeSectionBaseType'], $contentKeys));
            $defaultContentCount = count(array_filter($defaults['section_order'], fn ($k) => is_string($k) && ! str_starts_with(strtolower($k), 'component:')));
            if (count($baseTypes) > 0 && count(array_unique($baseTypes)) === 1 && $defaultContentCount > 1) {
                $sectionOrder = $defaults['section_order'];
            }
        }
        // Gebruik de opgeslagen section_order als bron van waarheid; voeg geen ontbrekende default-secties toe,
        // zodat door de gebruiker verwijderde secties/componenten na refresh weg blijven.
        // Als er opgeslagen email_template-data met template_id is maar die key ontbreekt in section_order,
        // voeg die dan toe (na featured_services) zodat het formulier op de FE onder Diensten verschijnt.
        foreach (array_keys($stored) as $key) {
            if (! is_string($key)) {
                continue;
            }
            $base = self::homeSectionBaseType($key);
            if ($base === 'email_template') {
                $raw = $stored[$key] ?? [];
                $tid = is_array($raw) ? ($raw['template_id'] ?? null) : null;
                if ($tid !== null && $tid !== '' && ! in_array($key, $sectionOrder, true)) {
                    $idx = array_search('featured_services', $sectionOrder, true);
                    if ($idx !== false) {
                        array_splice($sectionOrder, $idx + 1, 0, [$key]);
                    } else {
                        $sectionOrder[] = $key;
                    }
                    $sectionOrder = array_values($sectionOrder);
                }
                break;
            }
        }

        $sections = [];
        $nexaTaxiTarievenDefault = [
            'title' => 'Onze tarieven',
            'title_font_size' => '',
            'title_font_style' => 'normal',
            'title_align' => 'left',
            'items' => [
                ['rate_type' => '1-4', 'title' => 't/m 4 personen'],
                ['rate_type' => '5-8', 'title' => '5 t/m 8 personen'],
            ],
        ];
        $nexaTaxiBoekingsmoduleDefault = app(\App\Services\NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
        foreach ($sectionOrder as $sectionKey) {
            if (str_starts_with($sectionKey, 'component:')) {
                if ($sectionKey === 'component:taxi.tarieven') {
                    $canonicalKey = 'component:taxi.tarieven';
                    $raw = $stored[$canonicalKey] ?? $stored['component:taxiroyaal.tarieven'] ?? [];
                    $items = isset($raw['items']) && is_array($raw['items']) ? $raw['items'] : $nexaTaxiTarievenDefault['items'];
                    $allowedFontSizes = array_merge([''], array_map(fn ($px) => $px.'px', range(10, 40, 2)));
                    $title = isset($raw['title']) ? trim((string) $raw['title']) : $nexaTaxiTarievenDefault['title'];
                    if ($title === '') {
                        $title = $nexaTaxiTarievenDefault['title'];
                    }
                    $titleFontSize = isset($raw['title_font_size']) ? trim((string) $raw['title_font_size']) : '';
                    if (! in_array($titleFontSize, $allowedFontSizes, true)) {
                        $titleFontSize = '';
                    }
                    $titleFontStyle = isset($raw['title_font_style']) && in_array($raw['title_font_style'], ['normal', 'bold', 'italic'], true)
                        ? $raw['title_font_style'] : $nexaTaxiTarievenDefault['title_font_style'];
                    $titleAlign = isset($raw['title_align']) && in_array($raw['title_align'], ['left', 'center', 'right'], true)
                        ? $raw['title_align'] : $nexaTaxiTarievenDefault['title_align'];
                    $priceAnimation = isset($raw['price_animation'])
                        ? filter_var($raw['price_animation'], FILTER_VALIDATE_BOOLEAN)
                        : true;
                    $imageFadeDuration = isset($raw['image_fade_duration']) ? max(300, min(5000, (int) $raw['image_fade_duration'])) : 1200;
                    $sections[$canonicalKey] = [
                        'title' => $title,
                        'title_font_size' => $titleFontSize,
                        'title_font_style' => $titleFontStyle,
                        'title_align' => $titleAlign,
                        'price_animation' => $priceAnimation,
                        'image_fade_duration' => $imageFadeDuration,
                        'items' => array_values($items),
                    ];
                } elseif ($sectionKey === 'component:taxi.boekingsmodule') {
                    $canonicalKey = 'component:taxi.boekingsmodule';
                    $raw = $stored[$canonicalKey] ?? $stored['component:taxiroyaal.boekingsmodule'] ?? [];
                    if (! is_array($raw)) {
                        $raw = [];
                    }
                    $sections[$canonicalKey] = app(\App\Services\NexaTaxiBookingPricingService::class)->mergeSectionConfig(array_replace_recursive($nexaTaxiBoekingsmoduleDefault, $raw));
                } elseif ($sectionKey === 'component:website.nexa_modules_overview') {
                    $raw = $stored[$sectionKey] ?? [];
                    if (! is_array($raw)) {
                        $raw = [];
                    }
                    $sections[$sectionKey] = $raw;
                } else {
                    $raw = $stored[$sectionKey] ?? [];
                    if (! is_array($raw)) {
                        $raw = [];
                    }
                    $sections[$sectionKey] = $raw;
                }

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
        foreach ($sectionOrder as $sk) {
            if (! array_key_exists($sk, $visibility)) {
                $visibility[$sk] = true;
            }
        }

        $adminCollapsed = $stored['admin_collapsed'] ?? [];
        if (! is_array($adminCollapsed)) {
            $adminCollapsed = [];
        }

        return array_merge($sections, [
            'footer' => $footer,
            'copyright' => $copyright,
            'section_order' => $sectionOrder,
            'visibility' => $visibility,
            'admin_collapsed' => array_values($adminCollapsed),
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
                $defItems = $defaults['stats']['items'] ?? [['value' => '', 'label' => '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'], ['value' => '', 'label' => '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'], ['value' => '', 'label' => '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'], ['value' => '', 'label' => '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16']];
                if (isset($stats['items']) && is_array($stats['items'])) {
                    $items = [];
                    foreach (array_values($stats['items']) as $idx => $it) {
                        $vs = $it['value_size'] ?? '22';
                        $vs = in_array($vs, ['small', 'medium', 'large'], true) ? $vs : (in_array((int) $vs, range(10, 30, 2), true) ? (string) (int) $vs : '22');
                        $ls = $it['label_size'] ?? '16';
                        $ls = in_array($ls, ['small', 'medium', 'large'], true) ? $ls : (in_array((int) $ls, range(10, 30, 2), true) ? (string) (int) $ls : '16');
                        $items[] = [
                            'value' => $it['value'] ?? '',
                            'label' => $it['label'] ?? '',
                            'value_color' => isset($it['value_color']) && is_string($it['value_color']) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $it['value_color']) ? $it['value_color'] : '',
                            'value_size' => $vs,
                            'label_size' => $ls,
                        ];
                    }
                    while (count($items) < 4) {
                        $items[] = ['value' => $defItems[count($items)]['value'] ?? '', 'label' => $defItems[count($items)]['label'] ?? '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'];
                    }
                    $items = array_slice($items, 0, 4);
                    $bg = isset($stats['background']) && is_string($stats['background']) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $stats['background']) ? $stats['background'] : '';
                    $bgImage = isset($stats['background_image']) && is_string($stats['background_image']) ? trim($stats['background_image']) : '';

                    return ['items' => $items, 'background' => $bg, 'background_image' => $bgImage];
                }
                $legacy = is_array($stats) ? array_values($stats) : [];
                $items = [];
                foreach (array_slice(array_merge($legacy, $defItems), 0, 4) as $it) {
                    $vs = is_array($it) ? ($it['value_size'] ?? '22') : '22';
                    $vs = in_array($vs, ['small', 'medium', 'large'], true) ? $vs : (in_array((int) $vs, range(10, 30, 2), true) ? (string) (int) $vs : '22');
                    $ls = is_array($it) ? ($it['label_size'] ?? '16') : '16';
                    $ls = in_array($ls, ['small', 'medium', 'large'], true) ? $ls : (in_array((int) $ls, range(10, 30, 2), true) ? (string) (int) $ls : '16');
                    $items[] = [
                        'value' => is_array($it) ? ($it['value'] ?? '') : '',
                        'label' => is_array($it) ? ($it['label'] ?? '') : '',
                        'value_color' => (is_array($it) && isset($it['value_color']) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $it['value_color'] ?? '')) ? $it['value_color'] : '',
                        'value_size' => $vs,
                        'label_size' => $ls,
                    ];
                }

                return ['items' => $items, 'background' => '', 'background_image' => ''];
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
                $intervalSeconds = isset($raw['interval_seconds']) ? (int) $raw['interval_seconds'] : (int) ($defaults['carousel']['interval_seconds'] ?? 5);
                $intervalSeconds = max(0, min(120, $intervalSeconds));

                return [
                    'items' => array_values($items),
                    'interval_seconds' => $intervalSeconds,
                ];
            case 'cards_ronde_hoeken':
                $items = $raw['items'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }
                $defItems = $defaults['cards_ronde_hoeken']['items'] ?? [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left', 'image_padding' => 2, 'image_bg_color' => '', 'text_color' => '']];
                $cardsPerRow = isset($raw['cards_per_row']) ? (int) $raw['cards_per_row'] : ($defaults['cards_ronde_hoeken']['cards_per_row'] ?? 4);
                $cardsPerRow = in_array($cardsPerRow, [1, 2, 3, 4, 5, 6], true) ? $cardsPerRow : 4;
                $out = array_values(array_map(function ($row) {
                    $fontSize = isset($row['font_size']) ? max(10, min(24, (int) $row['font_size'])) : 14;
                    $fontStyle = isset($row['font_style']) && in_array($row['font_style'], ['normal', 'bold', 'italic'], true) ? $row['font_style'] : 'normal';
                    $cardSize = isset($row['card_size']) && in_array($row['card_size'], ['small', 'normal', 'large', 'xlarge', 'max', 'total_width'], true) ? $row['card_size'] : 'normal';
                    $textAlign = isset($row['text_align']) && in_array($row['text_align'], ['left', 'center', 'right'], true) ? $row['text_align'] : 'left';
                    $imagePadding = isset($row['image_padding']) ? max(0, min(30, (int) $row['image_padding'])) : 2;
                    $imagePadding = (int) (round($imagePadding / 2) * 2);
                    $imageBgColor = isset($row['image_bg_color']) ? trim((string) $row['image_bg_color']) : '';
                    if ($imageBgColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $imageBgColor)) {
                        $imageBgColor = '';
                    }
                    $textColor = isset($row['text_color']) ? trim((string) $row['text_color']) : '';
                    if ($textColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $textColor)) {
                        $textColor = '';
                    }

                    return [
                        'image_url' => isset($row['image_url']) ? trim((string) $row['image_url']) : '',
                        'text' => isset($row['text']) ? trim((string) $row['text']) : '',
                        'font_size' => $fontSize,
                        'font_style' => $fontStyle,
                        'card_size' => $cardSize,
                        'text_align' => $textAlign,
                        'image_padding' => $imagePadding,
                        'image_bg_color' => $imageBgColor,
                        'text_color' => $textColor,
                    ];
                }, $items));
                if (empty($out)) {
                    $out = $defItems;
                }

                return ['cards_per_row' => $cardsPerRow, 'items' => $out];
            case 'featured_services':
                $items = $raw['items'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }
                $defFs = $defaults['featured_services'] ?? ['title' => 'Diensten', 'subtitle' => '', 'title_font_size_px' => 24, 'subtitle_font_size_px' => 18, 'item_title_font_size_px' => 18, 'item_description_font_size_px' => 14, 'blocks_per_row' => 3, 'blocks_row_width_percent' => 100, 'block_size' => 'medium', 'block_align' => 'center', 'icon_size' => 'medium', 'icon_align' => 'center', 'card_bg_color' => '', 'animation_speed' => 'slow', 'items' => [['icon' => 'light-bulb', 'title' => '', 'description' => '']]];
                $allowedFsPx = range(10, 40, 2);
                $titleFontPx = isset($raw['title_font_size_px']) && $raw['title_font_size_px'] !== '' ? (int) $raw['title_font_size_px'] : (int) ($defFs['title_font_size_px'] ?? 24);
                $titleFontPx = in_array($titleFontPx, $allowedFsPx, true) ? $titleFontPx : 24;
                $subtitleFontPx = isset($raw['subtitle_font_size_px']) && $raw['subtitle_font_size_px'] !== '' ? (int) $raw['subtitle_font_size_px'] : (int) ($defFs['subtitle_font_size_px'] ?? 18);
                $subtitleFontPx = in_array($subtitleFontPx, $allowedFsPx, true) ? $subtitleFontPx : 18;
                $itemTitleFontPx = isset($raw['item_title_font_size_px']) && $raw['item_title_font_size_px'] !== '' ? (int) $raw['item_title_font_size_px'] : (int) ($defFs['item_title_font_size_px'] ?? 18);
                $itemTitleFontPx = in_array($itemTitleFontPx, $allowedFsPx, true) ? $itemTitleFontPx : 18;
                $itemDescFontPx = isset($raw['item_description_font_size_px']) && $raw['item_description_font_size_px'] !== '' ? (int) $raw['item_description_font_size_px'] : (int) ($defFs['item_description_font_size_px'] ?? 14);
                $itemDescFontPx = in_array($itemDescFontPx, $allowedFsPx, true) ? $itemDescFontPx : 14;
                $blocksPerRow = isset($raw['blocks_per_row']) ? (int) $raw['blocks_per_row'] : ($defFs['blocks_per_row'] ?? 3);
                $blocksPerRow = in_array($blocksPerRow, [2, 3, 4], true) ? $blocksPerRow : 3;
                $blocksRowWidthPct = isset($raw['blocks_row_width_percent']) && $raw['blocks_row_width_percent'] !== '' ? (int) $raw['blocks_row_width_percent'] : (int) ($defFs['blocks_row_width_percent'] ?? 100);
                $blocksRowWidthPct = max(1, min(100, $blocksRowWidthPct));
                $blockSize = isset($raw['block_size']) && in_array($raw['block_size'], ['small', 'medium', 'large', 'full'], true) ? $raw['block_size'] : ($defFs['block_size'] ?? 'medium');
                $blockAlign = isset($raw['block_align']) && in_array($raw['block_align'], ['left', 'center', 'right'], true) ? $raw['block_align'] : ($defFs['block_align'] ?? 'center');
                $iconSize = isset($raw['icon_size']) && in_array($raw['icon_size'], ['small', 'medium', 'large'], true) ? $raw['icon_size'] : ($defFs['icon_size'] ?? 'medium');
                $iconAlign = isset($raw['icon_align']) && in_array($raw['icon_align'], ['top', 'center', 'bottom'], true) ? $raw['icon_align'] : ($defFs['icon_align'] ?? 'center');
                $cardBgColor = isset($raw['card_bg_color']) && is_string($raw['card_bg_color']) ? trim($raw['card_bg_color']) : ($defFs['card_bg_color'] ?? '');
                $cardBgColor = $cardBgColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $cardBgColor) ? $cardBgColor : '';
                $animationSpeed = isset($raw['animation_speed']) && in_array($raw['animation_speed'], ['fast', 'normal', 'slow', 'slower'], true) ? $raw['animation_speed'] : ($defFs['animation_speed'] ?? 'slow');
                $out = [
                    'title' => trim((string) ($raw['title'] ?? $defFs['title'] ?? '')),
                    'subtitle' => trim((string) ($raw['subtitle'] ?? $defFs['subtitle'] ?? '')),
                    'title_font_size_px' => $titleFontPx,
                    'subtitle_font_size_px' => $subtitleFontPx,
                    'item_title_font_size_px' => $itemTitleFontPx,
                    'item_description_font_size_px' => $itemDescFontPx,
                    'blocks_per_row' => $blocksPerRow,
                    'blocks_row_width_percent' => $blocksRowWidthPct,
                    'block_size' => $blockSize,
                    'block_align' => $blockAlign,
                    'icon_size' => $iconSize,
                    'icon_align' => $iconAlign,
                    'card_bg_color' => $cardBgColor,
                    'animation_speed' => $animationSpeed,
                    'items' => array_values(array_map(function ($row) {
                        $iconColor = isset($row['icon_color']) && is_string($row['icon_color']) ? trim($row['icon_color']) : '';
                        $iconColor = $iconColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $iconColor) ? $iconColor : '';

                        return [
                            'icon' => trim((string) ($row['icon'] ?? 'light-bulb')),
                            'icon_color' => $iconColor,
                            'title' => trim((string) ($row['title'] ?? '')),
                            'description' => trim((string) ($row['description'] ?? '')),
                        ];
                    }, $items)),
                ];
                if (empty($out['items'])) {
                    $out['items'] = $defFs['items'] ?? [['icon' => 'light-bulb', 'title' => '', 'description' => '']];
                }

                return $out;
            case 'email_template':
                $def = $defaults['email_template'] ?? ['title' => 'Informatie aanvragen', 'template_id' => null];
                $title = isset($raw['title']) && trim((string) $raw['title']) !== '' ? trim((string) $raw['title']) : ($def['title'] ?? 'Informatie aanvragen');
                $templateId = isset($raw['template_id']) && $raw['template_id'] !== '' && is_numeric($raw['template_id'])
                    ? (int) $raw['template_id']
                    : ($def['template_id'] ?? null);

                return [
                    'title' => $title,
                    'template_id' => $templateId,
                ];
            case 'text_block':
                $def = $defaults['text_block'] ?? ['content' => '', 'alignment' => 'left', 'side_component_key' => '', 'side_template_id' => null, 'image_url' => '', 'width_percent' => 100];
                $wp = isset($raw['width_percent']) && is_numeric($raw['width_percent']) ? (int) $raw['width_percent'] : ($def['width_percent'] ?? 100);
                $wp = max(30, min(100, $wp));
                $sideKey = isset($raw['side_component_key']) && is_string($raw['side_component_key']) ? trim($raw['side_component_key']) : ($def['side_component_key'] ?? '');
                $sideTemplateId = isset($raw['side_template_id']) && $raw['side_template_id'] !== '' && is_numeric($raw['side_template_id'])
                    ? (int) $raw['side_template_id']
                    : ($def['side_template_id'] ?? null);
                if ($sideKey === '') {
                    $sideTemplateId = null;
                }

                return [
                    'content' => isset($raw['content']) && is_string($raw['content']) ? $raw['content'] : ($def['content'] ?? ''),
                    'alignment' => isset($raw['alignment']) && in_array($raw['alignment'], ['left', 'center', 'right', 'full'], true) ? $raw['alignment'] : ($def['alignment'] ?? 'left'),
                    'side_component_key' => $sideKey,
                    'side_template_id' => $sideTemplateId,
                    'image_url' => isset($raw['image_url']) && is_string($raw['image_url']) ? trim($raw['image_url']) : ($def['image_url'] ?? ''),
                    'width_percent' => $wp,
                ];
            default:
                return [];
        }
    }
}
