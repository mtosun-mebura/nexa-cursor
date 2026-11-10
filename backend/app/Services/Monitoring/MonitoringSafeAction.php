<?php

namespace App\Services\Monitoring;

/**
 * De volledige whitelist van acties die automatisch (ook na AI-advies) mogen draaien.
 * Alles wat hier niet in staat wordt nooit uitgevoerd.
 */
enum MonitoringSafeAction: string
{
    case OptimizeClear = 'optimize_clear';
    case CacheClear = 'cache_clear';
    case ConfigClear = 'config_clear';
    case ViewClear = 'view_clear';
    case ClearSchedulerLocks = 'clear_scheduler_locks';
    case EnsureTaxiSchema = 'ensure_taxi_schema';
    case RetryFailedJob = 'retry_failed_job';
    case ReleaseStuckJobs = 'release_stuck_jobs';

    public function label(): string
    {
        return match ($this) {
            self::OptimizeClear => 'optimize:clear uitgevoerd',
            self::CacheClear => 'cache:clear uitgevoerd',
            self::ConfigClear => 'config:clear uitgevoerd',
            self::ViewClear => 'view:clear uitgevoerd',
            self::ClearSchedulerLocks => 'schedule:clear-cache uitgevoerd (planner-locks gewist)',
            self::EnsureTaxiSchema => 'Taxi-schema gecontroleerd en aangevuld',
            self::RetryFailedJob => 'Mislukte job opnieuw in de wachtrij gezet',
            self::ReleaseStuckJobs => 'Vastgelopen jobs vrijgegeven',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
