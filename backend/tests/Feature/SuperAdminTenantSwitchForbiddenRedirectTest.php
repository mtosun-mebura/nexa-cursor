<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminTenantSwitchForbiddenRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function super_admin_is_sent_to_dashboard_instead_of_403_when_page_is_outside_selected_tenant(): void
    {
        $tenantA = Company::query()->create(['name' => 'Tenant A', 'is_active' => true]);
        $tenantB = Company::query()->create(['name' => 'Tenant B', 'is_active' => true]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $tenantB->id])
            ->get(route('admin.companies.edit', $tenantA))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('warning');
    }

    #[Test]
    public function super_admin_can_open_the_selected_tenant_company_page(): void
    {
        $tenant = Company::query()->create(['name' => 'Huidige tenant', 'is_active' => true]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $tenant->id])
            ->get(route('admin.companies.edit', $tenant))
            ->assertOk();
    }

    #[Test]
    public function company_admin_still_sees_403_for_another_company(): void
    {
        $tenantA = Company::query()->create(['name' => 'Bedrijf A', 'is_active' => true]);
        $tenantB = Company::query()->create(['name' => 'Bedrijf B', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $tenantB->id]);
        $admin->assignRole('company-admin');

        $this->actingAs($admin)
            ->get(route('admin.companies.edit', $tenantA))
            ->assertForbidden();
    }
}
