<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleConfigDashboardVisibilityPersistTest extends TestCase
{
    public function test_save_config_persists_dashboard_link_visible_as_string_one_for_taxi(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $module = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'enabled_menu_items' => ['vehicles', 'tarieven', 'ride_requests'],
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post(route('admin.modules.config.store', 'taxi'), [
            'enabled_menu_items' => ['vehicles', 'tarieven', 'ride_requests'],
            'app_name' => 'Taxi Test',
            'app_description' => '',
            'dashboard_link_visible' => '1',
            'dashboard_link_label' => 'Mijn Taxi',
        ]);

        $response->assertRedirect(route('admin.modules.config', 'taxi'));
        $module->refresh();
        $this->assertSame('1', $module->configuration['dashboard_link_visible'] ?? null);
        $this->assertSame('Mijn Taxi', $module->configuration['dashboard_link_label'] ?? null);
    }

    public function test_save_config_persists_dashboard_link_visible_as_string_zero_when_zero_posted(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $module = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'enabled_menu_items' => ['vehicles'],
                'dashboard_link_visible' => '1',
            ],
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post(route('admin.modules.config.store', 'taxi'), [
            'enabled_menu_items' => ['vehicles'],
            'app_name' => '',
            'app_description' => '',
            'dashboard_link_visible' => '0',
            'dashboard_link_label' => 'Mijn Nexa',
        ]);

        $response->assertRedirect(route('admin.modules.config', 'taxi'));
        $module->refresh();
        $this->assertSame('0', $module->configuration['dashboard_link_visible'] ?? null);
    }
}
