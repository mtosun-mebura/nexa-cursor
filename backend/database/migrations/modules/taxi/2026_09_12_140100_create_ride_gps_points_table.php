<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_12_140100_create_ride_gps_points_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ride_gps_points')) {
            return;
        }

        Schema::create('ride_gps_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedBigInteger('driver_id')->nullable()->index();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamp('recorded_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_gps_points');
    }
};
