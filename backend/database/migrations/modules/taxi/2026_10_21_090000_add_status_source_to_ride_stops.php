<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_10_21_090000_add_status_source_to_ride_stops.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_stops') || Schema::hasColumn('ride_stops', 'status_source')) {
            return;
        }

        Schema::table('ride_stops', function (Blueprint $table) {
            $table->string('status_source', 16)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_stops') || ! Schema::hasColumn('ride_stops', 'status_source')) {
            return;
        }

        Schema::table('ride_stops', function (Blueprint $table) {
            $table->dropColumn('status_source');
        });
    }
};
