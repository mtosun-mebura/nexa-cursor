<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'ride_alert_tone')) {
            return;
        }

        // Alleen nieuwe rijen. Een al gekozen toon blijft staan.
        Schema::table('users', function (Blueprint $table) {
            $table->string('ride_alert_tone', 16)->default('chime')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'ride_alert_tone')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('ride_alert_tone', 16)->default('alert')->change();
        });
    }
};
