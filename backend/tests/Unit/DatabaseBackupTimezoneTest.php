<?php

namespace Tests\Unit;

use App\Models\DatabaseBackup;
use App\Models\GeneralSetting;
use App\Services\DatabaseBackupSettingsService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DatabaseBackupTimezoneTest extends TestCase
{
    public function test_local_created_at_uses_amsterdam_when_app_timezone_is_utc(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('database_backup.display_timezone', 'Europe/Amsterdam');

        $backup = new DatabaseBackup;
        $backup->created_at = Carbon::parse('2026-08-24 20:21:00', 'UTC');

        $this->assertSame('24-08-2026 22:21', $backup->localCreatedAt()?->format('d-m-Y H:i'));
    }

    public function test_human_size_megabytes_shows_mb_for_kilobyte_files(): void
    {
        $backup = new DatabaseBackup;
        $backup->size_bytes = (int) round(771.5 * 1024);

        $this->assertSame('771.5 KB', $backup->humanSize());
        $this->assertSame('0.75 MB', $backup->humanSizeMegabytes());
    }

    public function test_scheduled_backup_is_due_at_amsterdam_wall_clock_when_app_is_utc(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('database_backup.display_timezone', 'Europe/Amsterdam');

        try {
            GeneralSetting::set('database_backup_enabled', '1', null);
            GeneralSetting::set('database_backup_frequency', 'daily', null);
            GeneralSetting::set('database_backup_time', '09:20', null);
            GeneralSetting::set('database_backup_last_run_at', '', null);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $settings = app(DatabaseBackupSettingsService::class);

        $this->assertFalse($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 07:15:00', 'UTC')));
        $this->assertTrue($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 07:20:00', 'UTC')));
        $this->assertTrue($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 07:22:00', 'UTC')));
        $this->assertFalse($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 07:51:00', 'UTC')));
        $this->assertStringContainsString('binnen een minuut', $settings->nextRunDescription(Carbon::parse('2026-08-25 07:22:00', 'UTC')));
    }

    public function test_later_time_same_day_is_due_after_earlier_scheduled_run(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('database_backup.display_timezone', 'Europe/Amsterdam');

        try {
            GeneralSetting::set('database_backup_enabled', '1', null);
            GeneralSetting::set('database_backup_frequency', 'daily', null);
            GeneralSetting::set('database_backup_time', '10:38', null);
            GeneralSetting::set('database_backup_last_run_at', '2026-08-25T07:23:00+00:00', null);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $settings = app(DatabaseBackupSettingsService::class);

        $this->assertFalse($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 08:30:00', 'UTC')));
        $this->assertTrue($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 08:38:00', 'UTC')));
        $this->assertTrue($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 08:45:00', 'UTC')));
        $this->assertFalse($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 09:20:00', 'UTC')));
        $this->assertStringContainsString('vandaag om 10:38', $settings->nextRunDescription(Carbon::parse('2026-08-25 08:00:00', 'UTC')));
    }

    public function test_same_slot_is_not_due_again_after_that_run(): void
    {
        config()->set('app.timezone', 'UTC');
        config()->set('database_backup.display_timezone', 'Europe/Amsterdam');

        try {
            GeneralSetting::set('database_backup_enabled', '1', null);
            GeneralSetting::set('database_backup_frequency', 'daily', null);
            GeneralSetting::set('database_backup_time', '10:38', null);
            GeneralSetting::set('database_backup_last_run_at', '2026-08-25T08:38:00+00:00', null);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $settings = app(DatabaseBackupSettingsService::class);

        $this->assertFalse($settings->isDueForScheduledRun(Carbon::parse('2026-08-25 08:40:00', 'UTC')));
    }
}
