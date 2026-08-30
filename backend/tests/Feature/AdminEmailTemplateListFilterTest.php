<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminEmailTemplateListFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function alle_tenants_shows_all_tenants_and_the_tenant_filter(): void
    {
        [$admin, $tenantA, $tenantB] = $this->seedTemplates();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.email-templates.index'))
            ->assertOk()
            ->assertSee('Algemeen Filtertest ZX-GLB', false)
            ->assertSee('TenantA Filtertest ZX-A', false)
            ->assertSee('TenantB Filtertest ZX-B', false)
            ->assertSee('id="company-filter"', false)
            ->assertSee('Filtertest Tenant A BV', false)
            ->assertSee('Filtertest Tenant B BV', false);
    }

    #[Test]
    public function alle_tenants_can_filter_the_list_by_tenant(): void
    {
        [$admin, $tenantA, $tenantB] = $this->seedTemplates();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.email-templates.index', ['company_id' => $tenantA->id]))
            ->assertOk()
            ->assertSee('TenantA Filtertest ZX-A', false)
            ->assertDontSee('TenantB Filtertest ZX-B', false)
            ->assertDontSee('Algemeen Filtertest ZX-GLB', false);
    }

    #[Test]
    public function alle_tenants_can_filter_to_general_templates(): void
    {
        [$admin, $tenantA] = $this->seedTemplates();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.email-templates.index', ['company_id' => 'algemeen']))
            ->assertOk()
            ->assertSee('Algemeen Filtertest ZX-GLB', false)
            ->assertDontSee('TenantA Filtertest ZX-A', false);
    }

    #[Test]
    public function selected_tenant_shows_only_that_tenants_templates(): void
    {
        [$admin, $tenantA, $tenantB] = $this->seedTemplates();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $tenantA->id])
            ->get(route('admin.email-templates.index'))
            ->assertOk()
            ->assertSee('TenantA Filtertest ZX-A', false)
            ->assertDontSee('Algemeen Filtertest ZX-GLB', false)
            ->assertDontSee('TenantB Filtertest ZX-B', false)
            ->assertDontSee('id="company-filter"', false);
    }

    /**
     * @return array{0: User, 1: Company, 2: Company}
     */
    private function seedTemplates(): array
    {
        $tenantA = Company::query()->create(['name' => 'Filtertest Tenant A BV', 'is_active' => true]);
        $tenantB = Company::query()->create(['name' => 'Filtertest Tenant B BV', 'is_active' => true]);

        EmailTemplate::query()->create([
            'name' => 'Algemeen Filtertest ZX-GLB',
            'subject' => 'Algemeen onderwerp',
            'type' => 'custom',
            'html_content' => '<p>algemeen</p>',
            'is_active' => true,
            'company_id' => null,
        ]);

        EmailTemplate::query()->create([
            'name' => 'TenantA Filtertest ZX-A',
            'subject' => 'Tenant A onderwerp',
            'type' => 'welcome',
            'html_content' => '<p>tenant a</p>',
            'is_active' => true,
            'company_id' => $tenantA->id,
        ]);

        EmailTemplate::query()->create([
            'name' => 'TenantB Filtertest ZX-B',
            'subject' => 'Tenant B onderwerp',
            'type' => 'welcome',
            'html_content' => '<p>tenant b</p>',
            'is_active' => true,
            'company_id' => $tenantB->id,
        ]);

        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return [$admin, $tenantA, $tenantB];
    }
}
