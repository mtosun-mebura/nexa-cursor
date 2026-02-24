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
        Schema::table('notifications', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            // This allows location_id = 0 to be used as a special value for main address
            $table->dropForeign(['location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original foreign key constraint
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('set null');
        });
    }
};
