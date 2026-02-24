<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slug uniek per thema: verwijder globale unique op slug,
     * voeg uniekheid toe op (frontend_theme_id, slug).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
            // Eén slug per thema; bij frontend_theme_id IS NULL maximaal één per slug (core-pagina's)
            DB::statement('CREATE UNIQUE INDEX website_pages_slug_unique_null_theme ON website_pages (slug) WHERE frontend_theme_id IS NULL');
            DB::statement('CREATE UNIQUE INDEX website_pages_theme_slug_unique ON website_pages (frontend_theme_id, slug) WHERE frontend_theme_id IS NOT NULL');
        } else {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->unique(['frontend_theme_id', 'slug'], 'website_pages_theme_slug_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS website_pages_slug_unique_null_theme');
            DB::statement('DROP INDEX IF EXISTS website_pages_theme_slug_unique');
            Schema::table('website_pages', function (Blueprint $table) {
                $table->unique('slug', 'website_pages_slug_unique');
            });
        } else {
            Schema::table('website_pages', function (Blueprint $table) {
                $table->dropUnique('website_pages_theme_slug_unique');
                $table->unique('slug');
            });
        }
    }
};
