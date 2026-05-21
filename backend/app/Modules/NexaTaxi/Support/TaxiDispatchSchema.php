<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class TaxiDispatchSchema
{
    public static function tablesExist(string $connection): bool
    {
        $schema = Schema::connection($connection);

        return $schema->hasTable('driver_availability')
            && $schema->hasTable('ride_dispatch_offers');
    }

    public static function driverAvailabilityExists(string $connection): bool
    {
        return Schema::connection($connection)->hasTable('driver_availability');
    }
}
