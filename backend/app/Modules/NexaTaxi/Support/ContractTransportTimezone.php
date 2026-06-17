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

    public static function toDriverIso8601(?CarbonInterface $value): ?string
    {
        if (! $value) {
            return null;
        }

        return $value->copy()->timezone(self::TIMEZONE)->toIso8601String();
    }
}
