<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapsPlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function general_settings_page_contains_maps_configuration(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin, 'web')
            ->get(route('admin.settings.general.index'));

        $response->assertOk();
        $response->assertSee('Google Maps Configuratie', false);
        $response->assertSee('id="maps"', false);
        $response->assertSee(route('admin.settings.maps.update'), false);
        $response->assertSee('GOOGLE_MAPS_API_KEY', false);
        $response->assertSee('PDOK Locatieserver', false);
    }

    #[Test]
    public function super_admin_can_save_platform_maps_settings_without_tenant(): void
    {
        try {
            GeneralSetting::set('GOOGLE_MAPS_API_KEY', 'probe');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        GeneralSetting::query()->where('key', 'GOOGLE_MAPS_API_KEY')->delete();
        GeneralSetting::clearRequestCache();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin, 'web')
            ->post(route('admin.settings.maps.update'), [
                'GOOGLE_MAPS_API_KEY' => 'PLATFORM_MAPS_KEY',
                'GOOGLE_MAPS_MAP_ID' => 'map-123',
                'GOOGLE_MAPS_ZOOM' => 14,
                'GOOGLE_MAPS_CENTER_LAT' => '52.1',
                'GOOGLE_MAPS_CENTER_LNG' => '5.1',
                'GOOGLE_MAPS_TYPE' => 'satellite',
            ]);

        $response->assertRedirect(route('admin.settings.general.index').'#maps');
        $response->assertSessionHas('success');
        GeneralSetting::clearRequestCache();
        $this->assertSame('PLATFORM_MAPS_KEY', GeneralSetting::get('GOOGLE_MAPS_API_KEY'));
        $this->assertSame('map-123', GeneralSetting::get('GOOGLE_MAPS_MAP_ID'));
        $this->assertSame('14', GeneralSetting::get('GOOGLE_MAPS_ZOOM'));
        $this->assertSame('52.1', GeneralSetting::get('GOOGLE_MAPS_CENTER_LAT'));
        $this->assertSame('5.1', GeneralSetting::get('GOOGLE_MAPS_CENTER_LNG'));
        $this->assertSame('satellite', GeneralSetting::get('GOOGLE_MAPS_TYPE'));
    }

    #[Test]
    public function super_admin_can_toggle_pdok_postcode_fallback(): void
    {
        try {
            GeneralSetting::set('GOOGLE_MAPS_API_KEY', 'probe');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin, 'web')
            ->post(route('admin.settings.maps.update'), [
                'GOOGLE_MAPS_API_KEY' => 'PLATFORM_MAPS_KEY',
                'GOOGLE_MAPS_MAP_ID' => '',
                'GOOGLE_MAPS_ZOOM' => 12,
                'GOOGLE_MAPS_CENTER_LAT' => '52.3676',
                'GOOGLE_MAPS_CENTER_LNG' => '4.9041',
                'GOOGLE_MAPS_TYPE' => 'roadmap',
                'POSTCODE_PDOK_FALLBACK' => '0',
            ])
            ->assertRedirect(route('admin.settings.general.index').'#maps');

        GeneralSetting::clearRequestCache();
        $this->assertSame('0', GeneralSetting::get('POSTCODE_PDOK_FALLBACK'));

        $this->actingAs($admin, 'web')
            ->post(route('admin.settings.maps.update'), [
                'GOOGLE_MAPS_API_KEY' => 'PLATFORM_MAPS_KEY',
                'GOOGLE_MAPS_MAP_ID' => '',
                'GOOGLE_MAPS_ZOOM' => 12,
                'GOOGLE_MAPS_CENTER_LAT' => '52.3676',
                'GOOGLE_MAPS_CENTER_LNG' => '4.9041',
                'GOOGLE_MAPS_TYPE' => 'roadmap',
                'POSTCODE_PDOK_FALLBACK' => '1',
            ])
            ->assertRedirect();

        GeneralSetting::clearRequestCache();
        $this->assertSame('1', GeneralSetting::get('POSTCODE_PDOK_FALLBACK'));
    }

    #[Test]
    public function tenant_settings_page_does_not_contain_maps_configuration(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin, 'web')
            ->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertDontSee('id="maps"', false);
        $response->assertDontSee('Maps Instellingen Opslaan', false);
    }
}
