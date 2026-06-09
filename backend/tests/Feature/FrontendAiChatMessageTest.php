<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FrontendAiChatMessageTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralSetting::clearRequestCache();
        parent::tearDown();
    }

    public function test_public_tarieven_question_goes_through_n8n_webhook(): void
    {
        Http::fake([
            'https://n8n.nexasuite.nl/webhook/nexa-taxi-assistant' => Http::response([
                'answer' => 'De actuele tarieven van Test Taxi BV zijn: instaptarief €3,60.',
                'source' => 'public_rates',
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://n8n.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        GeneralSetting::set('ai_chat_webhook_taxi', 'https://n8n.nexasuite.nl/webhook/nexa-taxi-assistant', $company->id);
        GeneralSetting::set('ai_chat_enabled', '1', $company->id);
        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->postJson('/ai-chat/message', [
            'message' => 'Wat zijn de tarieven?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertStringContainsString('instaptarief', mb_strtolower((string) $response->json('reply')));

        Http::assertSent(function ($request) {
            return $request['intent'] === 'tarieven' && $request['allowPublicRates'] === true;
        });
    }
}
