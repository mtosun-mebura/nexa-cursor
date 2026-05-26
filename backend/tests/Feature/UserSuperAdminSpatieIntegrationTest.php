<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSuperAdminSpatieIntegrationTest extends TestCase
{
    #[Test]
    public function super_admin_has_role_by_name_and_by_role_primary_key(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue($user->hasRole($role->getKey()));
    }

    #[Test]
    public function has_all_roles_treats_super_admin_when_global_super_admin_not_visible_on_team_relationship(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $otherRole = Role::firstOrCreate(['name' => 'company-staff', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole($superAdminRole);
        $user->assignRole($otherRole);

        $this->assertTrue($user->hasAllRoles(['super-admin', 'company-staff']));
    }
}
