<?php

namespace Tests\Feature;

use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use App\Services\NexaPricingService;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNexaPricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function guest_is_redirected_from_admin_prijzen(): void
    {
        $response = $this->get(route('admin.nexa-pricing.edit'));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
        $target = $response->headers->get('Location', '');
        $this->assertTrue(
            str_contains($target, 'login') || str_contains($target, 'meld/sessie-verlopen'),
            "Guest should be redirected to login or sessie-verlopen, got: {$target}"
        );
    }

    #[Test]
    public function company_admin_cannot_open_admin_prijzen(): void
    {
        $user = User::factory()->create();
        $user->assignRole('company-admin');

        $response = $this->actingAs($user)
            ->get(route('admin.nexa-pricing.edit'));

        $this->assertTrue(
            in_array($response->status(), [403, 302, 303], true),
            'Company-admin mag de prijzenpagina niet zien, got: '.$response->status()
        );
        if (in_array($response->status(), [302, 303], true)) {
            $this->assertNotSame(route('admin.nexa-pricing.edit'), $response->headers->get('Location'));
        }
    }

    #[Test]
    public function company_admin_does_not_see_paketten_menu(): void
    {
        $user = User::factory()->create();
        $user->assignRole('company-admin');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Paketten', false);
    }

    #[Test]
    public function super_admin_can_open_admin_prijzen(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.nexa-pricing.edit'))
            ->assertOk()
            ->assertSee('Paketten', false)
            ->assertSee('Prijzen', false)
            ->assertSee('Maandprijs', false)
            ->assertSee('Aanbiedingsprijs', false)
            ->assertSee('Gratis maanden', false)
            ->assertDontSee('getal, zonder €', false)
            ->assertSee('>€</span>', false)
            ->assertSee('Website (eenmalig)', false)
            ->assertSee('name="packages[0][price]"', false)
            ->assertSee('name="packages[0][offer]"', false)
            ->assertSee('name="packages[0][free_months]"', false)
            ->assertSee('name="packages[0][features][]"', false)
            ->assertSee('Kenmerk toevoegen', false)
            ->assertSee('Functies (voor de software)', false)
            ->assertSee('Maximum chauffeurs', false)
            ->assertSee('TenantPackageCapability::MAX_DRIVERS', false)
            ->assertSee('name="packages[0][key]"', false)
            ->assertSee('name="packages[0][entitlements][max_drivers]"', false)
            ->assertSee('Maximum contractklanten', false)
            ->assertSee('name="packages[0][entitlements][max_contract_clients]"', false)
            ->assertSee('Aanvullende modules', false)
            ->assertSee('GPS-trackers', false)
            ->assertSee('Extra contractklanten', false);
    }

    #[Test]
    public function admin_prijzen_shows_shared_feature_catalog_on_every_package(): void
    {
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin)
            ->get(route('admin.nexa-pricing.edit'))
            ->assertOk()
            ->assertSee('Onbeperkt chauffeurs', false)
            ->assertSee('Contractvervoer: school, zorg, zakelijk, privé', false)
            ->getContent();

        $package0 = substr_count($html, 'name="packages[0][features][]"');
        $package1 = substr_count($html, 'name="packages[1][features][]"');
        $package2 = substr_count($html, 'name="packages[2][features][]"');
        $this->assertGreaterThan(5, $package0);
        $this->assertSame($package0, $package1);
        $this->assertSame($package0, $package2);
    }

    #[Test]
    public function builder_v2_bootstrap_includes_live_nexa_pricing_for_readonly_preview(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $attrs = [
            'slug' => 'prijzen-builder-preview',
            'title' => 'Prijzen',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => [\App\Services\NexaPricingService::PACKAGES_SECTION_KEY],
            ],
        ];
        if (Schema::hasColumn('website_pages', 'company_id')) {
            $attrs['company_id'] = null;
        }
        $page = WebsitePage::create($attrs);
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.website-pages.builder-v2.edit', $page))
            ->assertOk()
            ->assertSee('nexaPricing', false)
            ->assertSee('nexaPricingEdit', false)
            ->assertSee('Website met boekingsmodule', false)
            ->assertSee(route('admin.nexa-pricing.edit'), false);
    }

    #[Test]
    public function super_admin_can_save_prices_and_public_page_shows_them(): void
    {
        $admin = $this->superAdmin();
        $payload = $this->formPayload();
        $payload['packages'][0]['price'] = '59';
        $payload['packages'][0]['offer'] = '39';
        $payload['website']['price_label'] = '800';
        $payload['website']['offer'] = '499';

        $this->actingAs($admin)
            ->put(route('admin.nexa-pricing.update'), $payload)
            ->assertRedirect(route('admin.nexa-pricing.edit'))
            ->assertSessionHas('success');

        $saved = app(NexaPricingService::class)->get();
        $this->assertSame('59', $saved['packages'][0]['price'] ?? null);
        $this->assertSame('39', $saved['packages'][0]['offer'] ?? null);
        $this->assertSame(0, $saved['packages'][0]['free_months'] ?? null);
        $this->assertSame('800', $saved['website']['price_label'] ?? null);
        $this->assertSame('499', $saved['website']['offer'] ?? null);
        $this->assertContains('Website met boekingsmodule', $saved['packages'][0]['features'] ?? []);
        $this->assertNotContains('Onbeperkt chauffeurs', $saved['packages'][0]['features'] ?? []);
        $this->assertContains('Onbeperkt chauffeurs', $saved['packages'][1]['features'] ?? []);
        $this->assertSame('start', $saved['packages'][0]['key'] ?? null);
        $this->assertSame(3, $saved['packages'][0]['entitlements']['max_drivers'] ?? null);
        $this->assertFalse((bool) ($saved['packages'][0]['entitlements']['mollie_payments'] ?? true));
        $this->assertTrue((bool) ($saved['packages'][0]['entitlements']['invoice_pdf'] ?? false));
        $this->assertSame(0, $saved['packages'][1]['entitlements']['max_drivers'] ?? null);
        $this->assertTrue((bool) ($saved['packages'][1]['entitlements']['mollie_payments'] ?? false));

        $this->get('http://localhost:8085/prijzen')
            ->assertOk()
            ->assertSee('€ 59,-', false)
            ->assertSee('€ 39,-', false)
            ->assertSee('nexa-price-original--struck', false)
            ->assertSee('€ 800,-', false)
            ->assertSee('€ 499,-', false)
            ->assertSee('Start', false)
            ->assertSee('Website live zetten', false);
    }

    #[Test]
    public function public_page_shows_offer_text_without_euro_prefix(): void
    {
        $admin = $this->superAdmin();
        $payload = $this->formPayload();
        $payload['packages'][1]['offer'] = 'eerste maand gratis';

        $this->actingAs($admin)
            ->put(route('admin.nexa-pricing.update'), $payload)
            ->assertRedirect(route('admin.nexa-pricing.edit'));

        $this->get('http://localhost:8085/prijzen')
            ->assertOk()
            ->assertSee('nexa-price-original--struck', false)
            ->assertSee('eerste maand gratis', false)
            ->assertDontSee('€ eerste maand gratis', false);
    }

    #[Test]
    public function public_page_shows_free_months_then_regular_price(): void
    {
        $admin = $this->superAdmin();
        $payload = $this->formPayload();
        $payload['packages'][0]['free_months'] = 1;
        $payload['packages'][0]['offer'] = '';

        $this->actingAs($admin)
            ->put(route('admin.nexa-pricing.update'), $payload)
            ->assertRedirect(route('admin.nexa-pricing.edit'));

        $saved = app(NexaPricingService::class)->get();
        $this->assertSame(1, $saved['packages'][0]['free_months'] ?? null);

        $this->get('http://localhost:8085/prijzen')
            ->assertOk()
            ->assertSee('1 maand gratis', false)
            ->assertSee('daarna € 49,-', false);
    }

    #[Test]
    public function saving_prices_preserves_pricing_packages_width_percent(): void
    {
        app(\App\Services\CentralWelcomePageService::class)->syncDefaultContent();
        $packagesKey = NexaPricingService::PACKAGES_SECTION_KEY;
        $page = WebsitePage::query()->where('slug', 'prijzen')->first();
        $this->assertNotNull($page);

        $sections = is_array($page->home_sections) ? $page->home_sections : [];
        $packages = is_array($sections[$packagesKey] ?? null) ? $sections[$packagesKey] : [];
        $packages['width_percent'] = 70;
        $sections[$packagesKey] = $packages;
        $page->home_sections = $sections;
        $page->save();

        $this->actingAs($this->superAdmin())
            ->put(route('admin.nexa-pricing.update'), $this->formPayload())
            ->assertRedirect(route('admin.nexa-pricing.edit'));

        $page->refresh();
        $this->assertSame(70, $page->getHomeSections()[$packagesKey]['width_percent'] ?? null);

        $this->get('http://localhost:8085/prijzen')
            ->assertOk()
            ->assertSee('--nexa-pricing-scale: 70%', false);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(): array
    {
        $pricing = app(NexaPricingService::class)->defaults();
        $packages = [];
        foreach ($pricing['packages'] as $i => $package) {
            $packages[$i] = [
                'key' => $package['key'] ?? '',
                'name' => $package['name'],
                'audience' => $package['audience'],
                'price' => $package['price'],
                'offer' => $package['offer'] ?? '',
                'free_months' => $package['free_months'] ?? 0,
                'period' => $package['period'],
                'badge' => $package['badge'],
                'highlighted' => ! empty($package['highlighted']) ? '1' : '0',
                'cta_text' => $package['cta_text'],
                'cta_url' => $package['cta_url'],
                'features' => $package['features'],
                'entitlements' => $package['entitlements'] ?? [],
            ];
            if ((int) (($packages[$i]['entitlements']['max_drivers'] ?? 0)) === 0) {
                $packages[$i]['entitlements']['max_drivers_unlimited'] = '1';
            }
        }

        $addons = [];
        foreach ($pricing['addons'] as $i => $addon) {
            $addons[$i] = [
                'name' => $addon['name'],
                'price' => $addon['price'],
                'description' => $addon['description'],
            ];
        }

        return [
            'eyebrow' => $pricing['eyebrow'],
            'title' => $pricing['title'],
            'subtitle' => $pricing['subtitle'],
            'vat_note' => $pricing['vat_note'],
            'packages' => $packages,
            'website' => [
                'title' => $pricing['website']['title'],
                'price_prefix' => $pricing['website']['price_prefix'],
                'price_label' => $pricing['website']['price_label'],
                'offer' => $pricing['website']['offer'] ?? '',
                'period' => $pricing['website']['period'],
                'subtitle' => $pricing['website']['subtitle'],
                'cta_text' => $pricing['website']['cta_text'],
                'cta_url' => $pricing['website']['cta_url'],
                'features' => $pricing['website']['features'],
            ],
            'addons' => $addons,
        ];
    }
}
