<?php

namespace Tests\Feature;

use App\Models\TenantSyncTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantSyncTargetPickerTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_settings_shows_form_when_no_targets_exist(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('id="tenant-sync-settings-form"', false);
        $response->assertSee('id="tenant-sync-new-env-btn"', false);
        $this->assertDoesNotMatchRegularExpression(
            '/id="tenant-sync-settings-form-wrap"[^>]*\bhidden\b/',
            $response->getContent()
        );
    }

    public function test_settings_hides_form_and_shows_target_cards_when_targets_exist(): void
    {
        $admin = $this->superAdmin();
        $target = TenantSyncTarget::query()->create([
            'name' => 'NEXA PROD',
            'ssh_enabled' => true,
            'ssh_port' => 22,
            'remote_db_host' => '127.0.0.1',
            'remote_db_port' => 5432,
            'push_enabled' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('NEXA PROD', false);
        $response->assertSee('Actief sync-doel', false);
        $response->assertSee('tenant-sync-edit-btn', false);
        $response->assertSee('Omgeving verwijderen', false);
        $response->assertSee('id="tenant-sync-form-cancel"', false);
        $this->assertMatchesRegularExpression(
            '/id="tenant-sync-settings-form-wrap"[^>]*\bhidden\b/',
            $response->getContent()
        );
        $this->assertStringContainsString('data-target-id="'.$target->id.'"', $response->getContent());
        $this->assertMatchesRegularExpression(
            '/id="tenant-sync-empty-form-json">\{[^}]*"tenant_sync_target_id":0/',
            $response->getContent()
        );
    }

    public function test_super_admin_can_activate_target_via_ajax_without_redirect(): void
    {
        $admin = $this->superAdmin();
        $active = TenantSyncTarget::query()->create([
            'name' => 'NEXA PROD',
            'ssh_enabled' => true,
            'ssh_port' => 22,
            'remote_db_host' => '127.0.0.1',
            'remote_db_port' => 5432,
            'push_enabled' => true,
            'is_active' => true,
        ]);
        $local = TenantSyncTarget::query()->create([
            'name' => 'Lokaal',
            'ssh_enabled' => false,
            'ssh_port' => 22,
            'remote_db_host' => '127.0.0.1',
            'remote_db_port' => 5432,
            'push_enabled' => false,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.settings.tenant-sync.target.activate'), [
            'tenant_sync_target_id' => $local->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'id' => $local->id,
            'name' => 'Lokaal',
        ]);

        $this->assertTrue($local->fresh()->is_active);
        $this->assertFalse($active->fresh()->is_active);
    }
}
