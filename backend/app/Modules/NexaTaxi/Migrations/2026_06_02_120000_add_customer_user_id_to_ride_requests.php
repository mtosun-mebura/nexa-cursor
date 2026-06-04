<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koppelt een rit aan een klant-account (users.id op hoofd-DB); geen FK i.v.m. module-database.
 *
 * @see database/migrations/modules/taxi/ (canonical pad voor artisan migrate --database=module_taxi)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ride_requests') && ! Schema::hasColumn('ride_requests', 'customer_user_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_user_id')
                    ->nullable()
                    ->after('customer_email')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ride_requests') && Schema::hasColumn('ride_requests', 'customer_user_id')) {
            Schema::table('ride_requests', function (Blueprint $table) {
                $table->dropColumn('customer_user_id');
            });
        }
    }
};
