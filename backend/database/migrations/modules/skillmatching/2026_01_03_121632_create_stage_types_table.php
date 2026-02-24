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
        Schema::create('stage_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., "INTAKE", "TEAM_INTERVIEW"
            $table->string('default_label'); // e.g., "Intake gesprek"
            $table->string('category')->default('interview'); // interview, offer, check, etc.
            $table->integer('typical_duration_minutes')->nullable();
            $table->boolean('can_schedule')->default(true);
            $table->boolean('can_collect_feedback')->default(true);
            $table->json('required_artifacts')->nullable(); // ["interviewers", "scorecard"]
            $table->json('optional_artifacts')->nullable(); // ["notes", "rating"]
            $table->json('outcomes')->nullable(); // ["PASS", "FAIL", "ON_HOLD"]
            $table->json('allowed_next_stage_types')->nullable(); // ["TEAM_INTERVIEW", "REJECTION"]
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_types');
    }
};
