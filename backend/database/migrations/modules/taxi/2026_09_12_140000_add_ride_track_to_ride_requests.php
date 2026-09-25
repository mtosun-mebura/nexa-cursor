<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_12_140000_add_ride_track_to_ride_requests.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('ride_requests', 'trip_started_at')) {
                $table->dateTime('trip_started_at')->nullable();
            }
            if (! Schema::hasColumn('ride_requests', 'trip_completed_at')) {
                $table->dateTime('trip_completed_at')->nullable();
            }
            if (! Schema::hasColumn('ride_requests', 'track_polyline')) {
                $table->text('track_polyline')->nullable();
            }
            if (! Schema::hasColumn('ride_requests', 'actual_distance_meters')) {
                $table->unsignedInteger('actual_distance_meters')->nullable();
            }
            if (! Schema::hasColumn('ride_requests', 'actual_duration_seconds')) {
                $table->unsignedInteger('actual_duration_seconds')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        Schema::table('ride_requests', function (Blueprint $table) {
            foreach (['trip_started_at', 'trip_completed_at', 'track_polyline', 'actual_distance_meters', 'actual_duration_seconds'] as $column) {
                if (Schema::hasColumn('ride_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
