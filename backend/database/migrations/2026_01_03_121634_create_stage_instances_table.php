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
        Schema::create('stage_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('cascade');
            $table->foreignId('match_id')->nullable()->constrained('matches')->onDelete('cascade');
            $table->foreignId('pipeline_template_id')->nullable()->constrained('pipeline_templates')->onDelete('set null');
            $table->string('stage_type_key'); // Reference to stage_types.key
            $table->string('label'); // Custom label for this instance
            $table->integer('sequence'); // Order in pipeline
            $table->enum('status', ['PENDING', 'SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'SKIPPED', 'CANCELED'])->default('PENDING');
            $table->string('outcome')->nullable(); // PASS, FAIL, ON_HOLD, ACCEPTED, DECLINED
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('artifacts')->nullable(); // Store stage-specific data
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['application_id', 'status']);
            $table->index(['match_id', 'status']);
            $table->index(['stage_type_key', 'status']);
            $table->index('sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_instances');
    }
};
