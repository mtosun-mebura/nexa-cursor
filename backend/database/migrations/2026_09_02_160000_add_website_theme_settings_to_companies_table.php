<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'website_theme_settings')) {
                $table->json('website_theme_settings')->nullable()->after('frontend_theme_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'website_theme_settings')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('website_theme_settings');
        });
    }
};
