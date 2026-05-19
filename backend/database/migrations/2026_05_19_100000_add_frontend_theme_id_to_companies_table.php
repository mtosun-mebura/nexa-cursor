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
            if (! Schema::hasColumn('companies', 'frontend_theme_id')) {
                $table->foreignId('frontend_theme_id')
                    ->nullable()
                    ->after('building_image')
                    ->constrained('frontend_themes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'frontend_theme_id')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['frontend_theme_id']);
            $table->dropColumn('frontend_theme_id');
        });
    }
};
