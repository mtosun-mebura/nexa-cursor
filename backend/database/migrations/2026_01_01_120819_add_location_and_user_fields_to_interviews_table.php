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
        Schema::table('interviews', function (Blueprint $table) {
            $table->foreignId('company_location_id')->nullable()->after('location')->constrained('company_locations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('interviewer_email')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropForeign(['company_location_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['company_location_id', 'user_id']);
        });
    }
};
