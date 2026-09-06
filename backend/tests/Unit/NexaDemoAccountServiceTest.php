<?php

namespace Tests\Unit;

use App\Models\Module;
use App\Models\User;
use App\Services\MenuService;
use App\Services\NexaDemoAccountService;
use App\Support\AdminPanelRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NexaDemoAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ensure_creates_taxi_only_demo_role_user(): void
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Skillmatching',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $result = app(NexaDemoAccountService::class)->ensure();

        $this->assertNotNull($result['user']);
        $this->assertNotNull($result['company']);
        $this->assertSame(config('nexa_demo.email'), $result['user']->email);
        $this->assertTrue(Hash::check(config('nexa_demo.password'), $result['user']->password));
        $this->assertContains('demo', $result['user']->webRoleNames());
        $this->assertNotContains('company-admin', $result['user']->webRoleNames());
        $this->assertTrue($result['user']->canAccessAdminPanel());
        $this->assertTrue($result['company']->hasTaxiModule());
        $this->assertFalse($result['company']->hasSkillmatchingModule());
        $this->assertTrue(User::query()->where('email', config('nexa_demo.email'))->exists());
        $this->assertTrue(Permission::query()->where('name', 'vehicles.view')->exists());
    }

    #[Test]
    public function demo_user_has_admin_role_with_team_context(): void
    {
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $result = app(NexaDemoAccountService::class)->ensure();
        $user = $result['user'];
        $this->assertNotNull($user);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);
        $user->unsetRelation('roles');

        $this->assertTrue($user->canAccessAdminPanel());
        $this->assertTrue($user->hasRole('demo'));
        $this->assertTrue(app(NexaDemoAccountService::class)->isDemoUser($user));
        $this->assertFalse($user->can('view-companies'));
        $this->assertFalse($user->can('view-users'));
        $this->assertFalse($user->can('view-email-templates'));
    }

    #[Test]
    public function ensure_does_not_reset_existing_demo_password(): void
    {
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $result = app(NexaDemoAccountService::class)->ensure();
        $this->assertNotNull($result['user']);
        $result['user']->update(['password' => 'changed-by-demo']);

        app(NexaDemoAccountService::class)->ensure();

        $demo = User::query()->where('email', config('nexa_demo.email'))->first();
        $this->assertNotNull($demo);
        $this->assertTrue(Hash::check('changed-by-demo', $demo->password));
        $this->assertFalse(Hash::check(config('nexa_demo.password'), $demo->password));
    }

    #[Test]
    public function reset_removes_extra_users_and_restores_password(): void
    {
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $result = app(NexaDemoAccountService::class)->ensure();
        $this->assertNotNull($result['company']);
        $this->assertNotNull($result['user']);

        $extra = User::factory()->create(['company_id' => $result['company']->id]);
        $result['user']->update(['password' => Hash::make('changed-by-demo')]);

        app(NexaDemoAccountService::class)->reset();

        $this->assertFalse(User::query()->where('id', $extra->id)->exists());
        $demo = User::query()->where('email', config('nexa_demo.email'))->first();
        $this->assertNotNull($demo);
        $this->assertTrue(Hash::check(config('nexa_demo.password'), $demo->password));
        $this->assertContains('demo', $demo->webRoleNames());
    }

    #[Test]
    public function demo_menu_hides_ai_chatbot_and_other_modules(): void
    {
        foreach (['vehicles.view', 'rides.view', 'rates.view', 'ai_chatbot.view', 'skillmatching.vacancies.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        Module::query()->create([
            'name' => 'skillmatching',
            'display_name' => 'Skillmatching',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $result = app(NexaDemoAccountService::class)->ensure();
        $this->actingAs($result['user']);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId((int) $result['user']->company_id);

        $items = app(MenuService::class)->getModuleMenuItems();
        $keys = array_values(array_filter(array_column($items, 'key')));
        $modules = array_values(array_unique(array_column($items, 'module')));

        $this->assertContains('vehicles', $keys);
        $this->assertContains('ride_requests', $keys);
        $this->assertContains('transport_customers', $keys);
        $this->assertContains('dispatch_settings', $keys);
        $this->assertNotContains('ai_chatbot', $keys);
        $this->assertNotContains('skillmatching', $modules);
    }

    #[Test]
    public function demo_is_a_system_role(): void
    {
        $this->assertTrue(AdminPanelRoles::isSystemRole('demo'));
        $this->assertContains('demo', AdminPanelRoles::PANEL);
    }

    #[Test]
    public function demo_user_suppresses_outgoing_mail(): void
    {
        Role::firstOrCreate(['name' => 'demo', 'guard_name' => 'web']);
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $result = app(NexaDemoAccountService::class)->ensure();
        $this->actingAs($result['user']);

        $this->assertTrue(app(NexaDemoAccountService::class)->shouldSuppressOutgoingMail());
    }
}
