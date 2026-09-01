<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuServiceCompanyModuleFilterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_only_sees_menu_items_for_attached_modules(): void
    {
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        foreach (['vehicles.view', 'skillmatching.vacancies.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

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

        $company = Company::query()->create(['name' => 'Taxi Demo Co', 'is_active' => true]);
        $company->modules()->attach(Module::query()->where('name', 'taxi')->value('id'));

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['vehicles.view', 'skillmatching.vacancies.view']);
        app(\App\Services\UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        $this->actingAs($user);
        $items = app(MenuService::class)->getModuleMenuItems();
        $modules = array_values(array_unique(array_column($items, 'module')));

        $this->assertContains('taxi', $modules);
        $this->assertNotContains('skillmatching', $modules);
        $this->assertNotContains('ai_chatbot', array_column($items, 'key'));
    }

    #[Test]
    public function package_hides_menu_items_the_tenant_may_not_use(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $start = Company::query()->create(['name' => 'Start Menu', 'is_active' => true, 'package_key' => 'start']);
        $pro = Company::query()->create(['name' => 'Pro Menu', 'is_active' => true, 'package_key' => 'pro']);
        $business = Company::query()->create(['name' => 'Business Menu', 'is_active' => true, 'package_key' => 'business']);
        $legacy = Company::query()->create(['name' => 'Legacy Menu', 'is_active' => true, 'package_key' => null]);
        foreach ([$start, $pro, $business, $legacy] as $company) {
            $company->modules()->attach($taxi->id);
        }

        session(['selected_tenant' => $start->id]);
        $startKeys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertContains('vehicles', $startKeys);
        $this->assertContains('ride_requests', $startKeys);
        $this->assertNotContains('transport_customers', $startKeys);
        $this->assertNotContains('dispatch_settings', $startKeys);

        session(['selected_tenant' => $pro->id]);
        $proKeys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertContains('dispatch_settings', $proKeys);
        $this->assertNotContains('transport_customers', $proKeys);

        session(['selected_tenant' => $business->id]);
        $businessKeys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertContains('transport_customers', $businessKeys);
        $this->assertContains('dispatch_settings', $businessKeys);

        session(['selected_tenant' => $legacy->id]);
        $legacyKeys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertContains('transport_customers', $legacyKeys);
        $this->assertContains('dispatch_settings', $legacyKeys);
    }
}
