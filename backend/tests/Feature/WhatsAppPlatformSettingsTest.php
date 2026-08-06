<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppPlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function platform_settings_page_contains_test_button_and_constrained_fields(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin, 'web')
            ->get(route('admin.settings.general.index'));

        $response->assertOk();
        $response->assertSee('Verbinding testen', false);
        $response->assertSee('/admin/settings/whatsapp/platform/test', false);
        $response->assertSee('max-w-xl', false);
        $response->assertSee('wizard-onboarding-form-table', false);
        $response->assertSee('Boekingstemplate (dispatch)', false);
        $response->assertSee('Boekingsvelden in', false);
        $response->assertSee('Boekingssjablonen', false);
        $response->assertSee('Statussjablonen (universeel)', false);
        $response->assertSee('whatsapp-booking-templates', false);
        $response->assertSee('whatsapp-status-templates', false);
        $response->assertSee('>Verbinding testen</span>', false);
    }
}
