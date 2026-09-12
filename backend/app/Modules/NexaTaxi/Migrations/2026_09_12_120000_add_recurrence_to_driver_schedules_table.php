<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_12_120000_add_recurrence_to_driver_schedules_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driver_schedules')) {
            return;
        }

        Schema::table('driver_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_schedules', 'weekdays')) {
                $table->string('weekdays', 32)->nullable();
            }
            if (! Schema::hasColumn('driver_schedules', 'repeat_weekly')) {
                $table->boolean('repeat_weekly')->default(false);
            }
            if (! Schema::hasColumn('driver_schedules', 'repeat_until')) {
                $table->date('repeat_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('driver_schedules')) {
            return;
        }

        Schema::table('driver_schedules', function (Blueprint $table) {
            foreach (['weekdays', 'repeat_weekly', 'repeat_until'] as $column) {
                if (Schema::hasColumn('driver_schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
