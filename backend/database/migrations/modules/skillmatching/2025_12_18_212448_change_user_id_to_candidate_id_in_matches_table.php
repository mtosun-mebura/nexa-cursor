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
            // Drop the foreign key constraint on user_id
            $table->dropForeign(['user_id']);
            
            // Rename the column from user_id to candidate_id
            $table->renameColumn('user_id', 'candidate_id');
        });
        
        // Add the new foreign key constraint on candidate_id
        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Drop the foreign key constraint on candidate_id
            $table->dropForeign(['candidate_id']);
            
            // Rename the column back from candidate_id to user_id
            $table->renameColumn('candidate_id', 'user_id');
        });
        
        // Add the old foreign key constraint back on user_id
        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
