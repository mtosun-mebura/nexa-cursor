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
        Schema::create('candidate_texts', function (Blueprint $table) {
            $table->foreignId('candidate_id')->primary()->constrained('candidates')->cascadeOnDelete();
            $table->text('last_responsibilities')->nullable(); // Q14
            $table->json('top_skills')->nullable(); // Q15
            $table->json('tools_tech')->nullable(); // Q16
            $table->text('employer_values')->nullable(); // Q21
            $table->text('best_result')->nullable(); // Q22
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_texts');
    }
};
