<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TaxiKnowledgeTableService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiKnowledgeTableServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function recreate_as_text_fallback_makes_knowledge_tables_queryable(): void
    {
        $connection = config('database.default');
        $service = app(TaxiKnowledgeTableService::class);

        Schema::connection($connection)->dropIfExists('knowledge_chunks');
        Schema::connection($connection)->dropIfExists('knowledge_documents');

        Schema::connection($connection)->create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('content');
            $table->text('category')->nullable();
            $table->text('embedding')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        $service->recreateAsTextFallback($connection);

        $this->assertTrue(Schema::connection($connection)->hasTable('knowledge_documents'));
        $this->assertTrue(Schema::connection($connection)->hasTable('knowledge_chunks'));
        $this->assertTrue($service->canQueryKnowledgeTables($connection));

        DB::connection($connection)->table('knowledge_documents')->insert([
            'title' => 'Personenvervoer',
            'content' => 'Test',
            'category' => 'diensten',
            'created_at' => now(),
        ]);

        $found = DB::connection($connection)->table('knowledge_documents')
            ->where('title', 'Personenvervoer')
            ->where('category', 'diensten')
            ->value('id');

        $this->assertNotNull($found);
    }

    #[Test]
    public function ensure_queryable_is_noop_when_tables_missing(): void
    {
        $connection = config('database.default');
        $service = app(TaxiKnowledgeTableService::class);

        Schema::connection($connection)->dropIfExists('knowledge_chunks');
        Schema::connection($connection)->dropIfExists('knowledge_documents');

        $service->ensureQueryableOnConnection($connection);

        $this->assertFalse(Schema::connection($connection)->hasTable('knowledge_documents'));
    }
}
