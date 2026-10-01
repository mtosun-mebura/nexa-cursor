<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        if (! Schema::hasColumn('ride_requests', 'customer_track_token')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->string('customer_track_token', 64)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ride_requests')) {
            return;
        }

        if (Schema::hasColumn('ride_requests', 'customer_track_token')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropUnique(['customer_track_token']);
                $table->dropColumn('customer_track_token');
            });
        }
    }
};
