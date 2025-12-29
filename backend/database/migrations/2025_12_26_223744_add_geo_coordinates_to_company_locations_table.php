<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('company_locations', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('country');
            }
            if (!Schema::hasColumn('company_locations', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
