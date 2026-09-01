<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\TenantSyncTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class DatabaseBackupSettingsService
{
    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public function formSettings(): array
    {
        $activeTarget = app(TenantSyncSettingsService::class)->activeTarget();

        return [
            'database_backup_enabled' => $this->isEnabled(),
            'database_backup_frequency' => $this->frequency(),
            'database_backup_time' => $this->time(),
            'database_backup_retention_days' => $this->retentionDays(),
            'database_backup_sync_target_id' => $this->syncTargetId() ?? ($activeTarget?->id ?? 0),
            'database_backup_last_run_at' => GeneralSetting::get('database_backup_last_run_at', ''),
            'database_backup_next_run_hint' => $this->nextRunDescription(),
        ];
    }

    public function isEnabled(): bool
    {
        return GeneralSetting::get('database_backup_enabled', '0') === '1';
    }

    public function frequency(): string
    {
        $value = strtolower(trim((string) GeneralSetting::get('database_backup_frequency', config('database_backup.default_frequency', 'daily'))));

        return in_array($value, [self::FREQUENCY_DAILY, self::FREQUENCY_WEEKLY], true)
            ? $value
            : self::FREQUENCY_DAILY;
    }

    public function time(): string
    {
        $raw = trim((string) GeneralSetting::get('database_backup_time', config('database_backup.default_time', '03:00')));
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $raw, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return '03:00';
    }

    public function retentionDays(): int
    {
        return max(1, min(3650, (int) GeneralSetting::get(
            'database_backup_retention_days',
            (string) config('database_backup.default_retention_days', 30)
        )));
    }

    public function syncTargetId(): ?int
    {
        $id = (int) GeneralSetting::get('database_backup_sync_target_id', '0');

        return $id > 0 ? $id : null;
    }

    public function syncTarget(): ?TenantSyncTarget
    {
        $id = $this->syncTargetId();

        return $id ? app(TenantSyncSettingsService::class)->findTarget($id) : null;
    }

    public function saveFromRequest(Request $request): void
    {
        GeneralSetting::set('database_backup_enabled', $request->boolean('database_backup_enabled') ? '1' : '0', null);
        GeneralSetting::set('database_backup_frequency', $request->input('database_backup_frequency', self::FREQUENCY_DAILY), null);
        GeneralSetting::set('database_backup_time', $this->normalizeTimeInput((string) $request->input('database_backup_time', '03:00')), null);
        GeneralSetting::set('database_backup_retention_days', (string) max(1, min(3650, (int) $request->input('database_backup_retention_days', 30))), null);

        $targetId = (int) $request->input('database_backup_sync_target_id', 0);
        GeneralSetting::set('database_backup_sync_target_id', $targetId > 0 ? (string) $targetId : '', null);
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        return [
            'database_backup_enabled' => ['nullable', 'boolean'],
            'database_backup_frequency' => ['required', 'in:'.self::FREQUENCY_DAILY.','.self::FREQUENCY_WEEKLY],
            'database_backup_time' => ['required', 'regex:/^([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/'],
            'database_backup_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'database_backup_sync_target_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function markScheduledRunCompleted(): void
    {
        GeneralSetting::set('database_backup_last_run_at', now()->toIso8601String(), null);
    }

    public function isDueForScheduledRun(?Carbon $now = null): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $now = $this->nowInDisplayTimezone($now);
        [$hour, $minute] = array_map('intval', explode(':', $this->time()));
        $scheduledToday = $now->copy()->setTime($hour, $minute, 0);

        if ($this->frequency() === self::FREQUENCY_WEEKLY && $now->dayOfWeekIso !== 1) {
            return false;
        }

        if ($now->lt($scheduledToday) || $now->gt($scheduledToday->copy()->addMinutes(30))) {
            return false;
        }

        $lastRunRaw = trim((string) GeneralSetting::get('database_backup_last_run_at', ''));
        if ($lastRunRaw === '') {
            return true;
        }

        try {
            $lastRun = Carbon::parse($lastRunRaw)->timezone($now->timezoneName);
        } catch (\Throwable) {
            return true;
        }

        // Een eerdere dump vandaag (bijv. 09:23) mag een later tijdstip dezelfde dag (10:38) niet blokkeren.
        return $lastRun->lt($scheduledToday);
    }

    public function nextRunDescription(?Carbon $now = null): string
    {
        if (! $this->isEnabled()) {
            return 'Automatische backups staan uit.';
        }

        $now = $this->nowInDisplayTimezone($now);
        if ($this->isDueForScheduledRun($now)) {
            return 'Backup start binnen een minuut (Nederlandse tijd).';
        }

        [$hour, $minute] = array_map('intval', explode(':', $this->time()));

        if ($this->frequency() === self::FREQUENCY_WEEKLY) {
            $next = $now->copy()->startOfWeek()->setTime($hour, $minute, 0);
            if ($now->gte($next)) {
                $next->addWeek();
            }

            return 'Volgende backup '.$next->locale('nl')->isoFormat('dddd D MMMM [om] HH:mm').' (Nederlandse tijd).';
        }

        $today = $now->copy()->setTime($hour, $minute, 0);
        if ($now->lt($today)) {
            return 'Volgende backup vandaag om '.$this->time().' (Nederlandse tijd).';
        }

        return 'Volgende backup morgen om '.$this->time().' (Nederlandse tijd).';
    }

    private function displayTimezone(): string
    {
        $timezone = (string) config('database_backup.display_timezone', 'Europe/Amsterdam');

        return $timezone !== '' ? $timezone : 'Europe/Amsterdam';
    }

    private function nowInDisplayTimezone(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->timezone($this->displayTimezone());
    }

    private function normalizeTimeInput(string $value): string
    {
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', trim($value), $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return '03:00';
    }
}
