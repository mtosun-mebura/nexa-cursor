<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use App\Services\CentralWelcomePageService;
use App\Services\WebsiteBuilderService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsiteFooterInheritFromHomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(CentralWelcomePageService::class)->ensureMarketingPagesExist();
        app(CentralWelcomePageService::class)->syncDefaultContent();
    }

    #[Test]
    public function inheriting_central_home_footer_hides_map_when_home_has_none(): void
    {
        $taxi = WebsitePage::query()->where('slug', CentralWelcomePageService::TAXI_SLUG)->first();
        $this->assertNotNull($taxi);

        $sections = $taxi->home_sections;
        $this->assertIsArray($sections);
        $sections['footer']['inherit_from_home'] = true;
        $sections['visibility']['footer_map'] = true;
        $taxi->home_sections = $sections;
        $taxi->save();

        $applied = app(WebsiteBuilderService::class)->applyInheritedHomeFooter(
            $taxi->fresh()->getHomeSections(),
            $taxi->fresh()
        );
        $this->assertFalse((bool) ($applied['visibility']['footer_map'] ?? true));

        $this->get('http://localhost:8085/')
            ->assertOk()
            ->assertDontSee('id="footer-google-map"', false)
            ->assertDontSee('footer-grid-with-map', false)
            ->assertDontSee('Stel de Google Maps API-sleutel in', false);

        $this->get('http://localhost:8085/taxi')
            ->assertOk()
            ->assertDontSee('id="footer-google-map"', false)
            ->assertDontSee('footer-grid-with-map', false)
            ->assertDontSee('Stel de Google Maps API-sleutel in', false);

        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user)
            ->get(route('admin.website-pages.preview', $taxi->fresh()))
            ->assertOk()
            ->assertDontSee('id="footer-google-map"', false)
            ->assertDontSee('footer-grid-with-map', false)
            ->assertDontSee('Stel de Google Maps API-sleutel in', false);

        $this->assertTrue(
            (bool) ($applied['footer']['inherit_from_home'] ?? false),
            'Na overname van Home moet inherit_from_home aan blijven (niet de false van de Home-footer overnemen)'
        );
    }

    #[Test]
    public function maps_placeholder_points_to_admin_panel(): void
    {
        $html = view('frontend.layouts.partials.website-footer', [
            'homeSections' => [
                'footer' => ['tagline' => 'Nexa'],
                'visibility' => ['footer' => true, 'footer_map' => true],
            ],
            'branding' => [],
            'googleMapsApiKey' => '',
        ])->render();

        $this->assertStringContainsString(
            'Stel de Google Maps API-sleutel in via het Admin paneel om de kaart te tonen.',
            $html
        );
    }

    #[Test]
    public function cms_footer_hides_vacatures_and_legacy_skillmatching_tagline_when_not_skillmatching(): void
    {
        $html = view('frontend.layouts.partials.website-footer', [
            'homeSections' => WebsitePage::prepareFooterForPublicDisplay([
                'footer' => [
                    'tagline' => WebsitePage::LEGACY_SKILLMATCHING_FOOTER_TAGLINE,
                    'quick_links' => [
                        ['label' => 'Home', 'url' => '/'],
                        ['label' => 'Vacatures', 'url' => '/jobs'],
                        ['label' => 'Over Ons', 'url' => '/over-ons'],
                    ],
                ],
                'visibility' => [
                    'footer' => true,
                    'footer_map' => false,
                    'footer_quick_links' => true,
                    'footer_tagline' => true,
                ],
            ], false),
            'branding' => ['site_name' => 'Test'],
            'googleMapsApiKey' => '',
            'websiteBuilder' => app(WebsiteBuilderService::class),
        ])->render();

        $this->assertStringNotContainsString('Vacatures', $html);
        $this->assertStringNotContainsString('perfecte match', $html);
        $this->assertStringContainsString('Ontdek wat wij voor u kunnen betekenen', $html);
        $this->assertStringContainsString('Over Ons', $html);
    }

    #[Test]
    public function central_home_shows_header_menu_with_product_pages(): void
    {
        $this->get('http://localhost:8085/')
            ->assertOk()
            ->assertSee('id="website-desktop-nav"', false)
            ->assertSee('aria-label="Hoofdnavigatie"', false)
            ->assertSee('>Home</a>', false)
            ->assertSee('>Nexa Taxi</a>', false)
            ->assertSee('>Contractvervoer</a>', false)
            ->assertSee('>Website</a>', false)
            ->assertSee('>Prijzen</a>', false)
            ->assertSee('>Contact</a>', false)
            ->assertDontSee(WebsitePage::CENTRAL_WELCOME_SLUG, false)
            ->assertDontSee('id="prijzen-pakketten"', false);
    }

    #[Test]
    public function central_home_does_not_restore_removed_pricing_packages(): void
    {
        $page = WebsitePage::query()->where('slug', WebsitePage::CENTRAL_WELCOME_SLUG)->first();
        $this->assertNotNull($page);
        $key = \App\Services\NexaPricingService::PACKAGES_SECTION_KEY;
        $sections = $page->getHomeSections();
        $sections['section_order'] = array_values(array_filter(
            is_array($sections['section_order'] ?? null) ? $sections['section_order'] : [],
            fn ($item) => $item !== $key
        ));
        unset($sections[$key]);
        $sections['removed_section_keys'] = $key;
        $page->home_sections = $sections;
        $page->save();

        $this->get('http://localhost:8085/')
            ->assertOk()
            ->assertDontSee('id="prijzen-pakketten"', false);

        $page->refresh();
        $this->assertNotContains($key, $page->getHomeSections()['section_order'] ?? []);
    }

    #[Test]
    public function tenant_footer_map_uses_company_contact_address_instead_of_cms_city(): void
    {
        $theme = FrontendTheme::query()->where('slug', 'modern')->first();
        $this->assertNotNull($theme);

        $company = Company::query()->create([
            'name' => 'Map Taxi Enschede',
            'street' => 'Deurningerstraat',
            'house_number' => '240',
            'postal_code' => '7522 CA',
            'city' => 'Enschede',
            'country' => 'Nederland',
            'is_active' => true,
        ]);

        $page = WebsitePage::query()->create([
            'slug' => 'home-map-'.uniqid(),
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $html = view('frontend.layouts.partials.website-footer', [
            'page' => $page,
            'homeSections' => [
                'footer' => [
                    'map_city' => 'Amsterdam',
                    'map_street' => '',
                    'map_huisnummer' => '',
                    'map_postcode' => '',
                    'map_city_only' => false,
                    'map_lat' => '52.3676',
                    'map_lng' => '4.9041',
                ],
                'visibility' => ['footer' => true, 'footer_map' => true],
            ],
            'branding' => ['site_name' => $company->name],
            'googleMapsApiKey' => 'test-maps-key',
            'websiteBuilder' => app(WebsiteBuilderService::class),
        ])->render();

        $this->assertStringContainsString('data-address="Deurningerstraat 240, 7522CA Enschede, Nederland"', $html);
        $this->assertStringContainsString('data-show-address-balloon="1"', $html);
        $this->assertStringNotContainsString('data-lat="52.3676"', $html);
        $this->assertStringNotContainsString('data-address="Amsterdam"', $html);
    }

    #[Test]
    public function tenant_footer_hides_support_links_when_those_pages_do_not_exist(): void
    {
        $theme = FrontendTheme::query()->where('slug', 'modern')->first();
        $this->assertNotNull($theme);

        $company = Company::query()->create([
            'name' => 'Footer Taxi',
            'is_active' => true,
            'city' => 'Enschede',
        ]);
        $home = WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'show_in_menu' => true,
        ]);
        WebsitePage::query()->create([
            'slug' => 'contact',
            'title' => 'Contact',
            'page_type' => 'contact',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 2,
            'show_in_menu' => true,
        ]);

        $html = view('frontend.layouts.partials.website-footer', [
            'page' => $home,
            'homeSections' => [
                'footer' => [
                    'quick_links' => [
                        ['label' => 'Home', 'url' => '/'],
                        ['label' => 'Contact', 'url' => '/contact'],
                        ['label' => 'Over Ons', 'url' => '/over-ons'],
                    ],
                    'support_links' => [
                        ['label' => 'Help & FAQ', 'url' => '/help'],
                        ['label' => 'Privacy', 'url' => '/privacy'],
                        ['label' => 'Voorwaarden', 'url' => '/voorwaarden'],
                        ['label' => 'Cookies', 'url' => '/privacy#cookies'],
                    ],
                    'support_links_title' => 'Ondersteuning',
                ],
                'visibility' => [
                    'footer' => true,
                    'footer_map' => false,
                    'footer_quick_links' => true,
                    'footer_support_links' => true,
                ],
            ],
            'branding' => ['site_name' => $company->name],
            'googleMapsApiKey' => '',
            'websiteBuilder' => app(WebsiteBuilderService::class),
        ])->render();

        $this->assertStringNotContainsString('Help & FAQ', $html);
        $this->assertStringNotContainsString('Voorwaarden', $html);
        $this->assertStringNotContainsString('Cookies', $html);
        $this->assertStringNotContainsString('Ondersteuning', $html);
        $this->assertStringNotContainsString('Over Ons', $html);
        $this->assertStringContainsString('Contact', $html);

        WebsitePage::query()->create([
            'slug' => 'privacy',
            'title' => 'Privacy',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 8,
            'show_in_menu' => false,
        ]);

        $htmlWithPrivacy = view('frontend.layouts.partials.website-footer', [
            'page' => $home->fresh(),
            'homeSections' => [
                'footer' => [
                    'support_links' => [
                        ['label' => 'Help & FAQ', 'url' => '/help'],
                        ['label' => 'Privacy', 'url' => '/privacy'],
                        ['label' => 'Cookies', 'url' => '/privacy#cookies'],
                    ],
                    'support_links_title' => 'Ondersteuning',
                ],
                'visibility' => [
                    'footer' => true,
                    'footer_map' => false,
                    'footer_support_links' => true,
                ],
            ],
            'branding' => ['site_name' => $company->name],
            'googleMapsApiKey' => '',
            'websiteBuilder' => app(WebsiteBuilderService::class),
        ])->render();

        $this->assertStringContainsString('Ondersteuning', $htmlWithPrivacy);
        $this->assertStringContainsString('Privacy', $htmlWithPrivacy);
        $this->assertStringContainsString('Cookies', $htmlWithPrivacy);
        $this->assertStringNotContainsString('Help & FAQ', $htmlWithPrivacy);
    }

    #[Test]
    public function tenant_footer_map_uses_stored_company_coordinates(): void
    {
        $company = Company::query()->create([
            'name' => 'Coord Taxi',
            'street' => 'Kalverstraat',
            'house_number' => '1',
            'postal_code' => '1012 NX',
            'city' => 'Amsterdam',
            'country' => 'Nederland',
            'latitude' => 52.3702,
            'longitude' => 4.8952,
            'is_active' => true,
        ]);

        $location = app(WebsiteBuilderService::class)->footerMapLocationForPage(null);
        $this->assertNull($location);

        app()->instance('resolved_tenant_id', $company->id);
        $location = app(WebsiteBuilderService::class)->footerMapLocationForPage(null);
        $this->assertNotNull($location);
        $this->assertSame('Kalverstraat', $location['street']);
        $this->assertSame(52.3702, $location['lat']);
        $this->assertSame(4.8952, $location['lng']);
    }
}
