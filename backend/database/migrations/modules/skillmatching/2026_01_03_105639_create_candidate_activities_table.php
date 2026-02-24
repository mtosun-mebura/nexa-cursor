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
        Schema::create('candidate_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('vacancy_id')->constrained('vacancies')->onDelete('cascade');
            $table->string('action'); // e.g., 'application_created', 'match_created', 'interview_scheduled', 'interview_cancelled', 'interview_reactivated', 'rejected', 'accepted'
            $table->text('title'); // Human-readable title
            $table->text('description')->nullable(); // Optional description
            $table->string('icon')->nullable(); // Icon class name
            $table->string('color')->nullable(); // Color class name
            
            // Optional foreign keys to related entities
            $table->foreignId('match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->foreignId('interview_id')->nullable()->constrained('interviews')->onDelete('set null');
            
            // Metadata as JSON
            $table->json('metadata')->nullable(); // Store additional data like changes, scores, etc.
            
            // User who performed the action
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Timestamp of when the action occurred (can be different from created_at)
            $table->timestamp('action_at')->useCurrent();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['candidate_id', 'vacancy_id', 'action_at']);
            $table->index(['candidate_id', 'vacancy_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_activities');
    }
};
