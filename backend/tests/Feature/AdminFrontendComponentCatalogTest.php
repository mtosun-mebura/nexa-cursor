<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFrontendComponentCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_alle_tenants_shows_platform_wide_component_catalog(): void
    {
        Company::query()->create(['name' => 'Filtertest Tenant A BV', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.frontend-components.index'))
            ->assertOk()
            ->assertSee('Platformbreed — geldt voor alle tenants', false)
            ->assertSee('Google Reviews', false)
            ->assertSee('FAQ-accordion', false)
            ->assertSee('Teamgrid', false)
            ->assertSee('Elevated infokaarten', false)
            ->assertSee('Cijferstrip', false)
            ->assertSee('Blog-preview', false)
            ->assertSee('Auteurheader', false)
            ->assertSee('Landwind', false)
            ->assertSee('Play Tailwind', false)
            ->assertSee('Vue Material Kit', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false)
            ->assertDontSee('Actieve module:', false);
    }

    public function test_selected_tenant_still_shows_the_same_platform_catalog(): void
    {
        $tenant = Company::query()->create(['name' => 'Filtertest Tenant A BV', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $tenant->id])
            ->get(route('admin.frontend-components.index'))
            ->assertOk()
            ->assertSee('Platformbreed — geldt voor alle tenants', false)
            ->assertSee('Google Reviews', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);
    }
}
