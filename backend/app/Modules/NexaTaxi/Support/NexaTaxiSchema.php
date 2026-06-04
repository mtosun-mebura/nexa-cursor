<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class NexaTaxiSchema
{
    /** @var list<string> */
    public const CORE_TABLES = ['vehicles', 'ride_requests', 'default_rates'];

    public static function coreTablesExist(string $connection): bool
    {
        $schema = Schema::connection($connection);
        foreach (self::CORE_TABLES as $table) {
            if (! $schema->hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
