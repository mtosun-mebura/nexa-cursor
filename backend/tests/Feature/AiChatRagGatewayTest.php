<?php

namespace Tests\Feature;

use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiChatRagGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.module_taxi_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        Schema::connection('module_taxi_test')->create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('content');
            $table->text('category')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        DB::connection('module_taxi_test')->table('knowledge_documents')->insert([
            'title' => 'Luchthavenvervoer',
            'content' => 'Wij rijden naar Schiphol en andere luchthavens.',
            'category' => 'diensten',
            'created_at' => now(),
        ]);

        $moduleDb = $this->createMock(ModuleDatabaseService::class);
        $moduleDb->method('ensureModuleStorageReady')->with('taxi');
        $moduleDb->method('getModuleConnectionName')->with('taxi')->willReturn('module_taxi_test');

        $this->app->instance(ModuleDatabaseService::class, $moduleDb);
    }

    public function test_rag_search_endpoint_returns_formatted_answer(): void
    {
        $response = $this->postJson('/integrations/n8n/ai-chat/rag-search', [
            'keyword' => 'luchthaven',
            'message' => 'Hebben jullie luchthavenvervoer?',
            'module' => 'taxi',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'rag')
            ->assertJsonPath('count', 1);

        $this->assertStringContainsString('Luchthavenvervoer', (string) $response->json('answer'));
    }
}
