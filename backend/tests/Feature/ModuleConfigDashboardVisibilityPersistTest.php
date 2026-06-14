<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Services\ModuleConfigurationService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleConfigDashboardVisibilityPersistTest extends TestCase
{
    private function createTaxiModule(): Module
    {
        return Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'enabled_menu_items' => ['vehicles', 'tarieven', 'ride_requests'],
                'dashboard_link_visible' => '0',
            ],
        ]);
    }

    private function createTenantWithModule(Module $module): Company
    {
        $suffix = uniqid();
        $company = Company::create([
            'name' => 'Tenant Test '.$suffix,
            'slug' => 'tenant-test-'.$suffix,
            'email' => 'tenant-'.$suffix.'@example.com',
            'is_active' => true,
        ]);
        $company->modules()->attach($module->id);

        return $company;
    }

    public function test_save_config_persists_dashboard_link_visible_for_taxi_per_tenant(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $module = $this->createTaxiModule();
        $company = $this->createTenantWithModule($module);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.modules.config.store', 'taxi'), [
                'enabled_menu_items' => ['vehicles', 'tarieven', 'ride_requests'],
                'app_name' => 'Taxi Test',
                'app_description' => '',
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi',
            ]);

        $response->assertRedirect(route('admin.modules.config', 'taxi'));
        $module->refresh();
        $this->assertSame('0', $module->configuration['dashboard_link_visible'] ?? null);

        $tenantConfig = app(ModuleConfigurationService::class)->getTenantSettings($module, $company->id);
        $this->assertSame('1', $tenantConfig['dashboard_link_visible'] ?? null);
        $this->assertSame('Mijn Taxi', $tenantConfig['dashboard_link_label'] ?? null);
    }

    public function test_save_config_persists_dashboard_link_visible_for_skillmatching_per_tenant(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $module = Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Nexa Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'enabled_menu_items' => [],
            ],
        ]);
        $company = $this->createTenantWithModule($module);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.modules.config.store', 'skillmatching'), [
                'enabled_menu_items' => [],
                'app_name' => 'Skillmatching Test',
                'app_description' => '',
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Skillmatching',
            ]);

        $response->assertRedirect(route('admin.modules.config', 'skillmatching'));
        $tenantConfig = app(ModuleConfigurationService::class)->getTenantSettings($module, $company->id);
        $this->assertSame('1', $tenantConfig['dashboard_link_visible'] ?? null);
        $this->assertSame('Mijn Skillmatching', $tenantConfig['dashboard_link_label'] ?? null);
    }

    public function test_save_config_persists_dashboard_link_visible_as_string_zero_when_zero_posted(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $module = $this->createTaxiModule();
        $company = $this->createTenantWithModule($module);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $company->id])
            ->post(route('admin.modules.config.store', 'taxi'), [
                'enabled_menu_items' => ['vehicles'],
                'app_name' => '',
                'app_description' => '',
                'dashboard_link_visible' => '0',
                'dashboard_link_label' => 'Mijn Nexa',
            ]);

        $response->assertRedirect(route('admin.modules.config', 'taxi'));
        $tenantConfig = app(ModuleConfigurationService::class)->getTenantSettings($module, $company->id);
        $this->assertSame('0', $tenantConfig['dashboard_link_visible'] ?? null);
    }

    public function test_super_admin_without_tenant_cannot_save_module_config(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->createTaxiModule();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post(route('admin.modules.config.store', 'taxi'), [
            'enabled_menu_items' => ['vehicles'],
            'app_name' => 'Taxi',
            'app_description' => '',
            'dashboard_link_visible' => '1',
            'dashboard_link_label' => 'Mijn Taxi',
        ]);

        $response->assertRedirect(route('admin.modules.config', 'taxi'));
        $response->assertSessionHas('error');
    }

    public function test_different_tenants_can_have_different_module_settings(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $module = $this->createTaxiModule();
        $companyA = $this->createTenantWithModule($module);
        $companyB = $this->createTenantWithModule($module);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $service = app(ModuleConfigurationService::class);

        $this->actingAs($user)
            ->withSession(['selected_tenant' => $companyA->id])
            ->post(route('admin.modules.config.store', 'taxi'), [
                'enabled_menu_items' => ['vehicles'],
                'app_name' => 'Taxi A',
                'app_description' => '',
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi A',
            ]);

        $this->actingAs($user)
            ->withSession(['selected_tenant' => $companyB->id])
            ->post(route('admin.modules.config.store', 'taxi'), [
                'enabled_menu_items' => ['vehicles'],
                'app_name' => 'Taxi B',
                'app_description' => '',
                'dashboard_link_visible' => '0',
                'dashboard_link_label' => 'Mijn Taxi B',
            ]);

        $configA = $service->getConfiguration($module, $companyA->id);
        $configB = $service->getConfiguration($module, $companyB->id);

        $this->assertSame('Taxi A', $configA['app_name']);
        $this->assertSame('1', $configA['dashboard_link_visible']);
        $this->assertSame('Taxi B', $configB['app_name']);
        $this->assertSame('0', $configB['dashboard_link_visible']);
    }
}
