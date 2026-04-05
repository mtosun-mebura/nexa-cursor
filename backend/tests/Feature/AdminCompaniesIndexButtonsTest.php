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
    public function companies_index_blade_contains_visible_create_actions(): void
    {
        $path = resource_path('views/admin/companies/index.blade.php');
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertStringContainsString('Nieuwe tenant (wizard)', $contents);
        $this->assertStringContainsString('data-company-create-actions', $contents);
        $this->assertStringNotContainsString('$canCreateCompany', $contents);
    }
}
