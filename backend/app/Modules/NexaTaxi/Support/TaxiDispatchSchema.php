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

    public static function ensureOfferDeclineReasonColumn(string $connection): void
    {
        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_dispatch_offers')) {
            return;
        }

        if (! $schema->hasColumn('ride_dispatch_offers', 'decline_reason')) {
            $schema->table('ride_dispatch_offers', function ($table) {
                $table->string('decline_reason', 500)->nullable();
            });
        }
    }
}
