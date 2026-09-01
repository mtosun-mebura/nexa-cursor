<?php

namespace Tests\Feature;

use App\Models\DatabaseBackup;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseBackupSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }

    public function test_super_admin_can_save_database_backup_settings(): void
    {
        try {
            GeneralSetting::set('database_backup_enabled', '0');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('admin.settings.database-backups.update'), [
            'database_backup_enabled' => '1',
            'database_backup_frequency' => 'weekly',
            'database_backup_time' => '04:30',
            'database_backup_retention_days' => '14',
            'database_backup_sync_target_id' => '0',
        ]);

        $response->assertRedirect(route('admin.settings.index').'?saved=1#database-backups');

        $this->assertSame('1', GeneralSetting::get('database_backup_enabled', '0'));
        $this->assertSame('weekly', GeneralSetting::get('database_backup_frequency', ''));
        $this->assertSame('04:30', GeneralSetting::get('database_backup_time', ''));
        $this->assertSame('14', GeneralSetting::get('database_backup_retention_days', ''));
    }

    public function test_super_admin_can_save_time_with_seconds_from_browser(): void
    {
        try {
            GeneralSetting::set('database_backup_enabled', '0');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.settings.database-backups.update'), [
            'database_backup_enabled' => '1',
            'database_backup_frequency' => 'daily',
            'database_backup_time' => '09:20:00',
            'database_backup_retention_days' => '30',
            'database_backup_sync_target_id' => '0',
        ])->assertRedirect(route('admin.settings.index').'?saved=1#database-backups');

        $this->assertSame('09:20', GeneralSetting::get('database_backup_time', ''));
    }

    public function test_super_admin_can_create_sqlite_backup(): void
    {
        if (config('database.connections.sqlite.database') === ':memory:') {
            $this->markTestSkipped('SQLite :memory: kan niet worden geback-upt in tests.');
        }

        Storage::fake('local');
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('admin.settings.database-backups.run'));

        $response->assertRedirect();
        $this->assertSame(1, DatabaseBackup::query()->count());
        $backup = DatabaseBackup::query()->first();
        $this->assertSame(DatabaseBackup::STATUS_COMPLETED, $backup->status);
        Storage::disk('local')->assertExists($backup->disk_path);
    }

    public function test_settings_page_shows_database_backups_section(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Database backups', false)
            ->assertSee('Nederlandse tijd', false)
            ->assertSee('Lijst vernieuwen', false);
    }

    public function test_super_admin_can_fetch_backup_table_fragment(): void
    {
        $admin = $this->superAdmin();

        DatabaseBackup::query()->create([
            'filename' => 'nexa-backup-refresh-test.dump',
            'disk_path' => 'database-backups/nexa-backup-refresh-test.dump',
            'connection' => 'pgsql',
            'database_name' => 'nexa',
            'size_bytes' => 1024,
            'status' => DatabaseBackup::STATUS_COMPLETED,
            'trigger' => DatabaseBackup::TRIGGER_MANUAL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.database-backups.table'))
            ->assertOk()
            ->assertSee('nexa-backup-refresh-test.dump', false)
            ->assertSee('id="database-backups-table"', false)
            ->assertSee('database-backups-select-all', false)
            ->assertSee('database-backup-checkbox', false)
            ->assertDontSee('Beschikbare backups', false);
    }

    public function test_backup_table_fragment_shows_pending_status(): void
    {
        $admin = $this->superAdmin();

        DatabaseBackup::query()->create([
            'filename' => 'nexa-backup-pending-test.dump',
            'disk_path' => 'database-backups/nexa-backup-pending-test.dump',
            'connection' => 'pgsql',
            'database_name' => 'nexa',
            'size_bytes' => 0,
            'status' => DatabaseBackup::STATUS_PENDING,
            'trigger' => DatabaseBackup::TRIGGER_MANUAL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.database-backups.table'))
            ->assertOk()
            ->assertSee('data-status="pending"', false)
            ->assertSee('Bezig', false)
            ->assertSee('Status', false);
    }

    public function test_guest_cannot_fetch_backup_table_fragment(): void
    {
        $this->get(route('admin.settings.database-backups.table'))
            ->assertRedirect();
    }

    public function test_ajax_run_backup_returns_json(): void
    {
        if (config('database.connections.sqlite.database') === ':memory:') {
            $this->markTestSkipped('SQLite :memory: kan niet worden geback-upt in tests.');
        }

        Storage::fake('local');
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.settings.database-backups.run'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'message', 'filename']);

        $this->assertSame(1, DatabaseBackup::query()->count());
    }

    public function test_super_admin_can_bulk_delete_backups(): void
    {
        Storage::fake('local');
        $admin = $this->superAdmin();

        $keep = DatabaseBackup::query()->create([
            'filename' => 'nexa-backup-keep.dump',
            'disk_path' => 'database-backups/nexa-backup-keep.dump',
            'connection' => 'pgsql',
            'database_name' => 'nexa',
            'size_bytes' => 10,
            'status' => DatabaseBackup::STATUS_COMPLETED,
            'trigger' => DatabaseBackup::TRIGGER_MANUAL,
        ]);
        $first = DatabaseBackup::query()->create([
            'filename' => 'nexa-backup-delete-a.dump',
            'disk_path' => 'database-backups/nexa-backup-delete-a.dump',
            'connection' => 'pgsql',
            'database_name' => 'nexa',
            'size_bytes' => 10,
            'status' => DatabaseBackup::STATUS_COMPLETED,
            'trigger' => DatabaseBackup::TRIGGER_MANUAL,
        ]);
        $second = DatabaseBackup::query()->create([
            'filename' => 'nexa-backup-delete-b.dump',
            'disk_path' => 'database-backups/nexa-backup-delete-b.dump',
            'connection' => 'pgsql',
            'database_name' => 'nexa',
            'size_bytes' => 10,
            'status' => DatabaseBackup::STATUS_COMPLETED,
            'trigger' => DatabaseBackup::TRIGGER_MANUAL,
        ]);
        $pending = DatabaseBackup::query()->create([
            'filename' => 'nexa-backup-pending-skip.dump',
            'disk_path' => 'database-backups/nexa-backup-pending-skip.dump',
            'connection' => 'pgsql',
            'database_name' => 'nexa',
            'size_bytes' => 0,
            'status' => DatabaseBackup::STATUS_PENDING,
            'trigger' => DatabaseBackup::TRIGGER_MANUAL,
        ]);

        Storage::disk('local')->put($keep->disk_path, 'keep');
        Storage::disk('local')->put($first->disk_path, 'a');
        Storage::disk('local')->put($second->disk_path, 'b');

        $this->actingAs($admin)
            ->postJson(route('admin.settings.database-backups.bulk-delete'), [
                'ids' => [$first->id, $second->id, $pending->id],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('deleted', 2)
            ->assertJsonPath('skipped', 1);

        $this->assertSame(2, DatabaseBackup::query()->count());
        $this->assertNotNull(DatabaseBackup::query()->find($keep->id));
        $this->assertNotNull(DatabaseBackup::query()->find($pending->id));
        Storage::disk('local')->assertMissing($first->disk_path);
        Storage::disk('local')->assertMissing($second->disk_path);
        Storage::disk('local')->assertExists($keep->disk_path);
    }

    public function test_guest_cannot_bulk_delete_backups(): void
    {
        $this->postJson(route('admin.settings.database-backups.bulk-delete'), ['ids' => [1]])
            ->assertUnauthorized();
    }
}
