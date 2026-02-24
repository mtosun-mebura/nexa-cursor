<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chats kunnen aan candidate/match/application gekoppeld worden; alleen in skillmatching-DB.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('candidate_id')->after('company_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('match_id')->nullable()->after('candidate_id')->constrained('matches')->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->after('match_id')->constrained('applications')->onDelete('cascade');
            $table->index(['user_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropForeign(['match_id']);
            $table->dropForeign(['application_id']);
            $table->dropIndex(['user_id', 'candidate_id']);
        });
    }
};
