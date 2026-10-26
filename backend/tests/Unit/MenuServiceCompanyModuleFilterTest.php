<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Modules\NexaTaxi\Support\TaxiDispatchAccess;
use App\Services\MenuService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $start = Company::query()->create(['name' => 'Start Menu', 'is_active' => true, 'package_key' => 'start']);
        $pro = Company::query()->create(['name' => 'Pro Menu', 'is_active' => true, 'package_key' => 'pro']);
        $business = Company::query()->create(['name' => 'Business Menu', 'is_active' => true, 'package_key' => 'business']);
        $marketplace = Company::query()->create(['name' => 'Marketplace Menu', 'is_active' => true, 'package_key' => 'marketplace']);
        $legacy = Company::query()->create(['name' => 'Legacy Menu', 'is_active' => true, 'package_key' => null]);
        foreach ([$start, $pro, $business, $marketplace, $legacy] as $company) {
            $company->modules()->attach($taxi->id);
        }

        $startKeys = $this->menuKeysForTenantAdmin($start);
        $this->assertContains('vehicles', $startKeys);
        $this->assertContains('ride_requests', $startKeys);
        $this->assertContains('tarieven', $startKeys);
        $this->assertNotContains('transport_customers', $startKeys);
        $this->assertNotContains('dispatch_settings', $startKeys);
        $this->assertNotContains('gps_tracking', $startKeys);

        $proKeys = $this->menuKeysForTenantAdmin($pro);
        $this->assertContains('dispatch_settings', $proKeys);
        $this->assertNotContains('transport_customers', $proKeys);
        $this->assertNotContains('gps_tracking', $proKeys);

        $pro->package_addons = [\App\Support\TenantPackageAddon::GPS_TRACKING => 1];
        $pro->save();
        $proWithGpsKeys = $this->menuKeysForTenantAdmin($pro->fresh());
        $this->assertContains('gps_tracking', $proWithGpsKeys);

        $businessKeys = $this->menuKeysForTenantAdmin($business);
        $this->assertContains('transport_customers', $businessKeys);
        $this->assertContains('dispatch_settings', $businessKeys);
        $this->assertNotContains('gps_tracking', $businessKeys);

        $marketplaceKeys = $this->menuKeysForTenantAdmin($marketplace);
        $this->assertContains('dispatch_settings', $marketplaceKeys);
        $this->assertNotContains('tarieven', $marketplaceKeys);

        $legacyKeys = $this->menuKeysForTenantAdmin($legacy);
        $this->assertContains('transport_customers', $legacyKeys);
        $this->assertContains('dispatch_settings', $legacyKeys);
        $this->assertContains('gps_tracking', $legacyKeys);
    }

    #[Test]
    public function super_admin_menu_includes_items_a_marketplace_role_does_not(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'marketplace', 'guard_name' => 'web']);
        foreach (['vehicles.view', 'rates.view', 'rides.view', 'view-mailserver'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);
        $marketplace = Company::query()->create([
            'name' => 'Marketplace Menu',
            'is_active' => true,
            'package_key' => 'marketplace',
        ]);

        if (! Route::has('admin.taxi.tarieven.edit')) {
            Route::middleware('web')
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            Route::getRoutes()->refreshNameLookups();
        }

        $super = User::factory()->create(['company_id' => $marketplace->id]);
        $super->assignRole('super-admin');
        $this->actingAs($super);
        session(['selected_tenant' => $marketplace->id]);

        $items = collect(app(MenuService::class)->getModuleMenuItems());
        $keys = $items->pluck('key')->all();
        $this->assertContains('tarieven', $keys);
        $this->assertContains('dispatch_settings', $keys);
        $this->assertContains('transport_customers', $keys);
        $gps = $items->firstWhere('key', 'gps_tracking');
        $this->assertNotNull($gps);
        $childRoutes = array_column($gps['children'] ?? [], 'route');
        $this->assertContains('admin.taxi.gps_tracking.index', $childRoutes);
        $this->assertContains('admin.taxi.gps_tracking.settings', $childRoutes);

        $superHtml = view('admin.layouts.partials.sidebar')->render();
        $this->assertStringContainsString('Tarieven', $superHtml);
        $this->assertStringContainsString('Chauffeur dispatch', $superHtml);
        $this->assertStringContainsString(route('admin.settings.index'), $superHtml);
        $this->assertStringContainsString('Systeem configuraties', $superHtml);

        $marketplace->modules()->attach($taxi->id);
        $tenant = User::factory()->create(['company_id' => $marketplace->id]);
        $tenant->givePermissionTo(['vehicles.view', 'rates.view', 'rides.view', 'view-mailserver']);
        app(UserRoleAssignmentService::class)->syncWebRoles($tenant, ['marketplace']);
        $this->actingAs($tenant->fresh());
        session()->forget('selected_tenant');

        $tenantKeys = array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
        $this->assertContains('vehicles', $tenantKeys);
        $this->assertNotContains('tarieven', $tenantKeys);
        $this->assertNotContains('dispatch_settings', $tenantKeys);

        $tenantHtml = view('admin.layouts.partials.sidebar')->render();
        $this->assertStringNotContainsString(route('admin.taxi.tarieven.edit'), $tenantHtml);
        $this->assertStringNotContainsString(route('admin.taxi.dispatch_settings.edit'), $tenantHtml);
        $this->assertStringNotContainsString('Mailserver', $tenantHtml);
        $this->assertStringNotContainsString('Systeem configuraties', $tenantHtml);
        $this->assertDoesNotMatchRegularExpression(
            '/href="'.preg_quote(route('admin.settings.index'), '/').'"/',
            $tenantHtml
        );
    }

    /**
     * @return list<string>
     */
    private function menuKeysForTenantAdmin(Company $company): array
    {
        $permissions = array_merge(
            ['vehicles.view', 'rates.view', 'rides.view', 'gps_tracking.view', 'gps_tracking_settings.view'],
            TaxiDispatchAccess::permissionNames(),
        );
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo($permissions);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);
        $this->actingAs($user->fresh());

        return array_column(app(MenuService::class)->getModuleMenuItems(), 'key');
    }
}
