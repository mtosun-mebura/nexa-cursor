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

        $sections['hero']['title'] = 'Boek een taxi bij een centrale in de buurt';
        $sections['hero']['title_highlight'] = 'centrale in de buurt';
        $sections['hero']['subtitle'] = 'Op nexataxi.nl en in de NEXA Taxi-app. U vult ophaaladres en bestemming in. De rit gaat naar aangesloten taxibedrijven. Wie accepteert, haalt u op.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Taxibedrijf aansluiten';
        $sections['hero']['cta_secondary_url'] = '/aansluiten';
        $this->applyStreetHero($sections['hero']);

        $sections[$bookingKey] = $this->bookingSectionConfig();

        $sections['features']['section_title'] = 'Wat de app doet';
        $sections['features']['items'] = [
            $this->feature('Taxi boeken', 'De klant tikt Taxi boeken. Ophaaladres, bestemming en ophaalmoment. De kaart toont hoeveel taxi’s online zijn. Daarna passagiers, bagage en contact. Een account is niet nodig.', 'map-pin'),
            $this->feature('Ophaalcode', 'Bij een marktplaatsrit ziet de klant vier cijfers: Ophaalcode voor de chauffeur. De chauffeur ziet die code niet. Alleen de juiste code start de rit.', 'shield-check'),
            $this->feature('Status van de taxi', 'Na acceptatie: naam van de chauffeur, kenteken, autonaam, de centrale en de verwachte aankomst als de locatie bekend is. Een tijdlijn toont de statusupdates.', 'bell'),
            $this->feature('Contractvervoer', 'De contractant heeft Ritten, Planning, Navigatie, Afmeldingen en Profiel. Alleen die rol markeert Opgehaald, tikt Starten en later Afronden.', 'building-office'),
            $this->feature('Contractouder', 'De ouder gebruikt dezelfde tabbladen en volgt het kind op de kaart. Starten en Afronden ontbreken. Afmeldingen gelden voor één of meer dagen.', 'user-circle'),
            $this->feature('Chauffeur', 'Aanvragen met Accepteren of Weigeren. Online kan pas na een gekozen voertuig. Ophalen en Afzetten openen de route in Google Maps.', 'truck'),
        ];

        $sections['text_block'] = $this->shotBlock(
            '<h2>Klanten boeken zelf een taxi</h2>'
            .'<p>Op Boeken vult de klant het ophaaladres in, of tikt de locatieknop, daarna de bestemming en het ophaalmoment. Onder de velden ligt de kaart. Daar staat hoeveel taxi’s in de buurt online zijn.</p>'
            .'<p>Zodra beide adressen gekozen zijn, verschijnt Rit &amp; prijs met afstand, rijtijd en prijs. Taxi aanvragen stuurt de rit naar aangesloten bedrijven. Zijn er geen taxi’s beschikbaar, dan gaat de rit niet de deur uit.</p>'
            .'<p>Op deze site staat dezelfde algemene boeking. <a href="/boek">Boek een taxi</a> zonder de app te installeren.</p>',
            $this->appShot('klant-boeken.png'),
            'left'
        );
        $sections['text_block_2'] = $this->shotBlock(
            '<h2>Drie ingangen, één app</h2>'
            .'<p>De app opent met Maak je keuze. Taxi boeken is voor de klant: adres, prijs, boeken en live volgen, in de app.</p>'
            .'<p>Inloggen is voor chauffeur, NEXA-marktplaats, netwerk en contract. De app opent het scherm dat bij de rollen hoort. Heeft iemand meer dan één rol, dan verschijnt Kies je scherm.</p>'
            .'<p>Taxibedrijf staat apart: Aanmelden op de NEXA Taxi marktplaats. Dat registreert het bedrijf op het NEXA-netwerk. Het is geen aanmelding bij marktplaats.nl.</p>',
            $this->appShot('welcome.png'),
            'right'
        );
        $sections['text_block_3'] = $this->shotBlock(
            '<h2>Inloggen voor chauffeur en contract</h2>'
            .'<p>E-mailadres, en de eerste keer Code sturen. Na de code uit de mail kiest u een wachtwoord. Daarna is inloggen e-mail plus wachtwoord.</p>'
            .'<p>De chauffeur krijgt de tabbladen Ritten, Aanvragen, Planning, Inkomsten (als dat recht aanstaat) en Profiel. Op Aanvragen zet hij zich Online. Zonder gekozen auto lukt dat niet.</p>'
            .'<p>Contractant en contractouder komen na dezelfde login in het contractscherm. Het verschil tussen die twee rollen staat hieronder.</p>',
            $this->appShot('login.png'),
            'left'
        );
        $sections['text_block_4'] = [
            'content' => '<h2>Contractouder: meekijken, niet de rit afmaken</h2>'
                .'<p>Een contractouder is de ouder of begeleider bij contractvervoer, bijvoorbeeld een schoolrit of een zorgrit. Die logt in met het contractaccount. In Profiel staat de rol Contractouder.</p>'
                .'<p>De tabbladen zijn dezelfde als bij de contractant: Ritten, Planning, Navigatie, Afmeldingen en Profiel. Starten, Afronden en de banner Bestemming bereikt ontbreken. De ouder volgt en meldt af. De contractant rondt de rit af.</p>'
                .'<p>Navigatie toont de kaart met de route van elk kind in een eigen kleur, en de taxi als die live is. Tik op een naam om de kleur van dat kind te wijzigen. Zonder open rit staat er: Geen open rit om te volgen. De knop Start navigatie, die de route langs de haltes opent, is er voor de ouder niet.</p>'
                .'<p>Onder Afmeldingen meldt de ouder een passagier af voor één of meer dagen, met een reden. Intrekken kan zolang de dag nog openstaat. Planning schakelt tussen Dag en Week; in de weekweergave kiest u een dag, de lijst eronder blijft die dag.</p>',
            'alignment' => 'left',
            'side_component_key' => '',
            'side_template_id' => null,
            'image_url' => $this->campaignImage('scherm-contractouder-navigatie.png'),
            'width_percent' => 100,
        ];

        $sections[$faqKey] = [
            'eyebrow' => 'Veelgestelde vragen',
            'title' => 'Taxi boeken via Nexa Taxi',
            'subtitle' => 'Voor reizigers en voor de ouder die een contractrit volgt.',
            'items' => [
                [
                    'question' => 'Wie rijdt mijn rit?',
                    'answer' => 'Een aangesloten taxicentrale bij u in de buurt. De aanvraag gaat naar kandidaten. Wie accepteert, rijdt de rit.',
                ],
                [
                    'question' => 'Wat is de ophaalcode?',
                    'answer' => 'Vier cijfers op het scherm van de klant, alleen bij een marktplaatsrit. De chauffeur ziet ze niet. Die tikt Klant opgehaald — voer code in en daarna Start rit. Een foute code laat de rit op geaccepteerd staan.',
                ],
                [
                    'question' => 'Wat doet een contractouder?',
                    'answer' => 'Die volgt de rit van het kind of de passagier: Ritten, Planning en Navigatie, plus afmelden. De contractouder start de rit niet en rondt hem niet af.',
                ],
                [
                    'question' => 'Wat als niemand accepteert?',
                    'answer' => 'De app blijft zoeken en zegt dat de rit is aangeboden aan taxibedrijven in de buurt. De klant kan wachten of annuleren. Blijft een rit te lang open, dan vraagt de app om te blijven wachten of te annuleren.',
                ],
            ],
        ];

        $sections['cta']['title'] = 'Taxicentrale, de rit ligt bij u';
        $sections['cta']['subtitle'] = 'Reizigers boeken hier en in de app. U accepteert in uw regio, met uw vloot en uw chauffeur.';
        $sections['cta']['cta_primary_text'] = 'Boek een taxi';
        $sections['cta']['cta_primary_url'] = '/boek';
        $sections['cta']['cta_secondary_text'] = 'Bedrijf aansluiten';
        $sections['cta']['cta_secondary_url'] = '/aansluiten';
        $sections['cta']['background_image_url'] = $this->campaignImage('hero-straat.jpg');

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', $bookingKey, 'features', 'text_block', 'text_block_2', 'text_block_3', 'text_block_4', $faqKey, 'cta'];
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
        $sections['hero']['subtitle'] = 'Opstap, bestemming en ophaalmoment. De rit gaat naar de dichtstbijzijnde aangesloten centrale. In de app volgt u daarna de status en, bij een marktplaatsrit, de ophaalcode.';
        $sections['hero']['cta_primary_text'] = '';
        $sections['hero']['cta_primary_url'] = '';
        $sections['hero']['cta_secondary_text'] = '';
        $sections['hero']['cta_secondary_url'] = '';
        $this->applyStreetHero($sections['hero']);

        $faqKey = 'component:landwind.faq';
        $sections[$faqKey] = [
            'eyebrow' => 'Boeken',
            'title' => 'Tips voor een snelle match',
            'subtitle' => 'Zo help je centrales om snel te accepteren.',
            'items' => [
                [
                    'question' => 'Moet ik een account aanmaken?',
                    'answer' => 'Nee. Boeken kan als gast. Naam, telefoon en e-mail horen bij de rit. In de app kunt u die gegevens onder Profiel bewaren, dan staat het boekscherm de volgende keer al ingevuld.',
                ],
                [
                    'question' => 'Waar zie ik de chauffeur?',
                    'answer' => 'Onder Ritten, zodra een chauffeur heeft bevestigd. Dan staan naam, kenteken, autonaam, de centrale en de verwachte aankomst als de locatie bekend is.',
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
        $sections['hero']['subtitle'] = 'Adres, aanbod aan centrales in de buurt, acceptatie, en daarna de status op uw telefoon.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'De app';
        $sections['hero']['cta_secondary_url'] = '/app';
        $this->applyStreetHero($sections['hero']);

        $sections['features']['section_title'] = 'Het proces';
        $sections['features']['items'] = [
            $this->feature('1. Vul de rit in', 'Ophaaladres, bestemming, personen en het ophaalmoment. Op de site via de algemene boeking, in de app op het tabblad Boeken.', 'pencil-square'),
            $this->feature('2. Centrales in de buurt', 'De rit gaat naar aangesloten taxibedrijven rond de opstapplaats. De klant ziet We zoeken een taxi en Wacht op bevestiging.', 'magnifying-glass'),
            $this->feature('3. Een centrale accepteert', 'De chauffeur tikt Accepteren of Weigeren. Er kan maar één rit tegelijk actief zijn. Overlapt een rit, dan blokkeert de app het accepteren.', 'check-circle'),
            $this->feature('4. Ophalen met code', 'Bij een marktplaatsrit noemt de klant de vier cijfers. De chauffeur tikt Klant opgehaald — voer code in en Start rit. Daarna Ophalen en Afzetten in Google Maps.', 'truck'),
        ];

        $sections['text_block'] = $this->shotBlock(
            '<h2>De klant houdt de rit in de hand</h2>'
            .'<p>Op Ritten wisselt de titel tussen We zoeken een taxi en Wacht op bevestiging, met het label Zoeken. De tekst is: Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.</p>'
            .'<p>Blijft de klant wachten, dan wordt dat: Je rit blijft op de marktplaats tot een taxi accepteert. Annuleren kan. Blijft een rit te lang open, dan vraagt de app om te blijven wachten of te annuleren.</p>'
            .'<p>Na de start verdwijnt de ophaalcode. De klant volgt de statusupdates en de verwachte aankomst. Is de rit afgerond, dan kunnen sterren, een opmerking en de factuur.</p>',
            $this->appShot('klant-boeken.png'),
            'right'
        );

        $sections['cta']['title'] = 'Zelf een rit invullen';
        $sections['cta']['subtitle'] = 'De algemene boeking staat op deze site. De app volgt de rit daarna.';
        $sections['cta']['cta_primary_text'] = 'Boek een taxi';
        $sections['cta']['cta_primary_url'] = '/boek';
        $sections['cta']['cta_secondary_text'] = 'Contact';
        $sections['cta']['cta_secondary_url'] = '/contact';

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'features', 'text_block', 'cta'];
        $sections['visibility']['features'] = true;
        $sections['visibility']['cta'] = true;
        $sections['visibility']['text_block'] = true;

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultAppSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);

        $sections['hero']['title'] = 'NEXA Taxi op de telefoon';
        $sections['hero']['title_highlight'] = 'op de telefoon';
        $sections['hero']['subtitle'] = 'Klant, chauffeur, contractant en contractouder. Elk scherm hoort bij een rol. De rit, de code en de kaart zitten in dezelfde app.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Hoe het werkt';
        $sections['hero']['cta_secondary_url'] = '/hoe-het-werkt';
        $this->applyStreetHero($sections['hero']);

        $sections['features']['section_title'] = 'Schermen per rol';
        $sections['features']['items'] = [
            $this->feature('Klant', 'Tabbladen Boeken, Ritten en Profiel. Boeken toont adres, tijd en de kaart. Ritten toont zoeken, geaccepteerd, de ophaalcode en daarna de rit tot de factuur.', 'user-circle'),
            $this->feature('Chauffeur', 'Ritten, Aanvragen, Planning, Inkomsten en Profiel. Aanbod heeft Accepteren en Weigeren. Vrijgeven zet de rit terug in de pool. Nieuw tijdstip voorstellen vraagt goedkeuring. Annuleren vraagt een reden die de klant ziet.', 'truck'),
            $this->feature('Contractant', 'Ritten van vandaag, filters Alles, Openstaand en Verlopen. Opgehaald, Starten, Afronden. Navigatie kleurt elk kind en opent Start navigatie langs de haltes.', 'map-pin'),
            $this->feature('Contractouder', 'Dezelfde tabbladen, zonder Starten, Afronden en Bestemming bereikt. Navigatie volgt taxi en kind. Afmeldingen voor één of meer dagen, intrekken zolang de dag openstaat.', 'shield-check'),
        ];

        $sections['text_block'] = $this->shotBlock(
            '<h2>Maak je keuze</h2>'
            .'<p>Klant: Taxi boeken. Chauffeur, marktplaats, netwerk en contract: Inloggen. Taxibedrijf: Aanmelden op de NEXA Taxi marktplaats, om ritten uit het netwerk te ontvangen.</p>',
            $this->appShot('welcome.png'),
            'left'
        );
        $sections['text_block_2'] = $this->shotBlock(
            '<h2>Boeken, Ritten, Profiel</h2>'
            .'<p>Ophaaladres met suggesties, bestemming, ophalen over tien minuten of een gekozen tijd, en de kaart met taxi’s die online zijn.</p>'
            .'<p>Onder Profiel staan naam, telefoon, e-mail en de weergave: systeem, licht of donker. Wacht de rit op betaling, dan staat er Wacht op betaling en de knop Nu betalen.</p>',
            $this->appShot('klant-boeken.png'),
            'right'
        );
        $sections['text_block_3'] = $this->shotBlock(
            '<h2>Aanvragen</h2>'
            .'<p>Bovenaan staat Online of Offline, het logo en het archief. Daaronder het voertuig. Zonder gekozen auto gaat de chauffeur niet online.</p>'
            .'<p>Een marktplaatsrit draagt het label NEXA-marktplaats. Daar staat Accepteren. Weigeren staat bij een rit die geen marktplaatsrit is, met het label Taxi.</p>',
            $this->campaignImage('scherm-chauffeur-aanvragen.png'),
            'left'
        );
        $sections['text_block_4'] = $this->shotBlock(
            '<h2>Geaccepteerde rit en ophaalcode</h2>'
            .'<p>Onder Ritten filtert de chauffeur op Dag of Week en op Alles, Normaal of Contract. Bij een marktplaatsrit is de startknop Klant opgehaald — voer code in. Daarnaast staat Ophalen, de route naar het ophaaladres. Zonder ophaalcode is de knop Klant opgehaald rit starten.</p>'
            .'<p>Afronden gaat met Rit afronden. Moet er nog betaald worden, dan staat er eerst Betalen. Afgeronde ritten blijven staan tot Naar archief.</p>',
            $this->campaignImage('scherm-chauffeur-rit.png'),
            'right'
        );
        $sections['text_block_5'] = $this->shotBlock(
            '<h2>Contractvervoer</h2>'
            .'<p>De contractant opent Ritten. Filters: Alles, Openstaand, Verlopen. Op de rit staan Contract en Heenweg, de status Nog niet opgehaald, en de knoppen Opgehaald, Starten, Afronden, Afmelden en Intrekken. Bij een open bestemming staat Bestemming bereikt.</p>',
            $this->campaignImage('scherm-contract-ritten.png'),
            'left'
        );
        $sections['text_block_6'] = $this->shotBlock(
            '<h2>Navigatie voor de contractouder</h2>'
            .'<p>Elk kind heeft een eigen kleur op de kaart, met de taxi ertussen. Tik op de naam om de kleur te wijzigen. De knop Start navigatie ontbreekt: die is voor de contractant. Zonder open rit staat er: Geen open rit om te volgen.</p>',
            $this->campaignImage('scherm-contractouder-navigatie.png'),
            'right'
        );
        $sections['text_block_7'] = $this->shotBlock(
            '<h2>Afmeldingen</h2>'
            .'<p>Meld een passagier af voor één of meer dagen. Intrekken kan zolang de dag nog openstaat. Onder Passagiers staat Afmelden. Onder Geplande afmeldingen staat Intrekken.</p>',
            $this->campaignImage('scherm-contractouder-afmeldingen.png'),
            'left'
        );

        $sections['cta']['title'] = 'Begin met een rit, of sluit uw centrale aan';
        $sections['cta']['subtitle'] = 'Reizigers boeken op de site. Taxibedrijven melden zich aan op de marktplaats.';
        $sections['cta']['cta_primary_text'] = 'Boek een taxi';
        $sections['cta']['cta_primary_url'] = '/boek';
        $sections['cta']['cta_secondary_text'] = 'Aansluiten';
        $sections['cta']['cta_secondary_url'] = '/aansluiten';

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'text_block', 'features', 'text_block_2', 'text_block_3', 'text_block_4', 'text_block_5', 'text_block_6', 'text_block_7', 'cta'];
        $sections['visibility']['features'] = true;
        $sections['visibility']['cta'] = true;
        foreach (['text_block', 'text_block_2', 'text_block_3', 'text_block_4', 'text_block_5', 'text_block_6', 'text_block_7'] as $textKey) {
            $sections['visibility'][$textKey] = true;
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultJoinSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        $joinUrl = $this->marketplaceJoinUrl();

        $sections['hero']['title'] = 'Zet uw taxicentrale op NEXA Taxi';
        $sections['hero']['title_highlight'] = 'NEXA Taxi';
        $sections['hero']['subtitle'] = 'Reizigers boeken op nexataxi.nl en in de app. Uw chauffeur accepteert of weigert. De rit start pas als de klant de ophaalcode noemt.';
        $sections['hero']['cta_primary_text'] = 'Bedrijf aanmelden';
        $sections['hero']['cta_primary_url'] = $joinUrl;
        $sections['hero']['cta_secondary_text'] = 'Eerst de app bekijken';
        $sections['hero']['cta_secondary_url'] = '/app';
        $this->applyStreetHero($sections['hero']);

        $sections['features']['section_title'] = 'Wat u in handen krijgt';
        $sections['features']['items'] = [
            $this->feature('Ritten uit de buurt', 'Boekingen van reizigers gaan naar aangesloten bedrijven rond de opstapplaats. Uw chauffeur ziet het aanbod onder Aanvragen.', 'map-pin'),
            $this->feature('U beslist per rit', 'Accepteren of Weigeren. Eén rit tegelijk actief. Vrijgeven zet de rit terug. Een nieuw tijdstip gaat pas door na goedkeuring van de klant.', 'check-circle'),
            $this->feature('Code aan de deur', 'De klant heeft vier cijfers. Uw chauffeur ziet ze niet en tikt ze in om de rit te starten. Een foute code houdt de rit op geaccepteerd.', 'shield-check'),
            $this->feature('Contract in dezelfde app', 'Contractant plant en rondt af. Contractouder volgt het kind op Navigatie en meldt af. Geen tweede systeem voor school of zorg.', 'user-circle'),
        ];

        $sections['text_block'] = $this->shotBlock(
            '<h2>Aanmelden is het marktplaatsformulier</h2>'
            .'<p>Bedrijfsnaam, e-mail, telefoon en plaats. Daarna een code per e-mail. Op dat scherm staat: geen abonnement, alleen een fee over ritten via NEXA Suite.</p>'
            .'<p>In de app heet dezelfde stap Aanmelden op de NEXA Taxi marktplaats. Dat is registratie op het NEXA-netwerk, niet bij marktplaats.nl.</p>'
            .'<p>Wilt u daarnaast een eigen website, dispatch en chauffeur-app in uw merk, dan is dat NEXA Suite op <a href="https://nexasuite.nl/contact">nexasuite.nl/contact</a>. Ritten via uw eigen site vallen onder het maandbedrag. De fee geldt voor ritten via NEXA Suite.</p>',
            $this->appShot('welcome.png'),
            'right'
        );

        $sections['cta']['title'] = 'Meld uw bedrijf aan';
        $sections['cta']['subtitle'] = 'Het formulier vraagt bedrijfsnaam, e-mail, telefoon en plaats. U ontvangt een code per e-mail.';
        $sections['cta']['cta_primary_text'] = 'Start met NEXA Taxi';
        $sections['cta']['cta_primary_url'] = $joinUrl;
        $sections['cta']['cta_secondary_text'] = 'Vraag stellen';
        $sections['cta']['cta_secondary_url'] = '/contact';

        $sections['footer'] = $this->taxiFooter($sections['footer'] ?? []);
        $sections['footer']['inherit_from_home'] = true;
        $sections['copyright'] = '© {year} Nexa Taxi. Alle rechten voorbehouden.';
        $sections['section_order'] = ['hero', 'features', 'text_block', 'cta'];
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

        $sections['hero']['title'] = 'Contact NEXA Taxi';
        $sections['hero']['title_highlight'] = 'Contact';
        $sections['hero']['subtitle'] = 'Een boeking, de ophaalcode, een contractrit of aansluiten als centrale. Stuur het formulier hieronder.';
        $sections['hero']['cta_primary_text'] = 'Boek een taxi';
        $sections['hero']['cta_primary_url'] = '/boek';
        $sections['hero']['cta_secondary_text'] = 'Aansluiten';
        $sections['hero']['cta_secondary_url'] = '/aansluiten';
        $this->applyStreetHero($sections['hero']);

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
        $footer['tagline'] = 'NEXA Taxi: taxi boeken voor reizigers, ritten en contractvervoer voor aangesloten centrales.';
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

    private function campaignImage(string $filename): string
    {
        return '/images/nexa-taxi/campaign/'.$filename;
    }

    private function appShot(string $filename): string
    {
        return '/images/handleiding/app/'.$filename;
    }

    private function marketplaceJoinUrl(): string
    {
        return 'https://nexasuite.online/admin/login?marketplace=1';
    }

    /**
     * @param  array<string, mixed>  $hero
     */
    private function applyStreetHero(array &$hero): void
    {
        $hero['background_image_url'] = $this->campaignImage('hero-straat.jpg');
        $hero['overlay'] = true;
        $hero['overlay_opacity'] = 46;
        $hero['overlay_color_from'] = '#0b1220';
        $hero['overlay_color_to'] = '#0f2744';
        $hero['text_bg_color'] = '#0b1220';
        $hero['text_bg_opacity'] = 58;
        $hero['text_bg_width_percent'] = 78;
        $hero['subtitle_width_percent'] = 72;
    }

    /**
     * @return array{title: string, description: string, icon: string, icon_size: string, icon_align: string}
     */
    private function feature(string $title, string $description, string $icon): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'icon_size' => 'medium',
            'icon_align' => 'left',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shotBlock(string $content, string $imageUrl, string $alignment): array
    {
        return [
            'content' => $content,
            'alignment' => $alignment,
            'side_component_key' => '',
            'side_template_id' => null,
            'image_url' => $imageUrl,
            'width_percent' => 100,
        ];
    }
}
