<?php

namespace Tests\Unit;

use App\Services\AiChat\AiChatKnowledgeFallbackService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiChatKnowledgeFallbackServiceTest extends TestCase
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
            'title' => 'Contactgegevens',
            'content' => 'Telefoon: +31 20 123 4567. Contactformulier: <a href="/contact">Contact</a>',
            'category' => 'contact',
            'created_at' => now(),
        ]);

        $moduleDb = $this->createMock(ModuleDatabaseService::class);
        $moduleDb->method('getModuleConnectionName')->with('taxi')->willReturn('module_taxi_test');
        $moduleDb->method('registerConnection')->with('taxi');

        $this->app->instance(ModuleDatabaseService::class, $moduleDb);
        Config::set('database.connections.module_taxi_test', Config::get('database.connections.module_taxi_test'));
    }

    public function test_finds_contact_document_by_keyword(): void
    {
        $service = app(AiChatKnowledgeFallbackService::class);

        $answer = $service->search('contact', 'taxi');

        $this->assertNotNull($answer);
        $this->assertStringContainsString('Contactgegevens', $answer);
        $this->assertStringContainsString('+31 20 123 4567', $answer);
        $this->assertStringContainsString('[Contact](/contact)', $answer);
    }
}
