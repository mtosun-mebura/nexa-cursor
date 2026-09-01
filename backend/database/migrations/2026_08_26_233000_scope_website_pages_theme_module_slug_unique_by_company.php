<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_pages') || ! Schema::hasColumn('website_pages', 'company_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql' && $driver !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS website_pages_theme_module_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_central_theme_module_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_tenant_theme_module_slug_unique');

        DB::statement(
            "CREATE UNIQUE INDEX website_pages_central_theme_module_slug_unique ON website_pages (frontend_theme_id, COALESCE(module_name, ''), slug) WHERE frontend_theme_id IS NOT NULL AND company_id IS NULL"
        );
        DB::statement(
            "CREATE UNIQUE INDEX website_pages_tenant_theme_module_slug_unique ON website_pages (company_id, frontend_theme_id, COALESCE(module_name, ''), slug) WHERE frontend_theme_id IS NOT NULL AND company_id IS NOT NULL"
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql' && $driver !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS website_pages_central_theme_module_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_tenant_theme_module_slug_unique');
        DB::statement(
            "CREATE UNIQUE INDEX website_pages_theme_module_slug_unique ON website_pages (frontend_theme_id, COALESCE(module_name, ''), slug) WHERE frontend_theme_id IS NOT NULL"
        );
    }
};
