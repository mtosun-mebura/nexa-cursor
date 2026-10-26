<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\DispatchSettingsController;
use App\Modules\NexaTaxi\Support\TaxiDispatchAccess;
use App\Services\MenuService;
use App\Services\UserRoleAssignmentService;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleDispatchPermissionsTest extends TestCase
{
    #[Test]
    public function show_lists_only_assigned_dispatch_rights_without_checkboxes(): void
    {
        TaxiDispatchAccess::ensurePermissions();
        $company = Company::query()->create(['name' => 'Rol Taxi', 'is_active' => true]);
        $role = Role::create([
            'name' => 'dispatch-show-role',
            'guard_name' => 'web',
            'description' => 'Test',
        ]);
        Permission::firstOrCreate(['name' => 'view-users', 'guard_name' => 'web']);
        $role->syncPermissions(['view-users', 'dispatch.network.view']);

        $member = User::factory()->create([
            'first_name' => 'Chauffeur',
            'last_name' => 'Start2',
            'email' => 'qa.start.chauffeur2@nexa.test',
            'company_id' => $company->id,
        ]);
        $member->assignRole($role);

        $show = $this->actingAs($this->superAdmin())
            ->get(route('admin.roles.show', $role))
            ->assertOk()
            ->assertSee('NEXA Network', false)
            ->assertSee('Chauffeur Start2', false)
            ->assertSee('qa.start.chauffeur2@nexa.test', false)
            ->assertSee('Rol Taxi', false)
            ->assertSee('data-admin-datatable="true"', false)
            ->assertSee('data-admin-datatable-pagination="true"', false)
            ->assertSee('data-admin-datatable-search="#role_users_table"', false)
            ->assertSee('data-admin-datatable-filter="company"', false)
            ->assertSee('title="Bekijken"', false)
            ->assertSee('title="Bewerken"', false)
            ->assertDontSee('Acceptatietimer', false)
            ->assertDontSee('Boekingsmeldingen', false)
            ->assertDontSee('name="permissions[]"', false)
            ->assertDontSee('value="dispatch.accept_timer.view"', false)
            ->assertDontSee('ziet deze schermen nog steeds allemaal', false)
            ->getContent();

        $this->assertStringNotContainsString('Kies welk onderdeel deze rol mag zien', $show);

        $edit = $this->actingAs($this->superAdmin())
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertSee('Kies welk onderdeel deze rol mag zien', false)
            ->assertDontSee('ziet deze schermen nog steeds allemaal', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/value="dispatch\.network\.view"\s+checked/', $edit);
        $this->assertMatchesRegularExpression('/value="dispatch\.accept_timer\.view"\s*>/', $edit);
        $this->assertDoesNotMatchRegularExpression('/value="dispatch\.accept_timer\.view"\s+checked/', $edit);
    }

    #[Test]
    public function zero_dispatch_permissions_hide_the_menu_and_deny_the_page(): void
    {
        TaxiDispatchAccess::ensurePermissions();
        Permission::firstOrCreate(['name' => 'rides.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'vehicles.view', 'guard_name' => 'web']);

        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Pro zonder dispatch',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [\App\Support\TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $company->modules()->attach($taxi->id);

        Role::firstOrCreate(['name' => 'fleet-viewer', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['rides.view', 'vehicles.view']);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['fleet-viewer']);

        $this->actingAs($user);
        $keys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertNotContains('dispatch_settings', $keys);
        $this->assertNotContains('gps_tracking', $keys);

        try {
            app(DispatchSettingsController::class)->edit();
            $this->fail('Dispatch zonder vinkjes had 403 moeten geven.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $user->givePermissionTo('dispatch.accept_timer.view');
        $user->givePermissionTo('gps_tracking.view');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user->fresh());

        $keys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertContains('dispatch_settings', $keys);
        $gps = collect(app(MenuService::class)->getModuleMenuItems())->firstWhere('key', 'gps_tracking');
        $this->assertNotNull($gps);
        $childRoutes = array_column($gps['children'] ?? [], 'route');
        $this->assertContains('admin.taxi.gps_tracking.index', $childRoutes);
        $this->assertNotContains('admin.taxi.gps_tracking.settings', $childRoutes);
        $this->assertTrue(TaxiDispatchAccess::seesDispatchScreen($user->fresh()));
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $super = User::factory()->create();
        app(UserRoleAssignmentService::class)->syncWebRoles($super, ['super-admin']);

        return $super;
    }
}
