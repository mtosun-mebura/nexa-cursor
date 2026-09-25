<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class TaxiRideTrackSchema
{
    public static function ensure(string $connection): void
    {
        self::ensureRideColumns($connection);
        self::ensurePointsTable($connection);
    }

    public static function ensureRideColumns(string $connection): void
    {
        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_requests')) {
            return;
        }

        $columns = [
            'trip_started_at' => fn (Blueprint $table) => $table->dateTime('trip_started_at')->nullable(),
            'trip_completed_at' => fn (Blueprint $table) => $table->dateTime('trip_completed_at')->nullable(),
            'track_polyline' => fn (Blueprint $table) => $table->text('track_polyline')->nullable(),
            'actual_distance_meters' => fn (Blueprint $table) => $table->unsignedInteger('actual_distance_meters')->nullable(),
            'actual_duration_seconds' => fn (Blueprint $table) => $table->unsignedInteger('actual_duration_seconds')->nullable(),
        ];

        foreach ($columns as $name => $add) {
            if ($schema->hasColumn('ride_requests', $name)) {
                continue;
            }
            $schema->table('ride_requests', $add);
        }
    }

    public static function ensurePointsTable(string $connection): void
    {
        if (Schema::connection($connection)->hasTable('ride_gps_points')) {
            return;
        }

        Schema::connection($connection)->create('ride_gps_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedBigInteger('driver_id')->nullable()->index();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamp('recorded_at')->index();
        });
    }

    public static function pointsTableExists(string $connection): bool
    {
        return Schema::connection($connection)->hasTable('ride_gps_points');
    }
}
