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
            // Add new columns
            $table->string('type', 50)->nullable()->after('match_id');
            $table->integer('duration')->nullable()->after('scheduled_at');
            $table->string('status', 50)->default('scheduled')->after('duration');
            $table->string('interviewer_name', 255)->nullable()->after('location');
            $table->string('interviewer_email', 255)->nullable()->after('interviewer_name');
            $table->text('feedback')->nullable()->after('notes');
            
            // Drop video_chat_url as it's replaced by location
            $table->dropColumn('video_chat_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            // Revert changes
            $table->string('video_chat_url', 255)->nullable()->after('location');
            $table->dropColumn(['type', 'duration', 'status', 'interviewer_name', 'interviewer_email', 'feedback']);
        });
    }
};
