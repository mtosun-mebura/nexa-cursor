<?php

namespace App\Modules\NexaTaxi\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class ContractTransportTimezone
{
    public const TIMEZONE = 'Europe/Amsterdam';

    public static function parseLocalDateTime(string $date, string $time): Carbon
    {
        $normalized = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::parse($date.' '.$normalized, self::TIMEZONE);
    }

    /**
     * Taxi pickup/drop times are stored as Europe/Amsterdam wall-clock in the DB
     * (naive datetime). With APP_TIMEZONE=UTC, Eloquent attaches UTC to that wall
     * clock — re-read the same digits as Amsterdam before comparing or serializing.
     */
    public static function asAmsterdamWall(?CarbonInterface $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $value->format('Y-m-d H:i:s'),
            self::TIMEZONE
        );
    }

    /**
     * Cutoff / query binding that matches naive Amsterdam wall-clock columns when
     * the app timezone is UTC (Eloquent would otherwise shift the instant).
     */
    public static function naiveUtcForWallClockQuery(CarbonInterface $amsterdamWall): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $amsterdamWall->copy()->timezone(self::TIMEZONE)->format('Y-m-d H:i:s'),
            'UTC'
        );
    }

    public static function toDriverIso8601(?CarbonInterface $value): ?string
    {
        if (! $value) {
            return null;
        }

        return self::asAmsterdamWall($value)?->toIso8601String();
    }
}
