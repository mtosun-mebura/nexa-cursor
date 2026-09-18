<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_18_220000_add_evening_night_tariff_to_default_rates.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('default_rates')) {
            return;
        }

        Schema::table('default_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('default_rates', 'evening_night_multiplier')) {
                $table->decimal('evening_night_multiplier', 4, 2)->default(1.20);
            }
            if (! Schema::hasColumn('default_rates', 'evening_night_from_hour')) {
                $table->unsignedTinyInteger('evening_night_from_hour')->default(22);
            }
            if (! Schema::hasColumn('default_rates', 'evening_night_until_hour')) {
                $table->unsignedTinyInteger('evening_night_until_hour')->default(6);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('default_rates')) {
            return;
        }

        Schema::table('default_rates', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('default_rates', 'evening_night_multiplier') ? 'evening_night_multiplier' : null,
                Schema::hasColumn('default_rates', 'evening_night_from_hour') ? 'evening_night_from_hour' : null,
                Schema::hasColumn('default_rates', 'evening_night_until_hour') ? 'evening_night_until_hour' : null,
            ]));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
