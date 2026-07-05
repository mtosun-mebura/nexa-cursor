<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * AI knowledge base voor Nexa Taxi (pgvector op PostgreSQL).
 *
 * @see database/migrations/modules/taxi/
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('knowledge_documents')) {
            return;
        }

        if ($driver === 'pgsql') {
            $this->createPostgresKnowledgeTables($this->pgVectorAvailable());

            return;
        }

        $this->createFallbackKnowledgeTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
    }

    private function pgVectorAvailable(): bool
    {
        return app(\App\Modules\NexaTaxi\Services\TaxiKnowledgeTableService::class)
            ->pgVectorAvailable((string) Schema::getConnection()->getName());
    }

    private function createPostgresKnowledgeTables(bool $useVector): void
    {
        $embeddingColumn = $useVector ? 'vector(1536)' : 'TEXT';

        DB::statement("
            CREATE TABLE knowledge_documents (
                id BIGSERIAL PRIMARY KEY,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                category TEXT,
                embedding {$embeddingColumn},
                created_at TIMESTAMP DEFAULT now()
            )
        ");

        DB::statement("
            CREATE TABLE knowledge_chunks (
                id BIGSERIAL PRIMARY KEY,
                document_id BIGINT REFERENCES knowledge_documents(id) ON DELETE CASCADE,
                chunk_text TEXT NOT NULL,
                embedding {$embeddingColumn},
                created_at TIMESTAMP DEFAULT now()
            )
        ");

        if (! $useVector) {
            return;
        }

        try {
            DB::statement('
                CREATE INDEX knowledge_documents_embedding_idx
                ON knowledge_documents
                USING ivfflat (embedding vector_cosine_ops)
                WITH (lists = 100)
            ');
            DB::statement('
                CREATE INDEX knowledge_chunks_embedding_idx
                ON knowledge_chunks
                USING ivfflat (embedding vector_cosine_ops)
                WITH (lists = 100)
            ');
        } catch (\Throwable $e) {
            Log::warning('nexa_taxi knowledge tables: ivfflat-indexen overgeslagen', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createFallbackKnowledgeTables(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('content');
            $table->text('category')->nullable();
            $table->text('embedding')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->text('chunk_text');
            $table->text('embedding')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
};
