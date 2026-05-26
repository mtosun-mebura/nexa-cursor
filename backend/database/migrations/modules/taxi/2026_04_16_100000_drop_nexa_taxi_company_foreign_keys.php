<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * company_id verwijst naar companies op de hoofd-DB (pgsql), niet naar een rij in de module-DB.
 * Een FK naar module.companies faalt daarom bij losse module-databases.
 *
 * @see database/migrations/modules/taxi/ (canonical pad voor artisan migrate --database=module_taxi)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vehicles DROP CONSTRAINT IF EXISTS vehicles_company_id_foreign');
            if (Schema::hasTable('ride_requests')) {
                DB::statement('ALTER TABLE ride_requests DROP CONSTRAINT IF EXISTS ride_requests_company_id_foreign');
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                Schema::table('vehicles', function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                });
            } catch (\Throwable) {
            }
            if (Schema::hasTable('ride_requests')) {
                try {
                    Schema::table('ride_requests', function (Blueprint $table) {
                        $table->dropForeign(['company_id']);
                    });
                } catch (\Throwable) {
                }
            }
        }
    }

    public function down(): void
    {
        // Herstel geen FK: cross-database-integriteit kan niet in PostgreSQL/MySQL.
    }
};
