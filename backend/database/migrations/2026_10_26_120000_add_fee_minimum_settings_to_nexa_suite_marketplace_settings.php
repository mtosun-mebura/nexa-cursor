<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (Schema::hasColumn('nexa_suite_marketplace_settings', 'fee_minimum_settings')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->json('fee_minimum_settings')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'fee_minimum_settings')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->dropColumn('fee_minimum_settings');
        });
    }
};
