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
        if (Schema::hasColumn('nexa_suite_marketplace_settings', 'allow_marketplace_network_owner')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->boolean('allow_marketplace_network_owner')->default(false)->after('fee_percent');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'allow_marketplace_network_owner')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->dropColumn('allow_marketplace_network_owner');
        });
    }
};
