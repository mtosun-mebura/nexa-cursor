<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see database/migrations/modules/taxi/2026_09_18_233000_add_company_id_to_default_rates.php
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('default_rates')) {
            return;
        }

        Schema::table('default_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('default_rates', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('default_rates') || ! Schema::hasColumn('default_rates', 'company_id')) {
            return;
        }

        Schema::table('default_rates', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
