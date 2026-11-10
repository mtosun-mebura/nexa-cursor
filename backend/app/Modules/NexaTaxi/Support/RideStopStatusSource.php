<?php

namespace App\Modules\NexaTaxi\Support;

use App\Modules\NexaTaxi\Models\RideStop;
use Illuminate\Support\Facades\Schema;

/**
 * Herkomst van een ritstop-status. 'auto' = gezet zonder tik van de chauffeur.
 */
final class RideStopStatusSource
{
    public const AUTO = 'auto';

    public static function columnExists(string $conn): bool
    {
        return Schema::connection($conn)->hasTable('ride_stops')
            && Schema::connection($conn)->hasColumn('ride_stops', 'status_source');
    }

    /**
     * @return array{status_source: string}
     */
    public static function autoAttributes(string $conn): array
    {
        if (! self::columnExists($conn)) {
            return [];
        }

        return ['status_source' => self::AUTO];
    }

    /**
     * @return array{status_source: null}
     */
    public static function clearedAttributes(string $conn): array
    {
        if (! self::columnExists($conn)) {
            return [];
        }

        return ['status_source' => null];
    }

    public static function isAuto(?RideStop $stop): bool
    {
        if ($stop === null) {
            return false;
        }

        return (string) ($stop->getAttributes()['status_source'] ?? '') === self::AUTO;
    }
}
