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
            if (! Schema::hasColumn('companies', 'accepts_nexa_suite_bookings')) {
                $table->boolean('accepts_nexa_suite_bookings')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'accepts_nexa_suite_bookings')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('accepts_nexa_suite_bookings');
        });
    }
};
