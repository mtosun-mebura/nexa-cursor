<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserForceLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-users', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_admin_can_revoke_driver_tokens_from_user_details(): void
    {
        $company = Company::query()->create(['name' => 'Force Logout Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $role = Role::findByName('company-admin', 'web');
        $role->givePermissionTo(['view-users', 'edit-users']);
        app(\App\Services\UserRoleAssignmentService::class)->syncWebRoles($admin, ['company-admin']);

        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->createToken('taxi-driver', ['taxi:driver'], now()->addDays(14));
        $driver->createToken('taxi-contract', ['taxi:contract'], now()->addDays(14));

        $this->assertSame(2, $driver->tokens()->count());

        $this->actingAs($admin->fresh(), 'web')
            ->from(route('admin.users.show', $driver))
            ->post(route('admin.users.force-logout', $driver))
            ->assertRedirect(route('admin.users.show', $driver))
            ->assertSessionHas('success');

        $this->assertSame(0, $driver->fresh()->tokens()->count());
        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $driver->id)->count());
    }

    #[Test]
    public function users_index_and_show_have_force_logout_action(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $admin->company_id])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.force-logout', $user), false)
            ->assertSee('Op afstand uitloggen', false);

        $this->actingAs($admin, 'web')
            ->withSession(['selected_tenant' => $admin->company_id])
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee(route('admin.users.force-logout', $user), false);
    }

    #[Test]
    public function admin_cannot_force_logout_self(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.show', $admin))
            ->post(route('admin.users.force-logout', $admin))
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHas('error');
    }

    private function superAdmin(): User
    {
        $company = Company::query()->create(['name' => 'Force Logout Admin Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        return $admin;
    }
}
