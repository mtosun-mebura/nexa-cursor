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

        $user = User::factory()->create();
        $user->assignRole('super-admin');

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
}
