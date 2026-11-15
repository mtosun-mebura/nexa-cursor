<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleDummySettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    #[Test]
    public function general_settings_page_contains_dummy_voertuigen_section(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->assertSee('Dummy voertuigen', false)
            ->assertSee('id="dummy-voertuigen"', false)
            ->assertSee('vehicle-dummy-car-yellow.png', false)
            ->assertSee('vehicle-dummy-van-yellow.png', false)
            ->assertSee('vehicle-dummy-bus.png', false)
            ->assertDontSee('vehicle-placeholder.png', false)
            ->assertDontSee('vehicle-placeholder-van.png', false);
    }

    #[Test]
    public function super_admin_can_select_and_upload_dummy(): void
    {
        try {
            GeneralSetting::set('vehicle_dummy_selected', '');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        GeneralSetting::clearRequestCache();

        $admin = $this->superAdmin();

        $select = $this->actingAs($admin)
            ->postJson(route('admin.settings.vehicle-dummies.select'), [
                'type' => 'car',
                'id' => 'builtin-car-black',
            ]);
        $select->assertOk()->assertJsonPath('success', true);

        $upload = $this->actingAs($admin)
            ->postJson(route('admin.settings.vehicle-dummies.upload'), [
                'type' => 'bus',
                'image' => UploadedFile::fake()->image('extra-bus.png', 120, 80),
            ]);
        $upload->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($upload->json('item.id'));
    }
}
