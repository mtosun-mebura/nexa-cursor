<?php

namespace App\Modules\NexaTaxi\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Zorgt dat knowledge_documents/chunks querybaar zijn zonder pgvector op PostgreSQL.
 */
final class TaxiKnowledgeTableService
{
    /**
     * @return bool true als knowledge-tabellen querybaar zijn (of ontbreken); false = sync knowledge overslaan
     */
    public function ensureQueryableOnConnection(string $connection): bool
    {
        if (! Schema::connection($connection)->hasTable('knowledge_documents')) {
            return true;
        }

        if ($this->pgVectorAvailable($connection) && $this->canQueryKnowledgeTables($connection)) {
            return true;
        }

        if ($this->canQueryKnowledgeTables($connection)) {
            return true;
        }

        Log::warning('taxi_knowledge_tables: pgvector ontbreekt op doel; knowledge-tabellen worden opnieuw aangemaakt als tekst.', [
            'connection' => $connection,
        ]);

        $this->repairBrokenPgVectorExtension($connection);

        try {
            $this->recreateAsTextFallback($connection);
        } catch (\Throwable $e) {
            Log::warning('taxi_knowledge_tables: knowledge-tabellen konden niet worden hersteld; sync van AI-kennis wordt overgeslagen.', [
                'connection' => $connection,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $this->canQueryKnowledgeTables($connection)) {
            Log::warning('taxi_knowledge_tables: knowledge-tabellen blijven onquerybaar na fallback.', [
                'connection' => $connection,
            ]);

            return false;
        }

        return true;
    }

    public function canQueryKnowledgeTables(string $connection): bool
    {
        if (! Schema::connection($connection)->hasTable('knowledge_documents')) {
            return true;
        }

        try {
            DB::connection($connection)->table('knowledge_documents')->limit(1)->value('id');

            return true;
        } catch (\Throwable $e) {
            if ($this->isPgVectorUnavailableError($e)) {
                return false;
            }

            throw $e;
        }
    }

    public function pgVectorAvailable(string $connection): bool
    {
        if (DB::connection($connection)->getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            DB::connection($connection)->statement('CREATE EXTENSION IF NOT EXISTS vector');

            $registered = DB::connection($connection)->selectOne(
                "SELECT 1 FROM pg_extension WHERE extname = 'vector'"
            ) !== null;

            if (! $registered) {
                return false;
            }

            DB::connection($connection)->selectOne("SELECT '[1,2,3]'::vector");

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Extensie staat in pg_extension maar shared library ontbreekt (58P01 / $libdir/vector).
     */
    public function repairBrokenPgVectorExtension(string $connection): void
    {
        if (DB::connection($connection)->getDriverName() !== 'pgsql') {
            return;
        }

        if ($this->pgVectorAvailable($connection)) {
            return;
        }

        try {
            $registered = DB::connection($connection)->selectOne(
                "SELECT 1 FROM pg_extension WHERE extname = 'vector'"
            ) !== null;
            if ($registered) {
                DB::connection($connection)->statement('DROP EXTENSION IF EXISTS vector CASCADE');
                Log::warning('taxi_knowledge_tables: kapotte pgvector-extensie verwijderd', [
                    'connection' => $connection,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('taxi_knowledge_tables: kon pgvector-extensie niet repareren', [
                'connection' => $connection,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recreateAsTextFallback(string $connection): void
    {
        $this->dropKnowledgeTables($connection);

        Schema::connection($connection)->create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('content');
            $table->text('category')->nullable();
            $table->text('embedding')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection($connection)->create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->text('chunk_text');
            $table->text('embedding')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function dropKnowledgeTables(string $connection): void
    {
        if (DB::connection($connection)->getDriverName() === 'pgsql') {
            DB::connection($connection)->statement('DROP TABLE IF EXISTS knowledge_chunks CASCADE');
            DB::connection($connection)->statement('DROP TABLE IF EXISTS knowledge_documents CASCADE');

            return;
        }

        Schema::connection($connection)->dropIfExists('knowledge_chunks');
        Schema::connection($connection)->dropIfExists('knowledge_documents');
    }

    private function isPgVectorUnavailableError(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '58P01')
            || str_contains($message, '$libdir/vector')
            || (str_contains($message, 'vector') && str_contains($message, 'No such file'));
    }
}
