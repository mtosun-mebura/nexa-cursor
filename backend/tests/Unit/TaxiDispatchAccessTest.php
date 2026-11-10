<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Support\TaxiDispatchAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiDispatchAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function rides_view_does_not_open_dispatch_or_gps_sections(): void
    {
        TaxiDispatchAccess::ensurePermissions();
        $user = $this->userWithPermissions(['rides.view', 'vehicles.view', 'rides.update']);

        foreach (array_column(TaxiDispatchAccess::sections(), 'key') as $key) {
            $this->assertFalse(TaxiDispatchAccess::sees($user, $key));
            $this->assertFalse(TaxiDispatchAccess::canMutate($user, $key));
        }

        $this->assertFalse(TaxiDispatchAccess::seesDispatchScreen($user));
        $this->assertNotContains('rides.view', TaxiDispatchAccess::dispatchMenuPermissions());
    }

    #[Test]
    public function known_roles_keep_the_dispatch_subset_they_already_had(): void
    {
        foreach (['company-admin', 'marketplace', 'demo', 'chauffeur', 'staff'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        TaxiDispatchAccess::syncKnownRolePresets();

        $companyAdmin = Role::findByName('company-admin', 'web');
        foreach (TaxiDispatchAccess::permissionNames() as $name) {
            $this->assertTrue($companyAdmin->hasPermissionTo($name), $name);
        }

        $marketplace = Role::findByName('marketplace', 'web');
        $this->assertTrue($marketplace->hasPermissionTo('dispatch.network.view'));
        $this->assertFalse($marketplace->hasPermissionTo('dispatch.accept_timer.view'));
        $this->assertFalse($marketplace->hasPermissionTo('dispatch.payments.view'));
        $this->assertFalse($marketplace->hasPermissionTo('gps_tracking.view'));

        $demo = Role::findByName('demo', 'web');
        foreach (TaxiDispatchAccess::dispatchPermissionNames() as $name) {
            $this->assertTrue($demo->hasPermissionTo($name), $name);
        }
        $this->assertFalse($demo->hasPermissionTo('gps_tracking.view'));
        $this->assertFalse($demo->hasPermissionTo('gps_tracking_settings.view'));

        $chauffeur = Role::findByName('chauffeur', 'web');
        $this->assertFalse($chauffeur->hasPermissionTo('dispatch.network.view'));
        $this->assertFalse($chauffeur->hasPermissionTo('gps_tracking.view'));
    }

    #[Test]
    public function a_role_can_receive_one_dispatch_section(): void
    {
        TaxiDispatchAccess::ensurePermissions();
        $user = $this->userWithPermissions(['dispatch.accept_timer.view']);

        $this->assertTrue(TaxiDispatchAccess::sees($user, 'accept_timer'));
        $this->assertFalse(TaxiDispatchAccess::sees($user, 'network'));
        $this->assertFalse(TaxiDispatchAccess::sees($user, 'gps_vehicles'));
        $this->assertTrue(TaxiDispatchAccess::seesDispatchScreen($user));
        $this->assertTrue(TaxiDispatchAccess::canMutate($user, 'accept_timer'));
        $this->assertFalse(TaxiDispatchAccess::canMutate($user, 'payments'));
    }

    #[Test]
    public function gps_sections_are_separate_from_the_dispatch_page(): void
    {
        TaxiDispatchAccess::ensurePermissions();
        $user = $this->userWithPermissions(['gps_tracking_settings.view']);

        $this->assertTrue(TaxiDispatchAccess::sees($user, 'gps_settings'));
        $this->assertFalse(TaxiDispatchAccess::sees($user, 'gps_vehicles'));
        $this->assertFalse(TaxiDispatchAccess::seesDispatchScreen($user));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $role = Role::firstOrCreate(['name' => 'dispatch-'.md5(implode(',', $permissions)), 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }
}
