<?php

namespace Tests\Feature;

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
}
