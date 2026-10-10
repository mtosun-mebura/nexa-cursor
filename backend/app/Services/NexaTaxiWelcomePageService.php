<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\WebsitePage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Website van tenant "Nexa Taxi" (nexataxi.nl): B2C taxi boeken via marktplaats.
 */
class NexaTaxiWelcomePageService
{
    public const COMPANY_NAME = 'Nexa Taxi';

    /** Voorkeursslugs om het bedrijf te vinden (lokaal: nexa-taxi-demo). */
    public const COMPANY_SLUGS = ['nexa-taxi-demo', 'nexa-taxi', 'nexataxi'];

    public const PUBLIC_HOSTS = ['nexataxi.nl', 'www.nexataxi.nl'];

    public const HOME_SLUG = 'home';

    public const BOEK_SLUG = 'boek';

    public const HOW_SLUG = 'hoe-het-werkt';

    public const APP_SLUG = 'app';

    public const JOIN_SLUG = 'aansluiten';

    public const CONTACT_SLUG = 'contact';

    public const HOME_BOOKING_SECTION_KEY = 'component:taxi.algemene_boekingsmodule';

    /** @return list<string> */
    public static function marketingSlugs(): array
    {
        return [
            self::BOEK_SLUG,
            self::HOW_SLUG,
            self::APP_SLUG,
            self::JOIN_SLUG,
            self::CONTACT_SLUG,
        ];
    }

    public function __construct(
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    public function resolveCompany(): ?Company
    {
        $byName = Company::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(self::COMPANY_NAME)])
            ->orderBy('id')
            ->first();
        if ($byName !== null) {
            return $byName;
        }

