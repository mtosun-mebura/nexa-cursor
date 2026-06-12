<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaxiPortalAiChatMessageTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralSetting::clearRequestCache();
        parent::tearDown();
    }

    public function test_mijn_taxi_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/mijn-taxi/api/ai-chat/message', [
            'message' => 'Wanneer word ik opgehaald?',
        ]);

        $response->assertUnauthorized();
    }

    public function test_public_endpoint_does_not_issue_mijn_rit_sql_token_for_logged_in_user(): void
    {
        Http::fake();

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        GeneralSetting::set('ai_chat_webhook_taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant', $company->id);
        GeneralSetting::set('ai_chat_enabled', '1', $company->id);
        app()->instance('resolved_tenant_id', $company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'klant@example.com',
        ]);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->actingAs($user)->postJson('/ai-chat/message', [
            'message' => 'Wanneer word ik opgehaald?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('Mijn Taxi', (string) $response->json('reply'));

        Http::assertNothingSent();
    }

    public function test_mijn_taxi_returns_friendly_message_for_public_rate_questions(): void
    {
        Http::fake();

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        GeneralSetting::set('ai_chat_webhook_taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant', $company->id);
        GeneralSetting::set('ai_chat_enabled', '1', $company->id);
        app()->instance('resolved_tenant_id', $company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'klant@example.com',
        ]);

        $response = $this->withoutMiddleware()->actingAs($user)->postJson('/mijn-taxi/api/ai-chat/message', [
            'message' => 'Wat kost een rit naar Schiphol?',
            'module' => 'taxi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertStringContainsString('website', (string) $response->json('reply'));
        $this->assertStringNotContainsString('403', (string) $response->json('reply'));
        $this->assertStringNotContainsString('Publieke tarieven', (string) $response->json('reply'));

        Http::assertNothingSent();
    }
}
