<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_10_07_140000_add_return_trip_to_transport_groups.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transport_groups')) {
            Schema::table('transport_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('transport_groups', 'has_return_trip')) {
                    $table->boolean('has_return_trip')->default(false)->after('destination_arrival_time');
                }
                if (! Schema::hasColumn('transport_groups', 'return_pickup_time')) {
                    $table->time('return_pickup_time')->nullable()->after('has_return_trip');
                }
                if (! Schema::hasColumn('transport_groups', 'return_boarding_delay_minutes')) {
                    $table->unsignedSmallInteger('return_boarding_delay_minutes')->default(15)->after('return_pickup_time');
                }
            });
        }

        if (Schema::hasTable('transport_route_templates')
            && ! Schema::hasColumn('transport_route_templates', 'direction')) {
            Schema::table('transport_route_templates', function (Blueprint $table) {
                $table->string('direction', 16)->default('outbound')->index()->after('label');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transport_groups')) {
            Schema::table('transport_groups', function (Blueprint $table) {
                foreach (['return_boarding_delay_minutes', 'return_pickup_time', 'has_return_trip'] as $column) {
                    if (Schema::hasColumn('transport_groups', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('transport_route_templates')
            && Schema::hasColumn('transport_route_templates', 'direction')) {
            Schema::table('transport_route_templates', function (Blueprint $table) {
                $table->dropColumn('direction');
            });
        }
    }
};
