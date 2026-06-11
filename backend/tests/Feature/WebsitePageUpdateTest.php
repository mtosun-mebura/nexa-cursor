<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsitePageUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $this->markTestSkipped('roles table (permission migrations) not available');
        }
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    /** @return array{company: ?Company, company_id: ?int} */
    private function websitePageCompanyForTests(): array
    {
        if (! Schema::hasColumn('website_pages', 'company_id')) {
            return ['company' => null, 'company_id' => null];
        }
        $company = Company::query()->first();
        if (! $company) {
            $company = Company::create([
                'name' => 'Testbedrijf Website',
                'is_active' => true,
            ]);
        }

        return ['company' => $company, 'company_id' => (int) $company->id];
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_cards_ronde_hoeken_home_sections()
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'cards_ronde_hoeken', 'cta'],
                'visibility' => ['hero' => true, 'cards_ronde_hoeken' => true],
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => '',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            'home_sections' => [
                'section_order' => 'hero,stats,cards_ronde_hoeken,cta',
                'visibility' => [
                    'hero' => '1',
                    'cards_ronde_hoeken' => '1',
                    'cards_ronde_hoeken_item_0' => '1',
                ],
                'copyright' => '',
                'cards_ronde_hoeken' => [
                    'items' => [
                        0 => [
                            'image_url' => '/storage/test.jpg',
                            'text' => '<p>Test card text</p>',
                        ],
                    ],
                ],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $this->assertArrayHasKey('cards_ronde_hoeken', $sections);
        $this->assertSame('/storage/test.jpg', $sections['cards_ronde_hoeken']['items'][0]['image_url'] ?? null);
        $this->assertStringContainsString('Test card text', $sections['cards_ronde_hoeken']['items'][0]['text'] ?? '');
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_component_in_section_order()
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'atom-v2'],
            ['name' => 'Atom v2', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => 'Nexa Taxi',
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'stats', 'cta'],
                'visibility' => ['hero' => true],
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'Nexa Taxi',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,stats,component:taxi.boekingsmodule,cta',
            'home_sections' => [
                'section_order' => 'hero,stats,component:taxi.boekingsmodule,cta',
                'visibility' => ['hero' => true, 'footer' => true],
                'copyright' => '© Test',
                'footer' => ['tagline' => 'Test tagline'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $order = $sections['section_order'] ?? [];
        $this->assertContains('component:taxi.boekingsmodule', $order, 'Section order must contain the component key');
        $this->assertArrayHasKey('component:taxi.boekingsmodule', $sections);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_component_section_visibility(): void
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'atom-v2'],
            ['name' => 'Atom v2', 'is_active' => true]
        );
        $componentKey = 'component:taxi.boekingsmodule';
        $page = WebsitePage::create(array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => 'Nexa Taxi',
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', $componentKey, 'cta'],
                'visibility' => ['hero' => true, 'footer' => true, $componentKey => true],
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'Nexa Taxi',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,'.$componentKey.',cta',
            'home_sections' => [
                'section_order' => 'hero,'.$componentKey.',cta',
                'visibility' => [
                    'hero' => '1',
                    'footer' => '1',
                    $componentKey => '0',
                ],
                'copyright' => '© Test',
                'footer' => ['tagline' => 'Test tagline'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $vis = $page->getHomeSections()['visibility'] ?? [];
        $this->assertArrayHasKey($componentKey, $vis);
        $this->assertFalse($vis[$componentKey], 'Hidden component visibility must persist as false');
    }

    #[Test]
    #[Group('website-pages')]
    public function update_removes_component_from_section_order_when_not_in_section_order()
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'stats', 'component:nexa.recente_vacatures', 'cta'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => [],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        // Simuleer: gebruiker heeft "Recente Vacatures" verwijderd; _section_order bevat alleen hero,stats,cta
        $payload = array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => '',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,stats,cta',
            '_removed_section_keys' => 'component:nexa.recente_vacatures',
            'home_sections' => [
                'section_order' => 'hero,stats,cta',
                'removed_section_keys' => 'component:nexa.recente_vacatures',
                'visibility' => ['hero' => true, 'footer' => true],
                'copyright' => '',
                'footer' => [],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $sections = $page->home_sections;
        $this->assertIsArray($sections);
        $order = $sections['section_order'] ?? [];
        $this->assertNotContains('component:nexa.recente_vacatures', $order, 'Removed component must not be in section_order after save');
        $this->assertSame(['hero', 'stats', 'cta'], $order);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_google_reviews_place_id_for_company(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }

        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        if ($companyId === null) {
            $this->markTestSkipped('company_id column required');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $componentKey = 'component:website.google_reviews';
        $placeId = 'ChIJ_test_persist_from_website_page_01';

        $page = WebsitePage::create(array_filter([
            'slug' => 'home-grw-persist',
            'title' => 'Home GRW persist',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', $componentKey, 'cta'],
                'visibility' => ['hero' => true, $componentKey => true],
                'footer' => [],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        GeneralSetting::set('google_reviews_place_id', '', $companyId);
        GeneralSetting::set('google_reviews_business_name', '', $companyId);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home-grw-persist',
            'title' => 'Home GRW persist',
            'page_type' => 'home',
            'module_name' => '',
            'company_id' => (string) $companyId,
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,'.$componentKey.',cta',
            '_google_reviews_place_id' => $placeId,
            '_google_reviews_business_name' => 'Test Taxi BV',
            'home_sections' => [
                'section_order' => 'hero,'.$componentKey.',cta',
                $componentKey => [
                    'place_id' => $placeId,
                    'business_name' => 'Test Taxi BV',
                    'section_title' => 'Klantbeoordelingen',
                    'section_background' => '#e0e7ff',
                    'count' => '3',
                    'cache_hours' => '12',
                    'min_stars' => '2',
                ],
                'visibility' => ['hero' => true, $componentKey => true],
                'copyright' => '',
                'footer' => [],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame($placeId, GeneralSetting::get('google_reviews_place_id', '', $companyId));
        $this->assertSame('Test Taxi BV', GeneralSetting::get('google_reviews_business_name', '', $companyId));
        $this->assertSame('3', GeneralSetting::get('google_reviews_count', '', $companyId));
        $this->assertSame('12', GeneralSetting::get('google_reviews_cache_hours', '', $companyId));
        $this->assertSame('2', GeneralSetting::get('google_reviews_min_stars', '', $companyId));
        $this->assertSame('Klantbeoordelingen', GeneralSetting::get('google_reviews_section_title', '', $companyId));
        $this->assertSame('#e0e7ff', GeneralSetting::get('google_reviews_section_background', '', $companyId));
    }

    #[Test]
    #[Group('website-pages')]
    public function update_normalizes_legacy_google_reviews_component_key(): void
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'home-grw',
            'title' => 'Home GRW',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'component:nexa.google_reviews', 'cta'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => [],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home-grw',
            'title' => 'Home GRW',
            'page_type' => 'home',
            'module_name' => '',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,component:nexa.google_reviews,cta',
            'home_sections' => [
                'section_order' => 'hero,component:nexa.google_reviews,cta',
                'visibility' => ['hero' => true, 'footer' => true],
                'copyright' => '',
                'footer' => [],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $order = $page->home_sections['section_order'] ?? [];
        $this->assertContains('component:website.google_reviews', $order);
        $this->assertNotContains('component:nexa.google_reviews', $order);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_preserves_component_missing_from_request_but_not_marked_removed(): void
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'atom-v2'],
            ['name' => 'Atom v2', 'is_active' => true]
        );
        $componentKey = 'component:taxi.boekingsmodule';
        $page = WebsitePage::create(array_filter([
            'slug' => 'home-preserve-comp',
            'title' => 'Home preserve',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => 'Nexa Taxi',
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', $componentKey, 'cta'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => [],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home-preserve-comp',
            'title' => 'Home preserve',
            'page_type' => 'home',
            'module_name' => 'Nexa Taxi',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'frontend_theme_id' => (string) $theme->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'sort_order' => '0',
            '_section_order' => 'hero,cta',
            'home_sections' => [
                'section_order' => 'hero,cta',
                'removed_section_keys' => '',
                'visibility' => ['hero' => true, 'footer' => true],
                'copyright' => '',
                'footer' => [],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $order = $page->home_sections['section_order'] ?? [];
        $this->assertContains($componentKey, $order);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_unchecks_show_in_menu_persists_false(): void
    {
        if (! Schema::hasColumn('website_pages', 'show_in_menu')) {
            $this->markTestSkipped('show_in_menu column not migrated');
        }

        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'menu-toggle-test',
            'title' => 'Menu toggle',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => ['inherit_from_home' => false],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        // Alleen hidden input (uitgevinkt): show_in_menu=0 — geen tweede veld met dezelfde name
        $payload = array_filter([
            'slug' => 'menu-toggle-test',
            'title' => 'Menu toggle',
            'page_type' => 'custom',
            'module_name' => '',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '0',
            'sort_order' => '0',
            '_section_order' => 'hero,footer,copyright',
            'home_sections' => [
                'section_order' => 'hero,footer,copyright',
                'visibility' => ['hero' => '1', 'footer' => '1'],
                'copyright' => '',
                'footer' => ['inherit_from_home' => '0'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $this->assertFalse(
            (bool) $page->show_in_menu,
            'show_in_menu moet false zijn na opslaan met alleen waarde 0'
        );
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_sort_order(): void
    {
        if (! Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('sort_order column not migrated');
        }

        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'sort-order-test',
            'title' => 'Sort order',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => ['inherit_from_home' => false],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'sort-order-test',
            'title' => 'Sort order',
            'page_type' => 'custom',
            'module_name' => '',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '1',
            'sort_order' => '42',
            '_section_order' => 'hero,footer,copyright',
            'home_sections' => [
                'section_order' => 'hero,footer,copyright',
                'visibility' => ['hero' => '1', 'footer' => '1'],
                'copyright' => '',
                'footer' => ['inherit_from_home' => '0'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $this->assertSame(42, (int) $page->sort_order);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_preserves_footer_tagline_and_map_when_footer_fields_missing_from_post(): void
    {
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $customTagline = '<p>Taxi footer tekst op maat</p>';
        $page = WebsitePage::create(array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => 'taxi',
            'company_id' => $companyId,
            'is_active' => true,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => [
                    'tagline' => $customTagline,
                    'map_street' => 'Stationsweg',
                    'map_huisnummer' => '1',
                    'map_postcode' => '1234AB',
                    'map_city' => 'Amsterdam',
                    'quick_links' => [['label' => 'Home', 'url' => '/']],
                ],
                'copyright' => '© Taxi Test',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'is_active' => '1',
            'show_in_menu' => '1',
            '_section_order' => 'hero,footer,copyright',
            'home_sections' => [
                'section_order' => 'hero,footer,copyright',
                'visibility' => ['hero' => '1', 'footer' => '1'],
                'hero' => ['title' => 'Nieuwe hero titel'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $footer = $page->home_sections['footer'] ?? [];
        $this->assertSame($customTagline, $footer['tagline'] ?? null);
        $this->assertSame('Stationsweg', $footer['map_street'] ?? null);
        $this->assertSame('Amsterdam', $footer['map_city'] ?? null);
        $this->assertNotSame(
            WebsitePage::defaultHomeSections()['footer']['tagline'] ?? '',
            $footer['tagline'] ?? '',
            'Footer mag niet terugvallen naar Skillmatching-default tagline'
        );
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_sort_order_from_early_fallback_when_primary_key_missing(): void
    {
        if (! Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('sort_order column not migrated');
        }

        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create(array_filter([
            'slug' => 'sort-fallback-test',
            'title' => 'Sort fallback',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 1,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
                'footer' => ['inherit_from_home' => false],
                'copyright' => '',
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'sort-fallback-test',
            'title' => 'Sort fallback',
            'page_type' => 'custom',
            'module_name' => '',
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '1',
            '_sort_order' => '77',
            '_section_order' => 'hero,footer,copyright',
            'home_sections' => [
                'section_order' => 'hero,footer,copyright',
                'visibility' => ['hero' => '1', 'footer' => '1'],
                'copyright' => '',
                'footer' => ['inherit_from_home' => '0'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $page->refresh();
        $this->assertSame(77, (int) $page->sort_order);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_selected_frontend_theme_when_company_has_no_theme(): void
    {
        $metronic = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $vpn = FrontendTheme::firstOrCreate(
            ['slug' => 'next-landing-vpn'],
            ['name' => 'Next Landing VPN', 'is_active' => true]
        );
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $page = WebsitePage::create(array_filter([
            'slug' => 'thema-test',
            'title' => 'Thema test',
            'page_type' => 'custom',
            'frontend_theme_id' => $metronic->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = array_filter([
            'slug' => 'thema-test',
            'title' => 'Thema test',
            'page_type' => 'custom',
            'module_name' => '',
            'frontend_theme_id' => (string) $vpn->id,
            'company_id' => $companyId !== null ? (string) $companyId : null,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '1',
            '_section_order' => 'hero,footer,copyright',
            'home_sections' => [
                'section_order' => 'hero,footer,copyright',
                'visibility' => ['hero' => '1', 'footer' => '1'],
            ],
        ], fn ($v) => $v !== null);

        $response = $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $response->assertRedirect();
        $page->refresh();
        $this->assertSame($vpn->id, (int) $page->frontend_theme_id);
    }

    #[Test]
    #[Group('website-pages')]
    public function update_persists_selected_frontend_theme_over_company_default(): void
    {
        $metronic = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $vpn = FrontendTheme::firstOrCreate(
            ['slug' => 'next-landing-vpn'],
            ['name' => 'Next Landing VPN', 'is_active' => true]
        );
        $company = Company::create([
            'name' => 'Thema tenant BV',
            'is_active' => true,
            'frontend_theme_id' => $metronic->id,
        ]);
        $page = WebsitePage::create([
            'slug' => 'thema-company-test',
            'title' => 'Thema company test',
            'page_type' => 'custom',
            'frontend_theme_id' => $metronic->id,
            'module_name' => null,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $payload = [
            'slug' => 'thema-company-test',
            'title' => 'Thema company test',
            'page_type' => 'custom',
            'module_name' => '',
            'frontend_theme_id' => (string) $vpn->id,
            'company_id' => (string) $company->id,
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '1',
            '_section_order' => 'hero,footer,copyright',
            'home_sections' => [
                'section_order' => 'hero,footer,copyright',
                'visibility' => ['hero' => '1', 'footer' => '1'],
            ],
        ];

        $this->actingAs($user)->put(route('admin.website-pages.update', $page), $payload);

        $page->refresh();
        $this->assertSame($vpn->id, (int) $page->frontend_theme_id);
        $this->assertSame($vpn->id, (int) app(\App\Services\WebsiteBuilderService::class)->getThemeForPage($page)?->id);
    }

    #[Test]
    #[Group('website-pages')]
    public function edit_form_renders_saved_frontend_theme_as_selected(): void
    {
        $metronic = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $vpn = FrontendTheme::firstOrCreate(
            ['slug' => 'next-landing-vpn'],
            ['name' => 'Next Landing VPN', 'is_active' => true]
        );
        ['company_id' => $companyId] = $this->websitePageCompanyForTests();
        $page = WebsitePage::create(array_filter([
            'slug' => 'thema-render-test',
            'title' => 'Thema render',
            'page_type' => 'custom',
            'frontend_theme_id' => $vpn->id,
            'module_name' => null,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'footer', 'copyright'],
                'visibility' => ['hero' => true, 'footer' => true],
            ],
        ], fn ($v) => $v !== null));

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.website-pages.edit', $page));

        $response->assertOk();
        $response->assertSee('name="frontend_theme_id"', false);
        $html = $response->getContent();
        preg_match('/<select[^>]*name="frontend_theme_id"[^>]*>(.*?)<\/select>/s', $html, $themeSelectMatch);
        $themeOptionsHtml = $themeSelectMatch[1] ?? '';
        $this->assertNotSame('', $themeOptionsHtml, 'Theme select should be rendered.');
        $this->assertMatchesRegularExpression(
            '/value="' . preg_quote((string) $vpn->id, '/') . '"[^>]*\sselected/',
            $themeOptionsHtml,
            'Saved VPN theme should be selected in the edit form.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/value="' . preg_quote((string) $metronic->id, '/') . '"[^>]*\sselected/',
            $themeOptionsHtml,
            'Metronic should not be selected when page uses VPN theme.'
        );
    }
}
