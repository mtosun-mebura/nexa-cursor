<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_returns_challenge_when_token_matches(): void
    {
        GeneralSetting::set('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'nexa-test-verify');

        $response = $this->get('/api/whatsapp/webhook?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'nexa-test-verify',
            'hub.challenge' => '12345challenge',
        ]));

        $response->assertOk();
        $response->assertSee('12345challenge', false);
    }

    public function test_verify_rejects_wrong_token(): void
    {
        GeneralSetting::set('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'nexa-test-verify');

        $response = $this->get('/api/whatsapp/webhook?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong',
            'hub.challenge' => '12345challenge',
        ]));

        $response->assertForbidden();
    }

    public function test_handle_acknowledges_post(): void
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ]);

        $response->assertOk();
        $response->assertSee('EVENT_RECEIVED', false);
    }
}
