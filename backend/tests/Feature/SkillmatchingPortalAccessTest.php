<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SkillmatchingPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function dashboard_redirects_home_when_skillmatching_inactive(): void
    {
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('home'));
    }

    #[Test]
    public function dashboard_is_available_when_skillmatching_active(): void
    {
        Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    #[Test]
    public function site_branding_shows_dashboard_link_for_taxi_module(): void
    {
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi',
            ],
        ]);

        $branding = app(\App\Services\WebsiteBuilderService::class)->getSiteBranding('taxi');

        $this->assertTrue($branding['dashboard_link_visible']);
        $this->assertSame('Mijn Taxi', $branding['dashboard_link_label']);
        $this->assertSame(route('taxi.portal.dashboard'), $branding['dashboard_link_url']);
    }

    #[Test]
    public function site_branding_shows_dashboard_link_for_skillmatching_module(): void
    {
        Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Skillmatching',
            ],
        ]);

        $branding = app(\App\Services\WebsiteBuilderService::class)->getSiteBranding('skillmatching');

        $this->assertTrue($branding['dashboard_link_visible']);
        $this->assertSame('Mijn Skillmatching', $branding['dashboard_link_label']);
        $this->assertSame(route('dashboard'), $branding['dashboard_link_url']);
    }

    #[Test]
    public function site_branding_hides_dashboard_link_when_tenant_lacks_module(): void
    {
        $skillmatching = Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Skillmatching',
            ],
        ]);
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Only', 'slug' => 'taxi-only-'.uniqid()]);
        $company->modules()->attach(Module::where('name', 'taxi')->first()->id);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $branding = app(\App\Services\WebsiteBuilderService::class)->getSiteBranding('skillmatching');

        $this->assertFalse($branding['dashboard_link_visible']);
    }

    #[Test]
    public function taxi_portal_redirects_home_when_tenant_lacks_taxi_module(): void
    {
        Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
        ]);
        $taxi = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create(['name' => 'Skill Only', 'slug' => 'skill-only-'.uniqid()]);
        $company->modules()->attach(Module::where('name', 'skillmatching')->first()->id);

        config(['tenancy.dev_host_company_map' => ['localhost' => $company->id]]);

        // Super-admin mag de portal altijd openen (EnsureTenantTaxiModule); gewone gebruiker niet.
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('taxi.portal.dashboard'))
            ->assertRedirect(route('home'));
    }

    #[Test]
    public function taxi_portal_is_available_when_tenant_has_taxi_module(): void
    {
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_label' => 'Mijn Taxi',
            ],
        ]);

        $company = Company::query()->create(['name' => 'Taxi Tenant', 'slug' => 'taxi-tenant-'.uniqid()]);
        $company->modules()->attach(Module::where('name', 'taxi')->first()->id);

        config(['tenancy.dev_host_company_map' => ['localhost' => $company->id]]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('taxi.portal.dashboard'))
            ->assertOk()
            ->assertSee('Mijn Taxi', false);
    }

    #[Test]
    public function dashboard_redirects_home_for_tenant_without_skillmatching_module(): void
    {
        Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
        ]);
        $taxi = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Only', 'slug' => 'taxi-only-'.uniqid()]);
        $company->modules()->attach($taxi->id);

        config(['tenancy.dev_host_company_map' => ['localhost' => $company->id]]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('home'));
    }

    #[Test]
    public function login_page_hides_skillmatching_content_for_taxi_tenant_with_mijn_taxi_intended(): void
    {
        Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'app_name' => 'Taxi Royaal',
                'dashboard_link_label' => 'Mijn Taxi',
            ],
        ]);

        $company = Company::query()->create(['name' => 'Taxi Royaal', 'slug' => 'taxi-royaal-'.uniqid()]);
        $company->modules()->attach(Module::where('name', 'taxi')->first()->id);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->get(route('login', [
            'intended' => route('taxi.portal.dashboard'),
        ]));

        $response->assertOk();
        $response->assertSee('Taxi Royaal', false);
        $response->assertDontSee('>Vacatures</a>', false);
        $response->assertDontSee('Waarom inloggen?', false);
        $response->assertDontSee('AI Matches', false);
    }

    #[Test]
    public function site_branding_shows_mijn_taxi_for_tenant_home_without_module_name(): void
    {
        $theme = \App\Models\FrontendTheme::create([
            'slug' => 'atom-v2',
            'name' => 'Atom v2',
            'description' => 'Test',
            'is_active' => true,
            'settings' => ['primary_color' => '#2563eb'],
        ]);

        $taxi = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi',
            ],
            'frontend_theme_id' => $theme->id,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Royaal', 'slug' => 'taxiroyaal']);
        $company->modules()->attach($taxi->id);

        $page = \App\Models\WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'component:taxi.boekingsmodule' => ['title' => 'Boek eenvoudig je taxirit'],
            ],
        ]);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $branding = app(\App\Services\WebsiteBuilderService::class)->getSiteBrandingForWebsitePage($page);

        $this->assertTrue($branding['dashboard_link_visible']);
        $this->assertSame('Mijn Taxi', $branding['dashboard_link_label']);
        $this->assertSame(route('taxi.portal.dashboard'), $branding['dashboard_link_url']);
    }

    #[Test]
    public function tenant_home_renders_mijn_taxi_button_when_module_config_allows(): void
    {
        $theme = \App\Models\FrontendTheme::create([
            'slug' => 'atom-v2',
            'name' => 'Atom v2',
            'description' => 'Test',
            'is_active' => true,
            'settings' => [
                'primary_color' => '#2563eb',
                'font_heading' => 'Inter',
                'font_body' => 'Inter',
                'dark_mode_available' => true,
            ],
        ]);

        $taxi = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi',
            ],
            'frontend_theme_id' => $theme->id,
        ]);

        $company = Company::query()->create(['name' => 'Taxi Royaal', 'slug' => 'taxiroyaal']);
        $company->modules()->attach($taxi->id);
        $company->update(['frontend_theme_id' => $theme->id]);

        \App\Models\WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'component:taxi.boekingsmodule' => ['title' => 'Boek eenvoudig je taxirit'],
            ],
        ]);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->get('https://taxiroyaal.nexasuite.nl/');

        $response->assertOk();
        $response->assertSee('Mijn Taxi', false);
        $response->assertSee(route('login', ['intended' => route('taxi.portal.dashboard')]), false);
    }

    #[Test]
    public function resolve_public_frontend_module_name_uses_mijn_taxi_intended(): void
    {
        $request = \Illuminate\Http\Request::create('/login', 'GET', [
            'intended' => 'http://localhost/mijn-taxi',
        ]);

        $this->assertSame('taxi', app(\App\Services\WebsiteBuilderService::class)->resolvePublicFrontendModuleName($request));
    }
}
