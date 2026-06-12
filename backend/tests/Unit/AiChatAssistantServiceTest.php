<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use App\Services\AiChatAssistantService;
use App\Services\WebsiteBuilderService;
use Mockery;
use Tests\TestCase;

class AiChatAssistantServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralSetting::clearRequestCache();
        Mockery::close();
        parent::tearDown();
    }

    public function test_extract_reply_text_from_common_n8n_payloads(): void
    {
        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $this->assertSame('Hallo taxi', $service->extractReplyText(['output' => 'Hallo taxi']));
        $this->assertSame('Antwoord', $service->extractReplyText(['reply' => 'Antwoord']));
        $this->assertSame('Nested', $service->extractReplyText([['json' => ['output' => 'Nested']]]));
        $this->assertSame('Plain', $service->extractReplyText(null, 'Plain'));
    }

    public function test_frontend_config_uses_taxi_copy_for_taxi_module(): void
    {
        $websiteBuilder = Mockery::mock(WebsiteBuilderService::class);
        $websiteBuilder->shouldReceive('resolvePublicFrontendModuleName')->andReturn('taxi');

        $service = new AiChatAssistantService($websiteBuilder);
        $config = $service->frontendConfig();

        $this->assertSame('taxi', $config['module']);
        $this->assertSame('Taxi-assistent', $config['title']);
    }

    public function test_webhook_setting_key_is_normalized_per_module(): void
    {
        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $this->assertSame('ai_chat_webhook_taxi', $service->webhookSettingKey('Taxi'));
        $this->assertSame('ai_chat_webhook_skillmatching', $service->webhookSettingKey('Skillmatching'));
    }

    public function test_normalize_webhook_url_replaces_legacy_n8n_host(): void
    {
        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $this->assertSame(
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant',
            $service->normalizeWebhookUrl('https://n8n.nexasuite.nl/webhook/nexa-taxi-assistant')
        );
    }

    public function test_webhook_url_for_module_normalizes_legacy_default(): void
    {
        config()->set(
            'services.ai_chat.module_defaults.taxi',
            'https://n8n.nexasuite.nl/webhook/nexa-taxi-assistant'
        );

        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $this->assertSame(
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant',
            $service->webhookUrlForModule('taxi')
        );
    }

    public function test_default_webhook_url_uses_module_defaults_from_config(): void
    {
        config()->set('services.ai_chat.module_defaults.skillmatching', 'https://n8n.example.test/skillmatching');
        config()->set('services.ai_chat.webhook_url', 'https://n8n.example.test/generic');

        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $this->assertSame(
            config('services.ai_chat.module_defaults.taxi'),
            $service->defaultWebhookUrlForModule('taxi')
        );
        $this->assertSame('https://n8n.example.test/skillmatching', $service->defaultWebhookUrlForModule('skillmatching'));
        $this->assertSame('https://n8n.example.test/generic', $service->defaultWebhookUrlForModule('unknown'));
    }

    public function test_build_webhook_payload_contains_company_id_and_message(): void
    {
        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $payload = $service->buildWebhookPayload('Welke ritten staan morgen gepland?', 2);

        $this->assertSame([
            'company_id' => 2,
            'message' => 'Welke ritten staan morgen gepland?',
        ], $payload);
    }

    public function test_extract_reply_text_formats_n8n_taxi_ride_answer_array(): void
    {
        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $reply = $service->extractReplyText([
            'answer' => [
                [
                    'id' => '1',
                    'klant_naam' => 'Test Klant Schiphol',
                    'pickup_adres' => 'Stationsplein 9, Amsterdam',
                    'dropoff_adres' => 'Vertrekhal, Schiphol',
                    'pickup_tijd' => '2026-06-09T20:27:19.000Z',
                    'status' => 'offered',
                ],
            ],
            'count' => 1,
        ]);

        $this->assertNotNull($reply);
        $this->assertStringContainsString('Er staat 1 rit gepland', $reply);
        $this->assertStringContainsString('Test Klant Schiphol', $reply);
        $this->assertStringContainsString('Stationsplein 9, Amsterdam', $reply);
        $this->assertStringContainsString('Vertrekhal, Schiphol', $reply);
    }

    public function test_extract_reply_text_formats_ride_with_driver_vehicle_and_phone(): void
    {
        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));

        $reply = $service->extractReplyText([
            'answer' => [
                [
                    'id' => '42',
                    'customer_name' => 'Jan Jansen',
                    'customer_phone' => '06-12345678',
                    'pickup_address' => 'Damrak 1, Amsterdam',
                    'dropoff_address' => 'Schiphol',
                    'pickup_at' => '2026-06-09T08:30:00.000Z',
                    'status' => 'assigned',
                    'status_label' => 'Toegewezen',
                    'driver_name' => 'Piet de Vries',
                    'vehicle_name' => 'Taxi 12',
                ],
            ],
            'count' => 1,
        ]);

        $this->assertNotNull($reply);
        $this->assertStringContainsString('Jan Jansen (#42)', $reply);
        $this->assertStringContainsString('Chauffeur: Piet de Vries', $reply);
        $this->assertStringContainsString('Voertuig: Taxi 12', $reply);
        $this->assertStringContainsString('Damrak 1, Amsterdam', $reply);
        $this->assertStringContainsString('Schiphol', $reply);
        $this->assertStringContainsString('Status: Toegewezen', $reply);
        $this->assertStringContainsString('06-12345678', $reply);
    }

    public function test_send_uses_website_fallback_when_webhook_returns_empty_body(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant' => \Illuminate\Support\Facades\Http::response('', 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Test Taxi Company',
            'is_active' => true,
        ]);

        WebsitePage::query()->create([
            'slug' => 'diensten-test',
            'title' => 'Diensten',
            'content' => '',
            'page_type' => 'custom',
            'module_name' => 'taxi',
            'company_id' => $company->id,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 99,
            'home_sections' => [
                'cards_ronde_hoeken' => [
                    'items' => [
                        [
                            'text' => '<p><strong>Luchthavenvervoer</strong></p><p>Stipt luchthavenvervoer zonder stress naar Schiphol en andere luchthavens.</p>',
                        ],
                    ],
                ],
            ],
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));
        $reply = $service->send('Hebben jullie luchthavenvervoer?', [], 'taxi');

        $this->assertStringContainsString('Ja, wij bieden Luchthavenvervoer aan.', $reply);
        $this->assertStringContainsString('Schiphol', $reply);
    }

    public function test_send_posts_configured_webhook_with_rbac_payload_for_diensten(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant' => \Illuminate\Support\Facades\Http::response([
                'output' => 'Ja, wij bieden luchthavenvervoer aan.',
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        app()->instance('resolved_tenant_id', 2);

        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));
        $reply = $service->send('Hebben jullie luchthavenvervoer?', [], 'taxi');

        $this->assertStringContainsString('luchthavenvervoer', mb_strtolower($reply));

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() === 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant'
                && $request['company_id'] === 2
                && $request['message'] === 'Hebben jullie luchthavenvervoer?'
                && $request['channel'] === 'public'
                && $request['intent'] === 'diensten'
                && $request['useRag'] === true
                && $request['isAdmin'] === false
                && $request['allowLiveData'] === false
                && ! array_key_exists('sql_token', $request->data())
                && ! array_key_exists('history', $request->data())
                && ! array_key_exists('sessionId', $request->data());
        });
    }

    public function test_public_tarieven_posts_webhook_with_public_rates_flags(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant' => \Illuminate\Support\Facades\Http::response([
                'answer' => 'De actuele tarieven van Nexa Taxi zijn: instaptarief €3,60.',
                'source' => 'public_rates',
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        app()->instance('resolved_tenant_id', 2);

        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));
        $reply = $service->send('Wat zijn jullie tarieven?', [], 'taxi');

        $this->assertStringContainsString('instaptarief', mb_strtolower($reply));

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() === 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant'
                && $request['intent'] === 'tarieven'
                && $request['allowLiveData'] === false
                && $request['allowPublicRates'] === true
                && isset($request['sql_token'])
                && isset($request['laravel_live_query_url']);
        });
    }

    public function test_public_live_question_is_denied_without_webhook_call(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $company = Company::query()->create([
            'name' => 'Test Taxi Tenant',
            'is_active' => true,
        ]);

        $deniedMessage = 'Daar kan ik je helaas geen informatie over geven. Stel je vraag gerust op een andere manier, of neem contact met ons op.';

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');
        config()->set('ai_chat.live_data_denied_message', $deniedMessage);
        GeneralSetting::set('ai_chat_taxi_live_data_denied_message', $deniedMessage, $company->id);

        app()->instance('resolved_tenant_id', $company->id);

        $service = new AiChatAssistantService(Mockery::mock(WebsiteBuilderService::class));
        $reply = $service->send('Welke ritten staan morgen gepland?', [], 'taxi');

        $this->assertSame($deniedMessage, $reply);

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
