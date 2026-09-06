<?php

namespace App\Services;

use App\Models\WebsitePage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CentralWelcomePageService
{
    public const TAXI_SLUG = 'taxi';

    public const BOEK_SLUG = 'boek';

    public const CONTRACT_SLUG = 'contractvervoer';

    public const WEBSITE_SLUG = 'website';

    public const CONTACT_SLUG = 'contact';

    public const PRIJZEN_SLUG = 'prijzen';

    public const COMPARISON_SLUG = 'voor-en-nadelen';

    /** @return list<string> */
    public static function marketingSlugs(): array
    {
        return [
            self::TAXI_SLUG,
            self::BOEK_SLUG,
            self::CONTRACT_SLUG,
            self::WEBSITE_SLUG,
            self::PRIJZEN_SLUG,
            self::COMPARISON_SLUG,
            self::CONTACT_SLUG,
        ];
    }

    public static function isMarketingSlug(?string $slug): bool
    {
        if ($slug === null || $slug === '') {
            return false;
        }

        return in_array(strtolower($slug), self::marketingSlugs(), true);
    }

    public function __construct(
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * Zorgt dat er precies één centrale welkom-pagina bestaat (company_id en module_name null).
     */
    public function ensurePageExists(): WebsitePage
    {
        return $this->firstOrCreateCentralPage(
            WebsitePage::CENTRAL_WELCOME_SLUG,
            $this->welcomePageAttributes()
        );
    }

    /**
     * Zorgt dat home + productpagina's van de NEXA Suite-hoofdwebsite bestaan.
     *
     * @return Collection<int, WebsitePage>
     */
    public function ensureMarketingPagesExist(): Collection
    {
        $pages = collect([
            $this->ensurePageExists(),
            $this->firstOrCreateCentralPage(self::TAXI_SLUG, $this->taxiPageAttributes()),
            $this->firstOrCreateCentralPage(self::BOEK_SLUG, $this->boekPageAttributes()),
            $this->firstOrCreateCentralPage(self::CONTRACT_SLUG, $this->contractPageAttributes()),
            $this->firstOrCreateCentralPage(self::WEBSITE_SLUG, $this->websiteBuilderPageAttributes()),
            $this->firstOrCreateCentralPage(self::PRIJZEN_SLUG, $this->prijzenPageAttributes()),
            $this->firstOrCreateCentralPage(self::COMPARISON_SLUG, $this->comparisonPageAttributes()),
            $this->firstOrCreateCentralPage(self::CONTACT_SLUG, $this->contactPageAttributes()),
        ]);

        return $pages->filter()->values();
    }

    /**
     * Zet het prijzenblok op de centrale homepage als het daar nog ontbreekt.
     */
    public function ensurePricingOnHomePage(?WebsitePage $page = null): WebsitePage
    {
        $page ??= $this->findCentralPage(WebsitePage::CENTRAL_WELCOME_SLUG) ?? $this->ensurePageExists();
        $key = NexaPricingService::PACKAGES_SECTION_KEY;
        $sections = $page->getHomeSections();
        $order = isset($sections['section_order']) && is_array($sections['section_order'])
            ? array_values($sections['section_order'])
            : [];
        $removedRaw = $sections['removed_section_keys'] ?? '';
        $removed = is_array($removedRaw)
            ? $removedRaw
            : array_values(array_filter(array_map('trim', explode(',', (string) $removedRaw))));

        if (in_array($key, $removed, true)) {
            return $page;
        }
        if (isset($sections['visibility'][$key]) && $sections['visibility'][$key] === false) {
            return $page;
        }
        if ($order !== [] && ! in_array($key, $order, true)) {
            return $page;
        }

        $changed = false;
        if (! in_array($key, $order, true)) {
            $after = 'component:website.nexa_modules_overview';
            $insertAt = array_search($after, $order, true);
            if ($insertAt === false) {
                $ctaAt = array_search('cta', $order, true);
                $insertAt = $ctaAt === false ? count($order) : $ctaAt;
            } else {
                $insertAt++;
            }
            array_splice($order, $insertAt, 0, [$key]);
            $sections['section_order'] = $order;
            $changed = true;

            $visibility = isset($sections['visibility']) && is_array($sections['visibility'])
                ? $sections['visibility']
                : [];
            if (! array_key_exists($key, $visibility)) {
                $visibility[$key] = true;
                $sections['visibility'] = $visibility;
            }
        }

        if ($changed) {
            $page->home_sections = $sections;
            $page->save();
        }

        return $page->fresh() ?? $page;
    }

    /**
     * Forceer de centrale website-inhoud op de huidige marketing-defaults.
     *
     * @return Collection<int, WebsitePage>
     */
    public function syncDefaultContent(): Collection
    {
        $this->ensureMarketingPagesExist();
        $themeSlug = $this->websiteBuilder->getActiveTheme()?->slug ?? 'modern';

        $synced = collect();
        foreach ($this->pageBlueprints($themeSlug) as $slug => $attrs) {
            $page = $this->findCentralPage($slug);
            if ($page === null) {
                continue;
            }
            $page->fill([
                'title' => $attrs['title'],
                'meta_description' => $attrs['meta_description'],
                'home_sections' => $attrs['home_sections'],
                'is_active' => true,
                'show_in_menu' => $attrs['show_in_menu'],
                'sort_order' => $attrs['sort_order'],
                'page_type' => $attrs['page_type'],
            ]);
            $page->save();
            $synced->push($page);
        }

        return $synced;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function firstOrCreateCentralPage(string $slug, array $attributes): WebsitePage
    {
        $table = (new WebsitePage)->getTable();
        $keys = [
            'slug' => $slug,
            'module_name' => null,
        ];
        if (Schema::hasColumn($table, 'company_id')) {
            $keys['company_id'] = null;
        }

        $page = WebsitePage::query()->firstOrCreate($keys, $attributes);
        $dirty = false;

        if (empty($page->home_sections) && ! empty($attributes['home_sections'])) {
            $page->home_sections = $attributes['home_sections'];
            $dirty = true;
        }
        if ($slug === WebsitePage::CENTRAL_WELCOME_SLUG && array_key_exists('show_in_menu', $attributes)
            && (bool) $page->show_in_menu !== (bool) $attributes['show_in_menu']) {
            $page->show_in_menu = (bool) $attributes['show_in_menu'];
            $dirty = true;
        }
        if (array_key_exists('is_active', $attributes) && ! (bool) $page->is_active) {
            $page->is_active = true;
            $dirty = true;
        }
        if (array_key_exists('show_in_menu', $attributes)
            && $slug !== WebsitePage::CENTRAL_WELCOME_SLUG
            && ! (bool) $page->show_in_menu
            && (bool) $attributes['show_in_menu']) {
            $page->show_in_menu = true;
            $dirty = true;
        }
        if (array_key_exists('menu_title', $attributes)
            && Schema::hasColumn($table, 'menu_title')
            && trim((string) ($page->menu_title ?? '')) === '') {
            $page->menu_title = $attributes['menu_title'];
            $dirty = true;
        }
        if ($dirty) {
            $page->save();
        }

        return $page;
    }

    private function findCentralPage(string $slug): ?WebsitePage
    {
        $q = WebsitePage::query()->where('slug', $slug)->whereNull('module_name');
        $table = (new WebsitePage)->getTable();
        if (Schema::hasColumn($table, 'company_id')) {
            $q->whereNull('company_id');
        }

        return $q->first();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pageBlueprints(string $themeSlug): array
    {
        return [
            WebsitePage::CENTRAL_WELCOME_SLUG => $this->welcomePageAttributes($themeSlug),
            self::TAXI_SLUG => $this->taxiPageAttributes($themeSlug),
            self::BOEK_SLUG => $this->boekPageAttributes($themeSlug),
            self::CONTRACT_SLUG => $this->contractPageAttributes($themeSlug),
            self::WEBSITE_SLUG => $this->websiteBuilderPageAttributes($themeSlug),
            self::PRIJZEN_SLUG => $this->prijzenPageAttributes($themeSlug),
            self::COMPARISON_SLUG => $this->comparisonPageAttributes($themeSlug),
            self::CONTACT_SLUG => $this->contactPageAttributes($themeSlug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function welcomePageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'NEXA Suite',
            'menu_title' => 'Home',
            'page_type' => 'custom',
            'meta_description' => 'Modulair SaaS voor taxibedrijven: eigen website met online boeking, chauffeur-app en optioneel contractvervoer.',
            'content' => null,
            'home_sections' => $this->defaultCentralHomeSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 0,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taxiPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Nexa Taxi',
            'menu_title' => 'Nexa Taxi',
            'page_type' => 'custom',
            'meta_description' => 'Van telefoon naar online boeking en chauffeur-app. Website, dispatch, betaling en chauffeur-PWA in één stack.',
            'content' => null,
            'home_sections' => $this->defaultTaxiSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function boekPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Taxi boeken',
            'menu_title' => 'Boeken',
            'page_type' => 'custom',
            'meta_description' => 'Boek een taxi via NEXA Suite. We sturen je rit naar de dichtstbijzijnde aangesloten taxicentrale.',
            'content' => null,
            'home_sections' => $this->defaultBoekSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contractPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Contractvervoer',
            'menu_title' => 'Contractvervoer',
            'page_type' => 'custom',
            'meta_description' => 'School- en vaste routes zonder Excel: planning, afmeldingen, status en facturatie.',
            'content' => null,
            'home_sections' => $this->defaultContractSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 2,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function websiteBuilderPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Website',
            'menu_title' => 'Website',
            'page_type' => 'custom',
            'meta_description' => 'Website builder: merk + boeking zonder apart CMS. Thema’s, secties, SEO en inzetbare modules.',
            'content' => null,
            'home_sections' => $this->defaultWebsiteBuilderSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 3,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prijzenPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');
        $pricing = app(NexaPricingService::class);
        $start = $pricing->startPrice();
        $website = $pricing->websitePrice();

        return [
            'title' => 'Prijzen',
            'menu_title' => 'Prijzen',
            'page_type' => 'custom',
            'meta_description' => 'NEXA Suite vanaf €'.$start.' per maand. Website live zetten vanaf €'.$website.' eenmalig.',
            'content' => null,
            'home_sections' => $this->defaultPrijzenSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 4,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comparisonPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Voor- en nadelen',
            'menu_title' => 'Voor & nadelen',
            'page_type' => 'custom',
            'meta_description' => 'Herkenbare pijnpunten in taxi versus wat Nexa vandaag oplost: online boeking, chauffeur-app en contractvervoer.',
            'content' => null,
            'home_sections' => $this->defaultComparisonSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => false,
            'sort_order' => 5,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contactPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Contact',
            'menu_title' => 'Contact',
            'page_type' => 'contact',
            'meta_description' => 'Vraag NEXA Suite aan voor jouw taxibedrijf. We nemen zo snel mogelijk contact met je op.',
            'content' => null,
            'home_sections' => $this->defaultContactSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 6,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultCentralHomeSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultHomeSectionsForTheme($themeSlug);

        $sections['hero']['title'] = 'Mis je ritten aan de telefoon? Laat klanten zelf boeken.';
        $sections['hero']['title_highlight'] = 'zelf boeken';
        $sections['hero']['subtitle'] = 'Online boeking, chauffeur-app en contractvervoer in één platform.';
        $sections['hero']['cta_primary_text'] = 'Neem contact op';
        $sections['hero']['cta_primary_url'] = '/contact';
        $sections['hero']['cta_secondary_text'] = 'Bekijk Nexa Taxi';
        $sections['hero']['cta_secondary_url'] = '/taxi';
        $sections['hero']['background_image_url'] = $this->marketingImage('hero-nexa-platform.png');
        $sections['hero']['overlay'] = true;

        $sections['features']['section_title'] = 'Wat de SaaS vandaag kan';
        $sections['features']['items'] = [
            [
                'title' => 'Multi-tenant basis',
                'description' => 'Bedrijven, modules, gebruikers, rollen, website builder, e-mailtemplates, agenda, SaaS-facturatie en AI-chatlaag.',
                'icon' => 'building-office',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Nexa Taxi',
                'description' => 'Website-boeking, tarieven, ritten, chauffeur-dispatch + PWA, klantportaal, contractvervoer en AI-assistent.',
                'icon' => 'truck',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Contractvervoer',
                'description' => 'Vaste routes, groepen, planning, uitzonderingen, afmeldingen (van–tot), verstoringenbanners, maandfacturatie.',
                'icon' => 'calendar-days',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Website builder',
                'description' => 'Merkbare tenant-sites met boekingsmodule, reviews en SEO, zonder apart CMS.',
                'icon' => 'computer-desktop',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'NEXA Garage',
                'description' => 'Werkplaats / werkorders: positioneer als “binnenkort”, niet als live product.',
                'icon' => 'cog-6-tooth',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
        ];

        $galleryKey = 'component:website.screenshot_gallery';
        $tableKey = 'component:website.comparison_table';
        $modulesKey = 'component:website.nexa_modules_overview';
        $whyCardsKey = 'component:vue_material.elevated_cards';
        $statsKey = 'component:landwind.stats_strip';
        $checklistKey = 'component:landwind.feature_checklist';
        $faqKey = 'component:landwind.faq';

        $sections[$whyCardsKey] = $this->whyNexaElevatedCards();
        $sections[$statsKey] = $this->nexaProductStatsStrip();
        $sections[$checklistKey] = $this->nexaBookingChecklist();
        $sections[$faqKey] = $this->nexaMarketingFaq();

        $sections['cta']['title'] = 'Klaar voor meer online boekingen?';
        $sections['cta']['subtitle'] = 'Plan een korte demo. We laten website, dispatch en chauffeur-app zien, met jullie merkkleuren.';
        $sections['cta']['cta_primary_text'] = 'Neem contact op';
        $sections['cta']['cta_primary_url'] = '/contact';
        $sections['cta']['cta_secondary_text'] = 'Bekijk prijzen';
        $sections['cta']['cta_secondary_url'] = '/prijzen';
        $sections['cta']['background_image_url'] = $this->marketingImage('hero-nexa-platform.png');

        $sections[$tableKey] = $this->defaultComparisonTableData();
        $sections[$galleryKey] = [
            'title' => 'Feature-visuals',
            'subtitle' => 'Boeking, chauffeur-app en contractportaal, zoals klanten het zien.',
            'layout' => 'grid',
            'items' => [
                [
                    'image_url' => $this->marketingImage('feature-taxi-booking.png'),
                    'caption' => 'Online taxi boeking',
                    'alt' => 'Online taxi boeking',
                    'crop' => 'none',
                    'url' => '/taxi',
                ],
                [
                    'image_url' => $this->marketingImage('feature-chauffeur-app.png'),
                    'caption' => 'Chauffeur-app',
                    'alt' => 'Chauffeur-app: geplande ritten',
                    'crop' => 'phone',
                ],
                [
                    'image_url' => $this->marketingImage('feature-contract-portal.png'),
                    'caption' => 'Contractportaal',
                    'alt' => 'Contractportaal: vandaag',
                    'crop' => 'portal',
                ],
            ],
        ];
        $sections[$modulesKey] = [
            'eyebrow' => 'Onze modules',
            'title' => 'Taxi eerst. De rest groeit mee',
            'subtitle' => 'Elke module werkt standalone of in combinatie. Begin met vervoer; voeg garage toe wanneer u klaar bent.',
            'items' => [
                [
                    'name' => 'NEXA Taxi',
                    'description' => 'Online boeking, ritten, chauffeur-app, tarieven en facturatie. Van aanvraag tot rit afgerond.',
                    'features' => ['Website-boekingsmodule en ritbeheer', 'Chauffeur-app met accept/decline', 'Klantportaal voor eigen ritten'],
                    'badge' => 'Beschikbaar',
                    'badge_variant' => 'available',
                    'icon' => 'truck',
                    'url' => '/taxi',
                ],
                [
                    'name' => 'Contractvervoer',
                    'description' => 'Schoolroutes, planning, ouderportaal en afmeldingen: upsell binnen Taxi.',
                    'features' => ['Vaste routes en groepen', 'Afmeldingen van–tot', 'Maandfacturatie'],
                    'badge' => 'Beschikbaar',
                    'badge_variant' => 'available',
                    'icon' => 'user-group',
                    'url' => '/contractvervoer',
                ],
                [
                    'name' => 'NEXA Garage',
                    'description' => 'Werkplaatsbeheer voor garages en autobedrijven. Werkorders, planning, onderdelen en klantcommunicatie.',
                    'features' => ['Werkorderbeheer en planning', 'Voertuighistorie per klant', 'Onderdelenvoorraad en leveranciers'],
                    'badge' => 'Binnenkort',
                    'badge_variant' => 'soon',
                    'icon' => 'cog-6-tooth',
                    'url' => '',
                ],
            ],
        ];

        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = [
            'hero',
            'features',
            $tableKey,
            $galleryKey,
            $modulesKey,
            $whyCardsKey,
            $statsKey,
            $checklistKey,
            $faqKey,
            'cta',
        ];
        $sections['visibility'][$tableKey] = true;
        $sections['visibility'][$galleryKey] = true;
        $sections['visibility'][$modulesKey] = true;
        $sections['visibility'][$whyCardsKey] = true;
        $sections['visibility'][$statsKey] = true;
        $sections['visibility'][$checklistKey] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['features'] = true;
        $sections['visibility']['cta'] = true;
        $sections['visibility']['why_nexa'] = false;
        $sections['visibility']['footer_map'] = false;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultTaxiSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $galleryKey = 'component:website.screenshot_gallery';

        $sections['hero']['title'] = 'Laat klanten 24/7 zelf boeken.';
        $sections['hero']['title_highlight'] = 'zelf boeken';
        $sections['hero']['subtitle'] = 'Website, dispatch en chauffeur-app in één systeem.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Bekijk prijzen';
        $sections['hero']['cta_secondary_url'] = '/prijzen';
        $sections['hero']['overlay'] = true;
        $sections['hero']['subtitle_width_percent'] = '50';
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-taxi-booking.png');

        $sections['features'] = [
            'section_title' => 'Wat je verkoopt',
            'items' => [
                [
                    'title' => 'Online boeking',
                    'description' => 'Meerstapsflow op de klantwebsite: route, voertuig, offerte, gegevens. Minder gemiste calls.',
                    'icon' => 'computer-desktop',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Dispatch',
                    'description' => 'Ritten toewijzen, waves, accept/decline, redispatch: overzicht voor de centrale.',
                    'icon' => 'map',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Chauffeur-PWA',
                    'description' => 'Online/offline, inbox, rit starten/afronden, stops, betaling.',
                    'icon' => 'device-phone-mobile',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Klantportaal',
                    'description' => 'Klanten zien eigen ritten en chat over hun boeking, zonder nabelen.',
                    'icon' => 'user-group',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
            ],
        ];

        $sections['text_block'] = [
            'content' => '<p><strong>Waarom dit scoort in sales</strong></p><ul><li>Directe ROI: zichtbare online boekingen</li><li>Chauffeurs zien iets concreets (app), niet alleen “admin”</li><li>Upsellpad naar contractvervoer en AI</li><li>White-label past bij merk van de taxicentrale</li></ul>',
            'alignment' => 'left',
            'side_component_key' => '',
            'side_template_id' => null,
            'image_url' => $this->marketingImage('feature-taxi-booking.png'),
            'width_percent' => 100,
        ];

        $sections[$galleryKey] = [
            'title' => 'Chauffeur-app',
            'subtitle' => 'Inbox, actieve rit en geplande ritten, zoals de chauffeur het ziet.',
            'layout' => 'stack',
            'items' => [
                [
                    'image_url' => $this->marketingImage('feature-chauffeur-inbox.png'),
                    'caption' => 'Nieuwe ritaanvraag',
                    'alt' => 'Chauffeur-app: nieuwe ritaanvraag',
                    'crop' => 'phone',
                ],
                [
                    'image_url' => $this->marketingImage('feature-chauffeur-active.png'),
                    'caption' => 'Actieve rit',
                    'alt' => 'Chauffeur-app: actieve rit',
                    'crop' => 'phone',
                ],
                [
                    'image_url' => $this->marketingImage('feature-chauffeur-app.png'),
                    'caption' => 'Geplande ritten',
                    'alt' => 'Chauffeur-app: geplande ritten in de auto',
                    'crop' => 'phone',
                ],
            ],
        ];

        $bookingKey = 'component:taxi.algemene_boekingsmodule';
        $bookingDefaults = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
        $bookingDefaults['title'] = 'Boek via NEXA Suite';
        $bookingDefaults['subtitle'] = 'We sturen je rit naar de dichtstbijzijnde aangesloten taxicentrale.';
        $sections[$bookingKey] = $bookingDefaults;

        $sections['cta'] = [
            'title' => 'Klaar voor meer online boekingen?',
            'subtitle' => 'We laten website, dispatch en chauffeur-app zien, met jullie merkkleuren.',
            'cta_primary_text' => 'Neem contact op',
            'cta_primary_url' => '/contact',
            'cta_secondary_text' => 'Contractvervoer',
            'cta_secondary_url' => '/contractvervoer',
            'background_image_url' => $this->marketingImage('hero-nexa-platform.png'),
        ];
        $checklistKey = 'component:landwind.feature_checklist';
        $faqKey = 'component:landwind.faq';
        $sections[$checklistKey] = $this->nexaBookingChecklist();
        $sections[$faqKey] = $this->nexaMarketingFaq([
            'eyebrow' => 'Nexa Taxi',
            'title' => 'Vragen over online boeking',
            'subtitle' => 'Website, dispatch en chauffeur-app in één systeem.',
        ]);
        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $bookingKey, 'text_block', 'features', $galleryKey, $checklistKey, $faqKey, 'cta'];
        $sections['visibility'][$galleryKey] = true;
        $sections['visibility']['features'] = true;
        $sections['visibility']['text_block'] = true;
        $sections['visibility'][$bookingKey] = true;
        $sections['visibility'][$checklistKey] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultBoekSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $bookingKey = 'component:taxi.algemene_boekingsmodule';
        $bookingDefaults = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
        $bookingDefaults['title'] = 'Boek een taxi';
        $bookingDefaults['subtitle'] = 'Algemene boeking via NEXA Suite. We koppelen je rit aan de dichtstbijzijnde aangesloten taxicentrale.';
        $sections[$bookingKey] = $bookingDefaults;

        $sections['hero']['title'] = 'Boek een taxi. Wij zoeken de dichtstbijzijnde centrale.';
        $sections['hero']['title_highlight'] = 'dichtstbijzijnde centrale';
        $sections['hero']['subtitle'] = 'Eén boeking op nexasuite.nl. De rit gaat naar een aangesloten taxibedrijf bij jou in de buurt.';
        $sections['hero']['cta_primary_text'] = '';
        $sections['hero']['cta_primary_url'] = '';
        $sections['hero']['cta_secondary_text'] = '';
        $sections['hero']['cta_secondary_url'] = '';
        $sections['hero']['overlay'] = true;
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-taxi-booking.png');

        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = $this->nexaMarketingFaq([
            'eyebrow' => 'Algemene boeking',
            'title' => 'Hoe werkt boeken via NEXA Suite?',
            'subtitle' => 'Je rit gaat naar de dichtstbijzijnde aangesloten taxicentrale.',
        ]);
        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $bookingKey, $faqKey];
        $sections['visibility'][$bookingKey] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = false;
        $sections['visibility']['features'] = false;
        $sections['visibility']['text_block'] = false;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultContractSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $galleryKey = 'component:website.screenshot_gallery';

        $sections['hero']['title'] = 'Vaste ritten zonder Excel.';
        $sections['hero']['title_highlight'] = 'zonder Excel';
        $sections['hero']['subtitle'] = 'School, zorg, zakelijk en privé. Planning en afmelden in één app.';
        $sections['hero']['cta_primary_text'] = 'Neem contact op';
        $sections['hero']['cta_primary_url'] = '/contact';
        $sections['hero']['cta_secondary_text'] = 'Bekijk Nexa Taxi';
        $sections['hero']['cta_secondary_url'] = '/taxi';
        $sections['hero']['overlay'] = true;
        $sections['hero']['subtitle_width_percent'] = '50';
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-contract-portal.png');

        $sections['featured_services'] = array_merge($sections['featured_services'] ?? [], [
            'title' => 'Voor elk vast contract dat je rijdt',
            'subtitle' => 'Zelfde planning, zelfde afmeldingen, zelfde factuur, of je nu kinderen naar school brengt of cliënten naar het ziekenhuis.',
            'blocks_per_row' => 3,
            'items' => [
                [
                    'icon' => 'academic-cap',
                    'title' => 'Leerlingenvervoer',
                    'description' => 'School- en dagopvangritten. Ouders of de school melden af; de chauffeur rijdt geen loze stop.',
                ],
                [
                    'icon' => 'lifebuoy',
                    'title' => 'Zorgcontracten',
                    'description' => 'Vervoer voor zorginstellingen, dagbesteding en woonzorg. Vaste groepen, vaste tijden, één opdrachtgever.',
                ],
                [
                    'icon' => 'building-office-2',
                    'title' => 'Ziekenhuisvervoer',
                    'description' => 'Zittend ziekenvervoer, dialyse, polikliniek. Terugkerende ritten met status en uitzonderingen.',
                ],
                [
                    'icon' => 'briefcase',
                    'title' => 'Zakelijk vervoer',
                    'description' => 'Woon-werk, shuttles tussen vestigingen, vaste relaties. Maandfactuur naar het bedrijf.',
                ],
                [
                    'icon' => 'home',
                    'title' => 'Privévervoer',
                    'description' => 'Vaste privéchauffeur of terugkerende privéritten. Abonnement of per rit, zonder telefoonchaos.',
                ],
                [
                    'icon' => 'user-group',
                    'title' => 'Wmo, shuttles en overig',
                    'description' => 'Doelgroepenvervoer, luchthaven- en hotelshuttles, evenementen. Elke vaste route past in hetzelfde contract.',
                ],
            ],
        ]);

        $sections[$galleryKey] = [
            'title' => 'Contractportaal',
            'subtitle' => 'Ouders en scholen zien status; chauffeurs rijden geen loze kilometers.',
            'layout' => 'stack',
            'items' => [
                [
                    'image_url' => $this->marketingImage('feature-contract-portal.png'),
                    'caption' => 'Vandaag',
                    'alt' => 'Contractportaal: vandaag',
                    'crop' => 'portal',
                ],
                [
                    'image_url' => $this->marketingImage('feature-contract-planning.png'),
                    'caption' => 'Planning',
                    'alt' => 'Contractportaal: weekplanning',
                    'crop' => 'portal',
                ],
            ],
        ];

        $sections['features'] = [
            'section_title' => 'Kerncapaciteiten',
            'items' => [
                [
                    'title' => 'Contracten & facturatie',
                    'description' => 'Contractklanten, abonnementen (vast / per rit / hybride), SEPA-mandaat en maandfacturatie PDF / e-mail / CSV.',
                    'icon' => 'receipt-percent',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Planning',
                    'description' => 'Passagiers, groepen, routeplanner, vaste chauffeur/voertuig. Planning 14 dagen vooruit + uitzonderingen.',
                    'icon' => 'calendar-days',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Ouderportaal (PWA)',
                    'description' => 'Vandaag, weekplanning, heen/retour-status, afmelden van–tot, verstoringenbanners.',
                    'icon' => 'device-phone-mobile',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Live op de rit',
                    'description' => 'Chauffeur ziet afmeldingen; de route wordt herberekend (skipped stops).',
                    'icon' => 'map',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
            ],
        ];

        $sections['text_block'] = [
            'content' => '<p>“Ouders weten of hun kind is opgehaald. De school meldt af voor een week in één keer. Jullie chauffeurs rijden geen loze kilometers.”</p>',
            'alignment' => 'center',
            'side_component_key' => '',
            'side_template_id' => null,
            'image_url' => '',
            'width_percent' => 80,
        ];

        $sections['cta'] = [
            'title' => 'Eerst Taxi, daarna contractvervoer',
            'subtitle' => 'Contractvervoer is de B2B-upsell op Nexa Taxi.',
            'cta_primary_text' => 'Bekijk Nexa Taxi',
            'cta_primary_url' => '/taxi',
            'cta_secondary_text' => 'Neem contact op',
            'cta_secondary_url' => '/contact',
            'background_image_url' => $this->marketingImage('hero-nexa-platform.png'),
        ];
        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = $this->nexaMarketingFaq([
            'eyebrow' => 'Contractvervoer',
            'title' => 'Vragen over vaste ritten',
            'subtitle' => 'School, zorg, zakelijk en privé: dezelfde planning, dezelfde factuur.',
            'items' => [
                ['question' => 'Is contractvervoer een apart product?', 'answer' => 'Nee. Het is de B2B-upsell op Nexa Taxi: dezelfde tenant, dezelfde chauffeurs, extra contracten, groepen en ouderportaal.'],
                ['question' => 'Kunnen ouders of de school zelf afmelden?', 'answer' => 'Ja. In het contractportaal meld je af van–tot. De chauffeur ziet skipped stops; geen loze kilometers.'],
                ['question' => 'Welke contracttypes passen erin?', 'answer' => 'Leerlingenvervoer, zorg, ziekenhuisvervoer, zakelijk, privé en shuttles. Vaste routes en groepen, maandfacturatie naar de opdrachtgever.'],
                ['question' => 'Hoe werkt de planning?', 'answer' => 'Passagiers, groepen, vaste chauffeur of voertuig. Planning tot 14 dagen vooruit, plus uitzonderingen en verstoringenbanners.'],
            ],
        ]);
        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'featured_services', $galleryKey, 'features', 'text_block', $faqKey, 'cta'];
        $sections['visibility']['featured_services'] = true;
        $sections['visibility'][$galleryKey] = true;
        $sections['visibility']['features'] = true;
        $sections['visibility']['text_block'] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultWebsiteBuilderSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $galleryKey = 'component:website.screenshot_gallery';

        $sections['hero']['title'] = 'Website builder: merk + boeking zonder apart CMS';
        $sections['hero']['title_highlight'] = 'Website builder';
        $sections['hero']['subtitle'] = 'Elke tenant krijgt een eigen site met thema’s, secties, SEO en inzetbare modules (boeking, reviews, vacatures).';
        $sections['hero']['cta_primary_text'] = 'Bekijk Nexa Taxi';
        $sections['hero']['cta_primary_url'] = '/taxi';
        $sections['hero']['cta_secondary_text'] = 'Naar overzicht';
        $sections['hero']['cta_secondary_url'] = '/';
        $sections['hero']['overlay'] = true;

        $sections[$galleryKey] = [
            'title' => '',
            'subtitle' => '',
            'layout' => 'stack',
            'items' => [
                [
                    'image_url' => $this->marketingImage('feature-website-builder.png'),
                    'caption' => 'Website builder',
                    'alt' => 'Website builder',
                    'crop' => 'none',
                ],
            ],
        ];

        $sections['features'] = [
            'section_title' => 'Waarom dit verkoopt',
            'items' => [
                [
                    'title' => 'Online zichtbaar',
                    'description' => 'Taxibedrijf wil “online zichtbaar”: de website is de haak.',
                    'icon' => 'globe-europe-africa',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Boeking op dezelfde site',
                    'description' => 'Boekingsmodule op dezelfde site = korte weg naar omzet.',
                    'icon' => 'truck',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'White-label',
                    'description' => 'Past bij het merk van de centrale; jij levert het platform.',
                    'icon' => 'sparkles',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
            ],
        ];

        $sections['cta'] = [
            'title' => 'Klaar voor meer online boekingen?',
            'subtitle' => 'Plan een korte demo. We laten website, dispatch en chauffeur-app zien.',
            'cta_primary_text' => 'Neem contact op',
            'cta_primary_url' => '/contact',
            'cta_secondary_text' => 'Nexa Taxi',
            'cta_secondary_url' => '/taxi',
            'background_image_url' => $this->marketingImage('hero-nexa-platform.png'),
        ];
        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = $this->nexaMarketingFaq([
            'eyebrow' => 'Website builder',
            'title' => 'Vragen over de tenant-site',
            'subtitle' => 'De website is het voorportaal; boeking en reviews zitten op dezelfde site.',
            'items' => [
                ['question' => 'Hebben we nog een apart CMS nodig?', 'answer' => 'Nee. Elke tenant krijgt een eigen site met thema’s, secties en SEO. Pagina’s beheer je in de website builder.'],
                ['question' => 'Kan de boeking op dezelfde site?', 'answer' => 'Ja. De boekingsmodule, reviews en vacatures zitten op dezelfde tenant-site — korte weg van bezoek naar rit.'],
                ['question' => 'Ziet de site eruit als ons merk?', 'answer' => 'White-label: merkkleuren, logo en eigen domein. Jij levert het platform; de centrale houdt het gezicht naar de klant.'],
                ['question' => 'Kunnen we later van thema wisselen?', 'answer' => 'Ja. Pagina’s en inhoud blijven staan; alleen het jasje van de site verandert.'],
            ],
        ]);
        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $galleryKey, 'features', $faqKey, 'cta'];
        $sections['visibility'][$galleryKey] = true;
        $sections['visibility']['features'] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPrijzenSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $packagesKey = NexaPricingService::PACKAGES_SECTION_KEY;
        $pricingService = app(NexaPricingService::class);
        $pricing = $pricingService->get();
        $startPrice = $pricingService->startPrice($pricing);
        $websitePrice = $pricingService->websitePrice($pricing);

        $sections['hero']['title'] = 'Duidelijke prijzen. Start bij € '.$startPrice.' per maand.';
        $sections['hero']['title_highlight'] = '€ '.$startPrice.' per maand';
        $sections['hero']['subtitle'] = 'Kies Start, Pro of Business. De details staan in de pakketten hieronder.';
        $sections['hero']['cta_primary_text'] = 'Neem contact op';
        $sections['hero']['cta_primary_url'] = '/contact';
        $sections['hero']['cta_secondary_text'] = 'Bekijk Nexa Taxi';
        $sections['hero']['cta_secondary_url'] = '/taxi';
        $sections['hero']['overlay'] = true;
        $sections['hero']['subtitle_width_percent'] = '50';
        $sections['hero']['background_image_url'] = $this->marketingImage('hero-nexa-platform.png');

        $sections[$packagesKey] = $pricingService->sectionPayload($pricing);

        $sections['features'] = [
            'section_title' => 'Wat je terugverdient',
            'items' => [
                [
                    'title' => 'Twee extra ritten per week',
                    'description' => 'Het Pro-pakket verdien je terug als de site twee of drie boekingen per week oplevert die je nu mist.',
                    'icon' => 'banknotes',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Geen commissie per rit',
                    'description' => 'Je betaalt een vast maandbedrag. De ritomzet blijft van jou, niet van een marktplaats.',
                    'icon' => 'receipt-percent',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
                [
                    'title' => 'Website een keer, daarna groeien',
                    'description' => 'Live-zetten vanaf € '.$websitePrice.'. Contractvervoer en extra vestigingen voeg je later toe, zonder opnieuw te beginnen.',
                    'icon' => 'sparkles',
                    'icon_size' => 'medium',
                    'icon_align' => 'center',
                ],
            ],
        ];

        $sections['cta'] = [
            'title' => 'Welk pakket past bij jouw ritten?',
            'subtitle' => 'Vertel hoeveel chauffeurs je hebt en of je contractvervoer rijdt. We sturen een voorstel.',
            'cta_primary_text' => 'Neem contact op',
            'cta_primary_url' => '/contact',
            'cta_secondary_text' => 'Nexa Taxi',
            'cta_secondary_url' => '/taxi',
            'background_image_url' => $this->marketingImage('hero-nexa-platform.png'),
        ];
        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = $this->nexaMarketingFaq([
            'eyebrow' => 'Prijzen',
            'title' => 'Vragen over pakketten',
            'subtitle' => 'Vast maandbedrag. Geen commissie per rit.',
            'items' => [
                ['question' => 'Betaal ik commissie per rit?', 'answer' => 'Nee. Je betaalt een vast maandbedrag. De ritomzet blijft van jou, niet van een marktplaats.'],
                ['question' => 'Wat zit er in Start, Pro en Business?', 'answer' => 'De pakketten staan hierboven. Start is de instap; Pro voegt dispatch en chauffeur-app toe; Business is voor meerdere vestigingen en contractvervoer.'],
                ['question' => 'Wat kost de website live zetten?', 'answer' => 'Live-zetten vanaf € '.$websitePrice.'. Daarna groei je met contractvervoer of extra vestigingen, zonder opnieuw te beginnen.'],
                ['question' => 'Kan ik later upgraden?', 'answer' => 'Ja. Begin met taxi; voeg contractvervoer of extra modules toe wanneer je klaar bent.'],
            ],
        ]);
        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $packagesKey, 'features', $faqKey, 'cta'];
        $sections['visibility'][$packagesKey] = true;
        $sections['visibility']['features'] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultComparisonSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $tableKey = 'component:website.comparison_table';

        $sections['hero']['title'] = 'Voor- en nadelen van Nexa.';
        $sections['hero']['title_highlight'] = 'Voor- en nadelen';
        $sections['hero']['subtitle'] = 'Herkenbare pijnpunten in taxi versus wat Nexa vandaag oplost.';
        $sections['hero']['cta_primary_text'] = 'Neem contact op';
        $sections['hero']['cta_primary_url'] = '/contact';
        $sections['hero']['cta_secondary_text'] = 'Bekijk prijzen';
        $sections['hero']['cta_secondary_url'] = '/prijzen';
        $sections['hero']['overlay'] = true;
        $sections['hero']['subtitle_width_percent'] = '55';
        $sections['hero']['background_image_url'] = $this->marketingImage('hero-nexa-platform.png');

        $sections[$tableKey] = $this->defaultComparisonTableData([
            'title' => 'Pijnpunt versus NEXA-antwoord',
            'subtitle' => 'Scroll: de blokken en regels komen staggered in beeld.',
        ]);

        $sections['cta'] = [
            'title' => 'Klaar om telefoonchaos te ruilen voor online boekingen?',
            'subtitle' => 'Plan een korte demo. We laten website, dispatch en chauffeur-app zien.',
            'cta_primary_text' => 'Neem contact op',
            'cta_primary_url' => '/contact',
            'cta_secondary_text' => 'Nexa Taxi',
            'cta_secondary_url' => '/taxi',
            'background_image_url' => $this->marketingImage('hero-nexa-platform.png'),
        ];
        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = $this->nexaMarketingFaq();
        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $tableKey, $faqKey, 'cta'];
        $sections['visibility']['hero'] = true;
        $sections['visibility'][$tableKey] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * Sample content for builder block-preview (comparison table).
     *
     * @return array<string, mixed>
     */
    public function comparisonTableSample(): array
    {
        return $this->defaultComparisonTableData();
    }

    /**
     * Gedeelde content voor het geanimeerde voor-/nadelen-blok (home + /voor-en-nadelen).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function defaultComparisonTableData(array $overrides = []): array
    {
        $base = [
            'title' => 'Herkenbaar? Dit lost Nexa vandaag op',
            'subtitle' => '',
            'left_heading' => 'Pijnpunt',
            'right_heading' => 'NEXA-antwoord',
            'left_color' => '#dc2626',
            'right_color' => '#16a34a',
            'layout' => 'columns',
            'left_width_percent' => '50',
            'right_width_percent' => '50',
            'cons' => [
                ['text' => 'Geen online boekingen / verloren calls'],
                ['text' => 'Chauffeurs via WhatsApp/Excel'],
                ['text' => 'Schoolvervoer handmatig afmelden'],
                ['text' => 'Losse website + losse app'],
            ],
            'pros' => [
                ['text' => 'Boekingsmodule op eigen website + tarieven'],
                ['text' => 'Dispatch + chauffeur-PWA met inbox'],
                ['text' => 'Contractportaal met status & afmeldingen'],
                ['text' => 'Alles in één SaaS, white-label per tenant'],
            ],
            'rows' => [
                ['left' => 'Geen online boekingen / verloren calls', 'right' => 'Boekingsmodule op eigen website + tarieven'],
                ['left' => 'Chauffeurs via WhatsApp/Excel', 'right' => 'Dispatch + chauffeur-PWA met inbox'],
                ['left' => 'Schoolvervoer handmatig afmelden', 'right' => 'Contractportaal met status & afmeldingen'],
                ['left' => 'Losse website + losse app', 'right' => 'Alles in één SaaS, white-label per tenant'],
            ],
        ];

        return array_merge($base, $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultContactSections(string $themeSlug): array
    {
        $template = app(NexaContactAanvraagEmailTemplateService::class)->ensureExists();
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);

        $sections['hero']['title'] = 'Plan een gesprek. We kijken naar jouw ritten.';
        $sections['hero']['title_highlight'] = 'jouw ritten';
        $sections['hero']['subtitle'] = 'Vertel kort wat je taxibedrijf nodig heeft. We nemen contact op over onboarding of een voorstel.';
        $sections['hero']['cta_primary_text'] = 'Naar het formulier';
        $sections['hero']['cta_primary_url'] = '#info-request-section-email_template';
        $sections['hero']['cta_secondary_text'] = '';
        $sections['hero']['cta_secondary_url'] = '';
        $sections['hero']['overlay'] = true;

        $sections['email_template'] = [
            'title' => 'Nieuwe aanvraag',
            'template_id' => $template->id,
        ];

        $sections['footer'] = $this->centralFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} NEXA Suite. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'email_template'];
        $sections['visibility']['hero'] = true;
        $sections['visibility']['email_template'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function whyNexaElevatedCards(): array
    {
        return [
            'eyebrow' => 'Waarom NEXA',
            'title' => 'Waarom ondernemers voor NEXA kiezen',
            'subtitle' => 'Minder telefoonchaos, meer boekingen. White-label en klaar om te groeien.',
            'items' => [
                [
                    'title' => 'Minder telefoonchaos',
                    'text' => 'Klanten boeken zelf op de website. De telefoon blijft vrij voor uitzonderingen, niet voor elke standaardrit.',
                    'accent' => '#2563eb',
                ],
                [
                    'title' => 'White-label op jullie merk',
                    'text' => 'Website, boeking en chauffeur-app in jullie kleuren en logo. De centrale ziet NEXA; de klant ziet jullie.',
                    'accent' => '#7c4dff',
                ],
                [
                    'title' => 'Klaar om te groeien',
                    'text' => 'Start met taxi. Voeg contractvervoer of extra vestigingen toe zonder een nieuw systeem.',
                    'accent' => '#0d9488',
                ],
            ],
        ];
    }

    /**
     * Productfeiten — geen verzonnen rit- of boekingscijfers.
     *
     * @return array<string, mixed>
     */
    private function nexaProductStatsStrip(): array
    {
        return [
            'eyebrow' => 'In het kort',
            'title' => 'Wat je krijgt, zonder kleine lettertjes',
            'subtitle' => 'Eén platform. Geen commissie per rit. Altijd boekbaar.',
            'items' => [
                ['value' => '24', 'suffix' => '/7', 'label' => 'Online boekbaar'],
                ['value' => '1', 'suffix' => '', 'label' => 'Platform voor website, dispatch en app'],
                ['value' => '0', 'suffix' => '', 'label' => 'Commissie per rit'],
                ['value' => '100', 'suffix' => '%', 'label' => 'White-label per tenant'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nexaBookingChecklist(): array
    {
        return [
            'eyebrow' => 'Van boeking tot rit',
            'title' => 'Zo loopt een rit door NEXA',
            'subtitle' => 'Website, dispatch en chauffeur-app in één keten — zonder nabelen.',
            'image_url' => $this->marketingImage('feature-taxi-booking.png'),
            'items' => [
                ['text' => 'Klant vult ophaal- en bestemming in op jullie site'],
                ['text' => 'Dispatch ziet de rit direct in het overzicht'],
                ['text' => 'Chauffeur accepteert vanaf de telefoon'],
                ['text' => 'Status terug naar de klant, zonder WhatsApp-ketting'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function nexaMarketingFaq(array $overrides = []): array
    {
        $base = [
            'eyebrow' => 'FAQ',
            'title' => 'Veelgestelde vragen',
            'subtitle' => 'Antwoorden voor taxiondernemers die online willen groeien.',
            'items' => [
                ['question' => 'Is NEXA white-label?', 'answer' => 'Ja. Website, boeking en chauffeur-app lopen in jullie merkkleuren en logo. Klanten zien jullie centrale, niet een marktplaats.'],
                ['question' => 'Kunnen klanten 24/7 boeken?', 'answer' => 'Ja. De boekingsmodule staat op jullie website. Geen gemiste calls ’s avonds of in het weekend.'],
                ['question' => 'Werkt de chauffeur-app op elke telefoon?', 'answer' => 'Het is een PWA: online/offline, inbox, rit starten en afronden. Geen aparte store-app verplicht.'],
                ['question' => 'Rijden we ook school- of zorgvervoer?', 'answer' => 'Contractvervoer is de upsell op Nexa Taxi: vaste routes, groepen, ouderportaal en afmeldingen van–tot.'],
                ['question' => 'Betaal ik commissie per rit?', 'answer' => 'Nee. Vast maandbedrag per pakket. De ritomzet blijft van jou.'],
                ['question' => 'Hoe starten we?', 'answer' => 'Plan een korte demo. We laten website, dispatch en chauffeur-app zien. Daarna onboarding met jullie merkkleuren.'],
            ],
        ];

        return array_merge($base, $overrides);
    }

    /**
     * @param  array<string, mixed>  $footer
     * @return array<string, mixed>
     */
    private function centralFooter(array $footer): array
    {
        $footer['tagline'] = 'Modulair SaaS voor taxibedrijven: website, online boeking, chauffeur-app en contractvervoer. White-label per tenant.';
        $footer['quick_links_title'] = 'Product';
        $footer['quick_links'] = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Nexa Taxi', 'url' => '/taxi'],
            ['label' => 'Contractvervoer', 'url' => '/contractvervoer'],
            ['label' => 'Website', 'url' => '/website'],
        ];
        $footer['support_links_title'] = 'Account';
        $footer['support_links'] = [
            ['label' => 'Admin', 'url' => '/admin/login'],
            ['label' => 'Privacy', 'url' => '/privacy'],
        ];

        return $footer;
    }

    private function marketingImage(string $filename): string
    {
        return '/assets/marketing/images/'.$filename;
    }
}
