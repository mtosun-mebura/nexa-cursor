<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_pages')) {
            return;
        }

        if (! Schema::hasColumn('website_pages', 'site_brand')) {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->string('site_brand', 32)->nullable()->after('company_id');
            });
        }

        // Bestaande centrale pagina's = NEXA Suite-merk.
        if (Schema::hasColumn('website_pages', 'company_id')) {
            DB::table('website_pages')
                ->whereNull('company_id')
                ->whereNull('site_brand')
                ->update(['site_brand' => 'nexasuite']);
        } else {
            DB::table('website_pages')
                ->whereNull('site_brand')
                ->update(['site_brand' => 'nexasuite']);
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql' && $driver !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS website_pages_central_core_slug_unique');
        DB::statement('DROP INDEX IF EXISTS website_pages_central_theme_module_slug_unique');

        DB::statement(
            'CREATE UNIQUE INDEX website_pages_central_core_slug_unique ON website_pages (site_brand, slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL AND company_id IS NULL'
        );
        DB::statement(
            "CREATE UNIQUE INDEX website_pages_central_theme_module_slug_unique ON website_pages (site_brand, frontend_theme_id, COALESCE(module_name, ''), slug) WHERE frontend_theme_id IS NOT NULL AND company_id IS NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_pages')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql' || $driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS website_pages_central_core_slug_unique');
            DB::statement('DROP INDEX IF EXISTS website_pages_central_theme_module_slug_unique');

            DB::statement(
                'CREATE UNIQUE INDEX website_pages_central_core_slug_unique ON website_pages (slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL AND company_id IS NULL'
            );
            DB::statement(
                "CREATE UNIQUE INDEX website_pages_central_theme_module_slug_unique ON website_pages (frontend_theme_id, COALESCE(module_name, ''), slug) WHERE frontend_theme_id IS NOT NULL AND company_id IS NULL"
            );
        }

        if (Schema::hasColumn('website_pages', 'site_brand')) {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropColumn('site_brand');
            });
        }
    }
};
