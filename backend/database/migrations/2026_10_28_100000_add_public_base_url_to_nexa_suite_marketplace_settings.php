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
        if (Schema::hasColumn('nexa_suite_marketplace_settings', 'public_base_url')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->string('public_base_url', 255)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nexa_suite_marketplace_settings')) {
            return;
        }
        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'public_base_url')) {
            return;
        }

        Schema::table('nexa_suite_marketplace_settings', function (Blueprint $table) {
            $table->dropColumn('public_base_url');
        });
    }
};
