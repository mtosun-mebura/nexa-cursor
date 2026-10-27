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

    public function test_index_has_search_and_full_bleed_table_padding(): void
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.frontend-components.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="frontend-components-search"', $html);
        $this->assertStringContainsString('placeholder="Zoek component..."', $html);
        $this->assertStringContainsString('aria-label="Zoek component..."', $html);
        $this->assertStringContainsString('ki-outline ki-magnifier', $html);
        $this->assertStringContainsString('w-full max-w-md', $html);
        $this->assertStringContainsString('id="frontend-components-search-clear"', $html);
        $this->assertMatchesRegularExpression(
            '/id="frontend-components-count"[^>]*>\s*\d+ van \d+ componenten\s*</',
            $html
        );
        $this->assertStringContainsString('Geen componenten gevonden voor &quot;:query&quot;.', $html);
        $this->assertStringContainsString('class="kt-card-table"', $html);
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*kt-card-table[^"]*\bp-4\b/', $html);
        $this->assertStringContainsString('admin-table-scroll-wrap', $html);
        $this->assertStringContainsString('data-label="Component"', $html);
        $this->assertStringContainsString('width:22%', $html);
        $this->assertStringContainsString('width:42%', $html);
        $this->assertMatchesRegularExpression('/<th[^>]*class="[^"]*\bpx-5/', $html);
        $this->assertMatchesRegularExpression('/<td[^>]*class="[^"]*\bpx-5/', $html);
        $this->assertStringContainsString('Escape', $html);
    }
}
