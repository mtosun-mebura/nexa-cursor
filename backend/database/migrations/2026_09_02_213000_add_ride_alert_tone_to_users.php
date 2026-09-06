<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ride_alert_tone')) {
                $after = Schema::hasColumn('users', 'pwa_accent') ? 'pwa_accent' : 'agenda_color';
                $table->string('ride_alert_tone', 16)->default('classic')->after($after);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ride_alert_tone')) {
                $table->dropColumn('ride_alert_tone');
            }
        });
    }
};
