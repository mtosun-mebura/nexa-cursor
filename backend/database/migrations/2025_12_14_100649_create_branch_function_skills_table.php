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
        Schema::create('branch_function_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_function_id')->constrained('branch_functions')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['branch_function_id', 'name']);
            $table->index(['branch_function_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_function_skills');
    }
};
