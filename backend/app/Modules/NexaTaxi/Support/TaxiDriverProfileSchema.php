<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class TaxiDriverProfileSchema
{
    /** @var array<string, bool> */
    private static array $ready = [];

    public static function ensure(string $connection): void
    {
        $key = $connection.':driver_profiles';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('taxi_driver_profiles')) {
            $schema->create('taxi_driver_profiles', function ($table) {
                $table->unsignedBigInteger('driver_id')->primary();
                $table->string('company_logo_path', 512)->nullable();
                $table->string('vehicle_photo_path', 512)->nullable();
                $table->unsignedInteger('rating_count')->default(0);
                $table->unsignedInteger('rating_sum')->default(0);
                $table->decimal('rating_avg', 3, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('taxi_ride_ratings')) {
            $schema->create('taxi_ride_ratings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('ride_request_id')->unique();
                $table->unsignedBigInteger('driver_id')->index();
                $table->unsignedTinyInteger('stars');
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        self::$ready[$key] = true;
    }
}
