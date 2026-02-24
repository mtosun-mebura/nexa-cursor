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
        Schema::table('matches', function (Blueprint $table) {
            // Rename score to match_score
            $table->renameColumn('score', 'match_score');
            
            // Add new columns
            $table->string('ai_recommendation', 50)->nullable()->after('status');
            $table->date('application_date')->nullable()->after('ai_recommendation');
            $table->text('notes')->nullable()->after('application_date');
            
            // Rename ai_feedback to ai_analysis
            $table->renameColumn('ai_feedback', 'ai_analysis');
            
            // Update status column to allow more values
            $table->string('status', 50)->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('match_score', 'score');
            $table->renameColumn('ai_analysis', 'ai_feedback');
            $table->string('status', 20)->default('matched')->change();
            $table->dropColumn(['ai_recommendation', 'application_date', 'notes']);
        });
    }
};
