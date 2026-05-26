<?php

namespace Tests\Feature;

use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\User;
use App\Models\WebsitePage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StagingDashboardLinkVisibleTest extends TestCase
{
    public function test_staging_with_empty_module_query_shows_dashboard_cta_when_module_allows(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $theme = FrontendTheme::create([
            'slug' => 'modern',
            'name' => 'Metronic',
            'description' => 'Test',
            'is_active' => true,
            'settings' => [
                'primary_color' => '#2563eb',
                'font_heading' => 'Inter',
                'font_body' => 'Inter',
                'dark_mode_available' => true,
            ],
        ]);

        $module = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi',
            ],
            'frontend_theme_id' => $theme->id,
        ]);
        $theme->update(['active_module_id' => $module->id]);

        WebsitePage::create([
            'slug' => 'home',
            'title' => 'Home',
            'content' => '',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.frontend-themes.staging', [
            'theme_id' => $theme->id,
            'module' => '',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('id="website-hamburger-row"', $html);
        $this->assertStringContainsString('Mijn Taxi', $html);
        $this->assertStringContainsString(route('dashboard'), $html);
    }

    public function test_staging_branding_uses_pinned_theme_module_not_page_module_name_when_they_differ(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $theme = FrontendTheme::create([
            'slug' => 'modern',
            'name' => 'Metronic',
            'description' => 'Test',
            'is_active' => true,
            'settings' => [
                'primary_color' => '#2563eb',
                'font_heading' => 'Inter',
                'font_body' => 'Inter',
                'dark_mode_available' => true,
            ],
        ]);

        $taxi = Module::create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '1',
                'dashboard_link_label' => 'Mijn Taxi',
            ],
            'frontend_theme_id' => $theme->id,
        ]);

        Module::create([
            'name' => 'skillmatching',
            'display_name' => 'Skillmatching',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-briefcase',
            'installed' => true,
            'active' => true,
            'configuration' => [
                'dashboard_link_visible' => '0',
            ],
            'frontend_theme_id' => $theme->id,
        ]);

        $theme->update(['active_module_id' => $taxi->id]);

        WebsitePage::create([
            'slug' => 'home',
            'title' => 'Home',
            'content' => '',
            'page_type' => 'home',
            'module_name' => 'skillmatching',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.frontend-themes.staging', [
            'theme_id' => $theme->id,
            'module' => '',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('Mijn Taxi', $response->getContent());
    }
}
