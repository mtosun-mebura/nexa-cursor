<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Invoices/payments kunnen aan een match (skillmatching) gekoppeld worden; alleen in skillmatching-DB.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('job_match_id')->nullable()->after('company_id')->constrained('matches')->onDelete('set null');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('job_match_id')->nullable()->after('company_id')->constrained('matches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['job_match_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['job_match_id']);
        });
    }
};
