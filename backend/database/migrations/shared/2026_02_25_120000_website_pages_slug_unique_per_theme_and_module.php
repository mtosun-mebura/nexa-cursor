<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slug uniek per thema én per module: (frontend_theme_id, module_name, slug).
     * Vervangt de bestaande index (frontend_theme_id, slug) zodat dezelfde slug
     * in verschillende modules binnen hetzelfde thema toegestaan is.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS website_pages_slug_unique_null_theme');
            DB::statement('DROP INDEX IF EXISTS website_pages_theme_slug_unique');
            // Core-pagina's: slug uniek waar theme en module beide null
            DB::statement('CREATE UNIQUE INDEX website_pages_core_slug_unique ON website_pages (slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL');
            // Thema + module: (theme_id, module_name, slug); COALESCE zodat NULL module_name één waarde is
            DB::statement('CREATE UNIQUE INDEX website_pages_theme_module_slug_unique ON website_pages (frontend_theme_id, COALESCE(module_name, \'\'), slug) WHERE frontend_theme_id IS NOT NULL');
        } else {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropUnique('website_pages_theme_slug_unique');
            });
            // MySQL: gegenereerde kolommen zodat NULL (theme/module) één waarde wordt in de unique index
            DB::statement("ALTER TABLE website_pages ADD COLUMN frontend_theme_id_for_unique BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(frontend_theme_id, 0)) STORED");
            DB::statement("ALTER TABLE website_pages ADD COLUMN module_name_for_unique VARCHAR(255) GENERATED ALWAYS AS (COALESCE(module_name, '')) STORED");
            Schema::table('website_pages', function (Blueprint $table) {
                $table->unique(['frontend_theme_id_for_unique', 'module_name_for_unique', 'slug'], 'website_pages_theme_module_slug_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS website_pages_core_slug_unique');
            DB::statement('DROP INDEX IF EXISTS website_pages_theme_module_slug_unique');
            DB::statement('CREATE UNIQUE INDEX website_pages_slug_unique_null_theme ON website_pages (slug) WHERE frontend_theme_id IS NULL');
            DB::statement('CREATE UNIQUE INDEX website_pages_theme_slug_unique ON website_pages (frontend_theme_id, slug) WHERE frontend_theme_id IS NOT NULL');
        } else {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropUnique('website_pages_theme_module_slug_unique');
            });
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropColumn(['frontend_theme_id_for_unique', 'module_name_for_unique']);
            });
            Schema::table('website_pages', function (Blueprint $table) {
                $table->unique(['frontend_theme_id', 'slug'], 'website_pages_theme_slug_unique');
            });
        }
    }
};
