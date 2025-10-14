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
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_location')->nullable();
            $table->integer('max_distance')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('work_hours')->nullable();
            $table->integer('min_salary')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_location', 'max_distance', 'contract_type', 'work_hours', 'min_salary']);
        });
    }
};
