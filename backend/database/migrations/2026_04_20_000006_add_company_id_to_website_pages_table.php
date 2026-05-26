<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('website_pages', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('frontend_theme_id')
                    ->constrained('companies')
                    ->nullOnDelete();
                $table->index(['company_id', 'is_active']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('website_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('website_pages', 'company_id')) {
                return;
            }
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
