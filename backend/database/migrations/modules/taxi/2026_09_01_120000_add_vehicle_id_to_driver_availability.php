<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_01_120000_add_vehicle_id_to_driver_availability.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver_availability')) {
            return;
        }

        if (Schema::hasColumn('driver_availability', 'vehicle_id')) {
            return;
        }

        Schema::table('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->nullable()->after('company_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('driver_availability')) {
            return;
        }

        if (! Schema::hasColumn('driver_availability', 'vehicle_id')) {
            return;
        }

        Schema::table('driver_availability', function (Blueprint $table) {
            $table->dropColumn('vehicle_id');
        });
    }
};
