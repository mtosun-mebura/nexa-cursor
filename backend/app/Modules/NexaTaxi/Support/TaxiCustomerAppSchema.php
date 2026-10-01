<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class TaxiCustomerAppSchema
{
    /** @var array<string, bool> */
    private static array $ready = [];

    public static function ensureTrackTokenColumn(string $connection): void
    {
        $key = $connection.':customer_track_token';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_requests')) {
            return;
        }

        if (! $schema->hasColumn('ride_requests', 'customer_track_token')) {
            $schema->table('ride_requests', function ($table) {
                $table->string('customer_track_token', 64)->nullable()->unique();
            });
        }

        self::$ready[$key] = true;
    }
}
