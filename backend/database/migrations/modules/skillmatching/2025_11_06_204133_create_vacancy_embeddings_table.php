<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vacancy_embeddings', function (Blueprint $table) {
            $table->foreignId('vacancy_id')->primary()->constrained('vacancies')->cascadeOnDelete();
            $table->string('model')->notNull();
            // Store as JSON for now - can be converted to vector type later when pgvector is installed
            $table->json('embedding')->nullable();
        });

        // Try to enable pgvector extension if using PostgreSQL (gracefully fail if not available)
        if (config('database.default') === 'pgsql') {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
                // Try to convert JSON column to vector type if extension is available
                try {
                    DB::statement('ALTER TABLE vacancy_embeddings DROP COLUMN embedding');
                    DB::statement('ALTER TABLE vacancy_embeddings ADD COLUMN embedding vector(1536)');
                } catch (\Exception $e) {
                    // If conversion fails, keep JSON column
                }
            } catch (\Exception $e) {
                // pgvector extension not available - continue with JSON storage
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancy_embeddings');
    }
};
