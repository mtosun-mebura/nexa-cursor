<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NexaSuiteAvatarSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');
    }

    #[Test]
    public function general_settings_page_contains_nexa_suite_avatar_upload(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->assertSee('NEXA Suite-avatar', false)
            ->assertSee('id="nexa-suite-avatar-upload-area"', false)
            ->assertSee(route('admin.settings.upload-nexa-suite-avatar'), false);
    }

    #[Test]
    public function nexa_suite_avatar_url_falls_back_to_default_when_missing(): void
    {
        $this->assertSame(
            asset('assets/media/avatars/300-2.png'),
            GeneralSetting::nexaSuiteAvatarUrl()
        );
    }

    #[Test]
    public function super_admin_can_upload_and_remove_nexa_suite_avatar(): void
    {
        try {
            GeneralSetting::set('nexa_suite_avatar', 'probe');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        GeneralSetting::query()->where('key', 'nexa_suite_avatar')->delete();
        GeneralSetting::clearRequestCache();

        $admin = $this->superAdmin();
        $file = UploadedFile::fake()->image('nexa-suite.png', 80, 80);

        $upload = $this->actingAs($admin)
            ->postJson(route('admin.settings.upload-nexa-suite-avatar'), [
                'nexa_suite_avatar' => $file,
            ]);

        $upload->assertOk()
            ->assertJsonPath('success', true);

        GeneralSetting::clearRequestCache();
        $path = GeneralSetting::get('nexa_suite_avatar');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(Storage::url($path), GeneralSetting::nexaSuiteAvatarUrl());
        $this->assertSame(Storage::url($path), $upload->json('avatar_url'));

        $remove = $this->actingAs($admin)
            ->postJson(route('admin.settings.remove-nexa-suite-avatar'));

        $remove->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('avatar_url', asset('assets/media/avatars/300-2.png'));

        Storage::disk('public')->assertMissing($path);
        GeneralSetting::clearRequestCache();
        $this->assertSame(
            asset('assets/media/avatars/300-2.png'),
            GeneralSetting::nexaSuiteAvatarUrl()
        );
    }

    #[Test]
    public function notification_list_and_drawer_use_uploaded_nexa_suite_avatar(): void
    {
        try {
            GeneralSetting::set('nexa_suite_avatar', 'probe');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $admin = $this->superAdmin();
        $path = UploadedFile::fake()->image('nexa-suite.png', 80, 80)->store('settings', 'public');
        GeneralSetting::set('nexa_suite_avatar', $path);

        Notification::query()->create([
            'user_id' => $admin->id,
            'title' => 'Systeem',
            'message' => 'Avatar-testmelding.',
            'type' => 'info',
        ]);

        $expected = Storage::url($path);

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.list'))
            ->assertOk()
            ->assertJsonPath('0.sender', null)
            ->assertJsonPath('0.system_avatar', $expected);

        Notification::query()->create([
            'user_id' => $admin->id,
            'title' => 'Met afzender',
            'message' => 'Zonder eigen foto.',
            'type' => 'info',
            'data' => json_encode([
                'sender_id' => $admin->id,
                'sender_email' => $admin->email,
            ]),
        ]);

        $withSender = $this->actingAs($admin)
            ->getJson(route('admin.notifications.list'))
            ->assertOk()
            ->json();
        $fromAdmin = collect($withSender)->firstWhere('title', 'Met afzender');
        $this->assertIsArray($fromAdmin);
        $this->assertSame($expected, $fromAdmin['sender']['avatar'] ?? null);
        $this->assertSame($expected, $fromAdmin['system_avatar'] ?? null);

        $this->actingAs($admin)
            ->get(route('admin.settings.general.index'))
            ->assertOk()
            ->assertSee('data-system-avatar="'.$expected.'"', false);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }
}
