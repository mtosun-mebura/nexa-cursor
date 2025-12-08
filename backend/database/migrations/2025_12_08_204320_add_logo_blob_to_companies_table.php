<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to ensure correct column types for PostgreSQL
        if (!Schema::hasColumn('companies', 'logo_blob')) {
            DB::statement('ALTER TABLE companies ADD COLUMN logo_blob TEXT');
        }
        if (!Schema::hasColumn('companies', 'logo_mime_type')) {
            DB::statement('ALTER TABLE companies ADD COLUMN logo_mime_type VARCHAR(255)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'logo_blob')) {
                $table->dropColumn('logo_blob');
            }
            if (Schema::hasColumn('companies', 'logo_mime_type')) {
                $table->dropColumn('logo_mime_type');
            }
        });
    }
};
