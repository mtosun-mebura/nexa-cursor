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

        DB::statement('DROP INDEX IF EXISTS website_pages_core_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_central_core_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_tenant_core_slug_unique');

        DB::statement(
            'CREATE UNIQUE INDEX website_pages_central_core_slug_unique ON website_pages (slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL AND company_id IS NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX website_pages_tenant_core_slug_unique ON website_pages (company_id, slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL AND company_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql' && $driver !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS website_pages_central_core_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_tenant_core_slug_unique');
        DB::statement(
            'CREATE UNIQUE INDEX website_pages_core_slug_unique ON website_pages (slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL'
        );
    }
};
