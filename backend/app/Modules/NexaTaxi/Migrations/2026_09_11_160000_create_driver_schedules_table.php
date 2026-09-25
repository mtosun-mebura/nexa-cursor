<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_11_160000_create_driver_schedules_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_schedules')) {
            return;
        }

        Schema::create('driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('driver_id')->index();
            $table->unsignedBigInteger('vehicle_id')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedBigInteger('series_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'starts_at', 'ends_at']);
            $table->index(['driver_id', 'starts_at', 'ends_at']);
            $table->index(['vehicle_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_schedules');
    }
};