        foreach (self::COMPANY_SLUGS as $slug) {
            $found = Company::query()->where('slug', $slug)->orderBy('id')->first();
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    public function requireCompany(): Company
    {
        $company = $this->resolveCompany();
        if ($company === null) {
            throw new \RuntimeException(
                'Tenant "'.self::COMPANY_NAME.'" niet gevonden. Maak het bedrijf aan vóór nexataxi.nl-pagina\'s.'
            );
        }

        return $company;
    }

    /**
     * Koppel nexataxi.nl / www.nexataxi.nl aan de Nexa Taxi-tenant.
     *
     * @return Collection<int, CompanyDomain>
     */
    public function ensurePublicDomains(?Company $company = null): Collection
    {
        $company ??= $this->requireCompany();
        $created = collect();

        foreach (self::PUBLIC_HOSTS as $index => $host) {
            $host = CompanyDomain::normalizeHost($host);
            $existingOther = CompanyDomain::query()
                ->where('host', $host)
                ->where('company_id', '!=', $company->id)
                ->first();
            if ($existingOther !== null) {
                continue;
            }

            $domain = CompanyDomain::query()->firstOrCreate(
                ['host' => $host],
                [
                    'company_id' => $company->id,
                    'is_primary' => $index === 0,
                ]
            );
            if ((int) $domain->company_id !== (int) $company->id) {
                $domain->company_id = $company->id;
                $domain->save();
            }
            if ($index === 0 && ! $domain->is_primary) {
                CompanyDomain::query()->where('company_id', $company->id)->update(['is_primary' => false]);
                $domain->is_primary = true;
                $domain->save();
            }
            $created->push($domain);
        }

        return $created;
    }

    /**
     * @return Collection<int, WebsitePage>
     */
    public function ensureMarketingPagesExist(?Company $company = null): Collection
    {
        $company ??= $this->requireCompany();
        $this->ensurePublicDomains($company);

        $home = $this->firstOrCreateTenantPage($company->id, self::HOME_SLUG, $this->homePageAttributes());
        $home = $this->ensureBookingVisibleOnHome($home);

        $boek = $this->ensureBookingModuleOnBoekPage(
            $this->firstOrCreateTenantPage($company->id, self::BOEK_SLUG, $this->boekPageAttributes())
        );

        return collect([
            $home,
            $boek,
            $this->firstOrCreateTenantPage($company->id, self::HOW_SLUG, $this->howPageAttributes()),
            $this->firstOrCreateTenantPage($company->id, self::APP_SLUG, $this->appPageAttributes()),
            $this->firstOrCreateTenantPage($company->id, self::JOIN_SLUG, $this->joinPageAttributes()),
            $this->firstOrCreateTenantPage($company->id, self::CONTACT_SLUG, $this->contactPageAttributes()),
        ])->filter()->values();
    }

    public function ensureBookingModuleOnBoekPage(WebsitePage $page): WebsitePage
    {
        return $this->ensureBookingSectionOnPage($page, insertAfterHero: true);
    }

    public function ensureBookingVisibleOnHome(WebsitePage $page): WebsitePage
    {
        return $this->ensureBookingSectionOnPage($page, insertAfterHero: true);
    }

    /**
     * @return Collection<int, WebsitePage>
     */
    public function syncDefaultContent(?Company $company = null): Collection
    {
        $company ??= $this->requireCompany();
        $this->ensureMarketingPagesExist($company);
        $themeSlug = $this->websiteBuilder->getActiveTheme()?->slug ?? 'modern';
        $synced = collect();

        foreach ($this->pageBlueprints($themeSlug) as $slug => $attrs) {
            $page = $this->findTenantPage($company->id, $slug);
            if ($page === null) {
                continue;
            }
            $page->fill([
                'title' => $attrs['title'],
                'menu_title' => $attrs['menu_title'] ?? $page->menu_title,
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

    private function ensureBookingSectionOnPage(WebsitePage $page, bool $insertAfterHero): WebsitePage
    {
        $key = self::HOME_BOOKING_SECTION_KEY;
        $sections = $page->getHomeSections();
        $order = isset($sections['section_order']) && is_array($sections['section_order'])
            ? array_values($sections['section_order'])
            : [];
        $changed = false;

        if (! in_array($key, $order, true)) {
            if ($insertAfterHero) {
                $heroAt = array_search('hero', $order, true);
                $insertAt = $heroAt === false ? 0 : $heroAt + 1;
                array_splice($order, $insertAt, 0, [$key]);
            } else {
                $order[] = $key;
            }
            $sections['section_order'] = $order;
            $changed = true;
        }

        $visibility = isset($sections['visibility']) && is_array($sections['visibility'])
            ? $sections['visibility']
            : [];
        if (($visibility[$key] ?? null) !== true) {
            $visibility[$key] = true;
            $sections['visibility'] = $visibility;
            $changed = true;
        }

        $existing = $sections[$key] ?? null;
        if (! is_array($existing) || $existing === []) {
            $sections[$key] = $this->bookingSectionConfig();
            $changed = true;
        } else {
            $logic = is_array($existing['logic'] ?? null) ? $existing['logic'] : [];
            $logicChanged = false;
            if (($logic['offer_display_mode'] ?? '') !== 'person_range') {
                $logic['offer_display_mode'] = 'person_range';
                $logicChanged = true;
            }
            if (! is_numeric($logic['marketplace_radius_km'] ?? null)) {
                $logic['marketplace_radius_km'] = NearestTaxiTenantResolver::MARKETPLACE_RADIUS_KM;
                $logicChanged = true;
            }
            if ($logicChanged) {
                $existing['logic'] = $logic;
                $sections[$key] = $existing;
                $changed = true;
            }
        }

        if ($changed) {
            $page->home_sections = $sections;
            $page->save();
        }

        return $page->fresh() ?? $page;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function firstOrCreateTenantPage(int $companyId, string $slug, array $attributes): WebsitePage
    {
        $table = (new WebsitePage)->getTable();
        $keys = [
            'slug' => $slug,
            'module_name' => null,
            'company_id' => $companyId,
        ];
        if (Schema::hasColumn($table, 'site_brand')) {
            $attributes['site_brand'] = null;
        }

        $page = WebsitePage::query()->firstOrCreate($keys, $attributes);
        $dirty = false;

        if (empty($page->home_sections) && ! empty($attributes['home_sections'])) {
            $page->home_sections = $attributes['home_sections'];
            $dirty = true;
        }
        if (array_key_exists('is_active', $attributes) && ! (bool) $page->is_active) {
            $page->is_active = true;
            $dirty = true;
        }
        if (array_key_exists('show_in_menu', $attributes)
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
        if (array_key_exists('page_type', $attributes)
            && (string) ($page->page_type ?? '') !== (string) $attributes['page_type']) {
            $page->page_type = $attributes['page_type'];
            $dirty = true;
        }
        if ($dirty) {
            $page->save();
        }

        return $page;
    }

    private function findTenantPage(int $companyId, string $slug): ?WebsitePage
    {
        return WebsitePage::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->whereNull('module_name')
            ->first();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pageBlueprints(string $themeSlug): array
    {
        return [
            self::HOME_SLUG => $this->homePageAttributes($themeSlug),
            self::BOEK_SLUG => $this->boekPageAttributes($themeSlug),
            self::HOW_SLUG => $this->howPageAttributes($themeSlug),
            self::APP_SLUG => $this->appPageAttributes($themeSlug),
            self::JOIN_SLUG => $this->joinPageAttributes($themeSlug),
            self::CONTACT_SLUG => $this->contactPageAttributes($themeSlug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function homePageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Nexa Taxi — Taxi boeken online',
            'menu_title' => 'Home',
            'page_type' => 'home',
            'meta_description' => 'Boek online een taxi via Nexa Taxi. Wij sturen je rit naar de dichtstbijzijnde aangesloten taxicentrale in Nederland.',
            'content' => null,
            'home_sections' => $this->defaultHomeSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 0,
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
            'title' => 'Taxi boeken online | Nexa Taxi',
            'menu_title' => 'Boeken',
            'page_type' => 'custom',
            'meta_description' => 'Boek direct een taxi op nexataxi.nl. Live beschikbaarheid, vaste offerte en rit naar de dichtstbijzijnde aangesloten centrale.',
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
    private function howPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Hoe taxi boeken werkt | Nexa Taxi',
            'menu_title' => 'Hoe het werkt',
            'page_type' => 'custom',
            'meta_description' => 'Zo werkt taxi boeken via Nexa Taxi: aanvraag, dichtstbijzijnde centrale, acceptatie en rit. Duidelijk van begin tot eind.',
            'content' => null,
            'home_sections' => $this->defaultHowSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 2,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Nexa Taxi app — ritten en status op je telefoon',
            'menu_title' => 'App',
            'page_type' => 'custom',
            'meta_description' => 'De Nexa Taxi-app voor reizigers en chauffeurs: boekingen volgen, statusupdates en snelle toegang tot je ritten.',
            'content' => null,
            'home_sections' => $this->defaultAppSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 3,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function joinPageAttributes(?string $themeSlug = null): array
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $themeSlug = $themeSlug ?? ($theme?->slug ?? 'modern');

        return [
            'title' => 'Taxicentrale aansluiten op Nexa Taxi',
            'menu_title' => 'Aansluiten',
            'page_type' => 'custom',
            'meta_description' => 'Sluit je taxicentrale aan op het Nexa-netwerk en ontvang marktplaatsritten van reizigers via nexataxi.nl. Software via NEXA Suite.',
            'content' => null,
            'home_sections' => $this->defaultJoinSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 4,
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
            'title' => 'Contact | Nexa Taxi',
            'menu_title' => 'Contact',
            'page_type' => 'contact',
            'meta_description' => 'Neem contact op met Nexa Taxi over een boeking, support of aansluiting als taxicentrale.',
            'content' => null,
            'home_sections' => $this->defaultContactSections($themeSlug),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 5,
            'frontend_theme_id' => $theme?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingSectionConfig(): array
    {
        $bookingDefaults = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
        $bookingDefaults['title'] = 'Boek je taxi';
        $bookingDefaults['subtitle'] = 'We sturen je rit naar de dichtstbijzijnde aangesloten taxicentrales. Wie accepteert, rijdt jou.';
        $bookingDefaults['logic']['offer_display_mode'] = 'person_range';
        $bookingDefaults['logic']['marketplace_radius_km'] = NearestTaxiTenantResolver::MARKETPLACE_RADIUS_KM;

        return $bookingDefaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultHomeSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultHomeSectionsForTheme($themeSlug);
        $bookingKey = self::HOME_BOOKING_SECTION_KEY;
        $faqKey = 'component:landwind.faq';

        $sections['hero']['title'] = 'Boek een taxi. Wij vinden de dichtstbijzijnde centrale.';
        $sections['hero']['title_highlight'] = 'dichtstbijzijnde centrale';
        $sections['hero']['subtitle'] = 'Online taxi boeken via Nexa Taxi. Eén aanvraag, lokale taxibedrijven in het netwerk, snelle acceptatie.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Hoe het werkt';
        $sections['hero']['cta_secondary_url'] = '/hoe-het-werkt';
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-taxi-booking.png');
        $sections['hero']['overlay'] = true;

        $sections[$bookingKey] = $this->bookingSectionConfig();

        $sections['features']['section_title'] = 'Waarom Nexa Taxi';
        $sections['features']['items'] = [
            [
                'title' => 'Dichtstbijzijnde centrale',
                'description' => 'Je rit gaat naar aangesloten taxibedrijven bij jou in de buurt. Wie accepteert, krijgt de rit.',
                'icon' => 'map-pin',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Direct online boeken',
                'description' => 'Geen telefoon-wachtrij. Vul adressen in, kies personen of voertuigtype, en bevestig.',
                'icon' => 'device-phone-mobile',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Lokaal netwerk',
                'description' => 'Aangesloten centrales rijden met hun eigen vloot. Jij boekt centraal; de rit blijft lokaal.',
                'icon' => 'building-office',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'App & status',
                'description' => 'Volg je rit en status via web of app. Duidelijke stappen van aanvraag tot aankomst.',
                'icon' => 'bell',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
        ];

        $sections[$faqKey] = [
            'eyebrow' => 'Veelgestelde vragen',
            'title' => 'Taxi boeken via Nexa Taxi',
            'subtitle' => 'Kort en duidelijk voor reizigers.',
            'items' => [
                [
                    'question' => 'Wie rijdt mijn rit?',
                    'answer' => 'Een aangesloten taxicentrale bij jou in de buurt. Wij sturen de aanvraag naar kandidaten; wie accepteert, rijdt jou.',
                ],
                [
                    'question' => 'Is dit gratis te gebruiken?',
                    'answer' => 'Boeken is gratis. Je betaalt de ritprijs aan de uitvoerende taxicentrale volgens de getoonde offerte.',
                ],
                [
                    'question' => 'Wat als niemand accepteert?',
                    'answer' => 'We zoeken binnen het netwerk. Krijg je geen match, dan laten we je dat weten zodat je opnieuw kunt proberen of contact kunt opnemen.',
                ],
            ],
        ];

        $sections['cta']['title'] = 'Klaar om te vertrekken?';
        $sections['cta']['subtitle'] = 'Boek in een minuut. Wij koppelen je aan een lokale centrale.';
        $sections['cta']['cta_primary_text'] = 'Boek een taxi';
        $sections['cta']['cta_primary_url'] = '/boek';
        $sections['cta']['cta_secondary_text'] = 'Contact';
        $sections['cta']['cta_secondary_url'] = '/contact';
        $sections['cta']['background_image_url'] = $this->marketingImage('hero-nexa-platform.png');

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $bookingKey, 'features', $faqKey, 'cta'];
        $sections['visibility'][$bookingKey] = true;
        $sections['visibility']['features'] = true;
        $sections['visibility'][$faqKey] = true;
        $sections['visibility']['cta'] = true;
        $sections['visibility']['why_nexa'] = false;
        $sections['visibility']['footer_map'] = false;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultBoekSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $bookingKey = self::HOME_BOOKING_SECTION_KEY;
        $sections[$bookingKey] = $this->bookingSectionConfig();

        $sections['hero']['title'] = 'Taxi boeken online';
        $sections['hero']['title_highlight'] = 'online';
        $sections['hero']['subtitle'] = 'Vul opstap en bestemming in. Wij sturen je rit naar de dichtstbijzijnde aangesloten centrale.';
        $sections['hero']['cta_primary_text'] = '';
        $sections['hero']['cta_primary_url'] = '';
        $sections['hero']['cta_secondary_text'] = '';
        $sections['hero']['cta_secondary_url'] = '';
        $sections['hero']['overlay'] = true;
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-taxi-booking.png');

        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = [
            'eyebrow' => 'Boeken',
            'title' => 'Tips voor een snelle match',
            'subtitle' => 'Zo help je centrales om snel te accepteren.',
            'items' => [
                [
                    'question' => 'Moet ik een account aanmaken?',
                    'answer' => 'Je kunt boeken als gast. Een account helpt om eerdere ritten terug te vinden.',
                ],
                [
                    'question' => 'Kan ik vooruit boeken?',
                    'answer' => 'Ja, kies een gewenste ophaaltijd als die beschikbaar is in de boekingsmodule.',
                ],
            ],
        ];

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
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
    private function defaultHowSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);

        $sections['hero']['title'] = 'Van aanvraag tot rit in vier stappen';
        $sections['hero']['title_highlight'] = 'vier stappen';
        $sections['hero']['subtitle'] = 'Zo werkt taxi boeken via het Nexa-netwerk.';
        $sections['hero']['cta_primary_text'] = 'Boek nu';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'App';
        $sections['hero']['cta_secondary_url'] = '/app';
        $sections['hero']['overlay'] = true;
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-taxi-booking.png');

        $sections['features']['section_title'] = 'Het proces';
        $sections['features']['items'] = [
            [
                'title' => '1. Vul je rit in',
                'description' => 'Opstap, bestemming, personen en gewenste tijd.',
                'icon' => 'pencil-square',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => '2. Wij zoeken centrales',
                'description' => 'Op basis van locatie sturen we de rit naar kandidaten in de buurt.',
                'icon' => 'magnifying-glass',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => '3. Een centrale accepteert',
                'description' => 'De eerste passende centrale die accepteert, krijgt jouw rit.',
                'icon' => 'check-circle',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => '4. Je wordt opgehaald',
                'description' => 'Chauffeur en status volgen via web of app.',
                'icon' => 'truck',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
        ];

        $sections['cta']['title'] = 'Probeer het zelf';
        $sections['cta']['subtitle'] = 'Boek een rit of bekijk de app.';
        $sections['cta']['cta_primary_text'] = 'Boek een taxi';
        $sections['cta']['cta_primary_url'] = '/boek';
        $sections['cta']['cta_secondary_text'] = 'Contact';
        $sections['cta']['cta_secondary_url'] = '/contact';

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'features', 'cta'];
        $sections['visibility']['features'] = true;
        $sections['visibility']['cta'] = true;
        $sections['visibility']['text_block'] = false;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultAppSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);

        $sections['hero']['title'] = 'Nexa Taxi op je telefoon';
        $sections['hero']['title_highlight'] = 'op je telefoon';
        $sections['hero']['subtitle'] = 'Volg ritten, statusupdates en snelle toegang tot boeken — voor reizigers en chauffeurs in het netwerk.';
        $sections['hero']['cta_primary_text'] = 'Boek via de website';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Hoe het werkt';
        $sections['hero']['cta_secondary_url'] = '/hoe-het-werkt';
        $sections['hero']['overlay'] = true;
        $sections['hero']['background_image_url'] = $this->marketingImage('feature-chauffeur-app.png');

        $sections['features']['section_title'] = 'Wat je kunt doen';
        $sections['features']['items'] = [
            [
                'title' => 'Reizigers',
                'description' => 'Boekingen starten of volgen, status zien en contactgegevens bij de hand.',
                'icon' => 'user',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Chauffeurs',
                'description' => 'Geplande ritten, accept/decline en navigatie vanuit de chauffeur-app van aangesloten centrales.',
                'icon' => 'truck',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
        ];

        $sections['cta']['title'] = 'Begin met boeken';
        $sections['cta']['subtitle'] = 'De webboeking werkt direct; de app volgt je rit daarna.';
        $sections['cta']['cta_primary_text'] = 'Boek een taxi';
        $sections['cta']['cta_primary_url'] = '/boek';
        $sections['cta']['cta_secondary_text'] = 'Contact';
        $sections['cta']['cta_secondary_url'] = '/contact';

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'features', 'cta'];
        $sections['visibility']['features'] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultJoinSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $suiteUrl = 'https://nexasuite.nl';

        $sections['hero']['title'] = 'Taxicentrale? Ontvang marktplaatsritten';
        $sections['hero']['title_highlight'] = 'marktplaatsritten';
        $sections['hero']['subtitle'] = 'Sluit aan op het Nexa-netwerk. Reizigers boeken op nexataxi.nl; jij accepteert ritten in jouw regio.';
        $sections['hero']['cta_primary_text'] = 'Naar NEXA Suite';
        $sections['hero']['cta_primary_url'] = $suiteUrl;
        $sections['hero']['cta_secondary_text'] = 'Contact';
        $sections['hero']['cta_secondary_url'] = '/contact';
        $sections['hero']['overlay'] = true;
        $sections['hero']['background_image_url'] = $this->marketingImage('hero-nexa-platform.png');

        $sections['features']['section_title'] = 'Wat je krijgt';
        $sections['features']['items'] = [
            [
                'title' => 'Extra ritten',
                'description' => 'Marktplaatsboekingen van reizigers in jouw dekkingsgebied.',
                'icon' => 'plus-circle',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Eigen software',
                'description' => 'Dispatch, website en chauffeur-app via NEXA Suite — white-label voor jouw merk.',
                'icon' => 'computer-desktop',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
            [
                'title' => 'Jij blijft baas',
                'description' => 'Accepteer of weiger ritten. Jouw vloot, jouw tarieven, jouw klanten.',
                'icon' => 'shield-check',
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ],
        ];

        $sections['cta']['title'] = 'Start met NEXA Suite';
        $sections['cta']['subtitle'] = 'Bekijk het SaaS-platform voor taxibedrijven en vraag een demo aan.';
        $sections['cta']['cta_primary_text'] = 'Ga naar nexasuite.nl';
        $sections['cta']['cta_primary_url'] = $suiteUrl.'/contact';
        $sections['cta']['cta_secondary_text'] = 'Prijzen Suite';
        $sections['cta']['cta_secondary_url'] = $suiteUrl.'/prijzen';

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'features', 'cta'];
        $sections['visibility']['features'] = true;
        $sections['visibility']['cta'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultContactSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);

        $sections['hero']['title'] = 'Contact Nexa Taxi';
        $sections['hero']['title_highlight'] = 'Contact';
        $sections['hero']['subtitle'] = 'Vragen over een boeking, de app of aansluiten als centrale? We helpen je graag.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Aansluiten';
        $sections['hero']['cta_secondary_url'] = '/aansluiten';
        $sections['hero']['overlay'] = true;

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'cta'];
        $sections['visibility']['cta'] = false;
        $sections['visibility']['features'] = false;

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $footer
     * @return array<string, mixed>
     */
    private function taxiFooter(array $footer): array
    {
        $footer['tagline'] = 'Nexa Taxi: online taxi boeken via het Nexa-netwerk. Dichtstbijzijnde aangesloten centrale rijdt jouw rit.';
        $footer['quick_links_title'] = 'Menu';
        $footer['quick_links'] = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Boeken', 'url' => '/boek'],
            ['label' => 'Hoe het werkt', 'url' => '/hoe-het-werkt'],
            ['label' => 'App', 'url' => '/app'],
            ['label' => 'Aansluiten', 'url' => '/aansluiten'],
        ];
        $footer['support_links_title'] = 'Info';
        $footer['support_links'] = [
            ['label' => 'Contact', 'url' => '/contact'],
            ['label' => 'Privacy', 'url' => '/privacy'],
            ['label' => 'Voorwaarden', 'url' => '/voorwaarden'],
            ['label' => 'Disclaimer', 'url' => '/disclaimer'],
            ['label' => 'NEXA Suite', 'url' => 'https://nexasuite.nl'],
        ];

        return CentralWelcomePageService::withLegalSupportLinks($footer);
    }

    private function marketingImage(string $filename): string
    {
        return '/assets/marketing/images/'.$filename;
    }
}
