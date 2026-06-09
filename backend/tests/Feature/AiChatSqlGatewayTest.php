<?php

namespace Tests\Feature;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatIntent;
use App\Services\AiChat\AiChatSqlTokenService;
use Tests\TestCase;

class AiChatSqlGatewayTest extends TestCase
{
    public function test_sql_gateway_rejects_missing_token(): void
    {
        $response = $this->postJson('/api/ai-chat/live-query', [
            'intent' => AiChatIntent::RittenMorgen->value,
            'company_id' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_sql_gateway_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/ai-chat/live-query', [
            'intent' => AiChatIntent::RittenMorgen->value,
            'sql_token' => 'invalid-token',
            'company_id' => 1,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_sql_gateway_rejects_company_id_mismatch(): void
    {
        $tokenService = app(AiChatSqlTokenService::class);
        $context = new AiChatRequestContext(
            companyId: 5,
            channel: AiChatChannel::Admin,
            userId: 10,
        );
        $intent = new AiChatIntentResult(
            intent: AiChatIntent::RittenMorgen,
            isAdmin: true,
            allowLiveData: true,
            allowPublicRates: false,
        );

        $token = $tokenService->issue($context, $intent);
        $this->assertNotNull($token);

        $response = $this->postJson('/api/ai-chat/live-query', [
            'intent' => AiChatIntent::RittenMorgen->value,
            'sql_token' => $token,
            'company_id' => 99,
        ]);

        $response->assertStatus(403);
    }

    public function test_sql_gateway_executes_with_valid_token(): void
    {
        $tokenService = app(AiChatSqlTokenService::class);
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: 10,
        );
        $intent = new AiChatIntentResult(
            intent: AiChatIntent::RittenMorgen,
            isAdmin: true,
            allowLiveData: true,
            allowPublicRates: false,
        );

        $token = $tokenService->issue($context, $intent);

        $response = $this->postJson('/api/ai-chat/live-query', [
            'intent' => AiChatIntent::RittenMorgen->value,
            'sql_token' => $token,
            'company_id' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['count', 'rows']);
    }

    public function test_sql_gateway_returns_formatted_public_rates(): void
    {
        $tokenService = app(AiChatSqlTokenService::class);
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        );
        $intent = new AiChatIntentResult(
            intent: AiChatIntent::Tarieven,
            isAdmin: false,
            allowLiveData: false,
            allowPublicRates: true,
        );

        $token = $tokenService->issue($context, $intent);
        $this->assertNotNull($token);

        $response = $this->postJson('/api/ai-chat/live-query', [
            'intent' => AiChatIntent::Tarieven->value,
            'sql_token' => $token,
            'company_id' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('source', 'public_rates');
        $response->assertJsonStructure(['answer', 'rows']);
    }
}
