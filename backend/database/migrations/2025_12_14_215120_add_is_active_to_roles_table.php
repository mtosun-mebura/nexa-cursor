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
        // Use raw SQL with IF NOT EXISTS for PostgreSQL compatibility
        // The after() method doesn't work in PostgreSQL
        if (!Schema::hasColumn('roles', 'is_active')) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE roles ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT true');
            } else {
                Schema::table('roles', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('description');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop column if it exists
        if (Schema::hasColumn('roles', 'is_active')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
