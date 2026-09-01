<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCompaniesIndexButtonsTest extends TestCase
{
    #[Test]
    public function super_admin_user_can_create_companies_via_role_or_db_check(): void
    {
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-companies', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('guard_name', 'web')->get());

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->canCreateCompanies());
    }

    #[Test]
    public function company_admin_cannot_create_companies_even_with_permission(): void
    {
        Permission::firstOrCreate(['name' => 'create-companies', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('company-admin');
        $user->givePermissionTo('create-companies');

        $this->assertFalse($user->canCreateCompanies());
    }

    #[Test]
    public function companies_index_create_actions_are_super_admin_only(): void
    {
        $path = resource_path('views/admin/companies/index.blade.php');
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertStringContainsString('Nieuwe tenant (wizard)', $contents);
        $this->assertStringContainsString('data-company-create-actions', $contents);
        $this->assertStringContainsString('canCreateCompanies()', $contents);
    }

    #[Test]
    public function company_admin_does_not_see_create_company_buttons(): void
    {
        Permission::firstOrCreate(['name' => 'view-companies', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('company-admin');
        $user->givePermissionTo('view-companies');

        $this->actingAs($user)
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertDontSee('Nieuwe tenant (wizard)', false)
            ->assertDontSee('Nieuw bedrijf (formulier)', false)
            ->assertDontSee('E-mail Templates', false);
    }

    #[Test]
    public function super_admin_sees_create_company_buttons(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee('Nieuwe tenant (wizard)', false)
            ->assertSee('Nieuw bedrijf (formulier)', false);
    }
}
