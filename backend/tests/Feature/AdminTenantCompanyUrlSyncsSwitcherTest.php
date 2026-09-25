<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTenantCompanyUrlSyncsSwitcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function tenant_company_query_selects_that_tenant_in_sidebar_and_session(): void
    {
        $tenant = Company::query()->create(['name' => 'URL Tenant '.uniqid(), 'is_active' => true]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $html = $this->actingAs($super)
            ->get(route('admin.website-pages.index', ['tenant_company' => $tenant->id]))
            ->assertOk()
            ->assertSessionHas('selected_tenant', $tenant->id)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/class="tenant-toggle-full[\s\S]{0,900}?<span class="truncate">\s*'.preg_quote($tenant->name, '/').'\s*<\/span>/',
            $html
        );
    }

    #[Test]
    public function tenant_company_query_overrides_previously_selected_tenant(): void
    {
        $tenantA = Company::query()->create(['name' => 'Oude tenant '.uniqid(), 'is_active' => true]);
        $tenantB = Company::query()->create(['name' => 'Nieuwe tenant '.uniqid(), 'is_active' => true]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $tenantA->id])
            ->get(route('admin.website-pages.index', ['tenant_company' => $tenantB->id]))
            ->assertOk()
            ->assertSessionHas('selected_tenant', $tenantB->id);
    }

    #[Test]
    public function unknown_tenant_company_query_does_not_change_session(): void
    {
        $tenant = Company::query()->create(['name' => 'Blijft staan '.uniqid(), 'is_active' => true]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $tenant->id])
            ->get(route('admin.website-pages.index', ['tenant_company' => 999999]))
            ->assertOk()
            ->assertSessionHas('selected_tenant', $tenant->id);
    }

    #[Test]
    public function company_admin_is_not_affected_by_tenant_company_query(): void
    {
        $own = Company::query()->create(['name' => 'Eigen '.uniqid(), 'is_active' => true]);
        $other = Company::query()->create(['name' => 'Andere '.uniqid(), 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $own->id]);
        $admin->assignRole('company-admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tenant_company' => $other->id]))
            ->assertOk()
            ->assertSessionMissing('selected_tenant');
    }
}
