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
            'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant' => Http::response([
                'answer' => 'De actuele tarieven van Test Taxi BV zijn: instaptarief €3,60.',
                'source' => 'public_rates',
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        GeneralSetting::set('ai_chat_webhook_taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant', $company->id);
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

    public function test_quote_flow_uses_quote_address_coordinates_for_route_calculation(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [[
                    'distance' => 198000,
                    'duration' => 7200,
                    'geometry' => 'mockRoutePolyline',
                ]],
            ], 200),
        ]);

        config()->set('services.ai_chat.module_defaults.taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant');

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        GeneralSetting::set('ai_chat_webhook_taxi', 'https://automations.nexasuite.nl/webhook/nexa-taxi-assistant', $company->id);
        GeneralSetting::set('ai_chat_enabled', '1', $company->id);
        app()->instance('resolved_tenant_id', $company->id);

        $sessionId = 'quote-route-test';
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->postJson('/ai-chat/message', [
            'message' => 'Wat kost een rit naar Dusseldorf Airport?',
            'module' => 'taxi',
            'sessionId' => $sessionId,
        ], $headers)->assertOk();

        $this->postJson('/ai-chat/message', [
            'message' => 'Stationsplein, Enschede, Nederland',
            'module' => 'taxi',
            'sessionId' => $sessionId,
            'quoteAddress' => [
                'label' => 'Stationsplein, Enschede, Nederland',
                'place_id' => 'ChIJEnschedePickup',
                'lat' => 52.2222,
                'lng' => 6.8914,
            ],
        ], $headers)->assertOk();

        $this->postJson('/ai-chat/message', [
            'message' => 'Luchthaven Düsseldorf (DUS), Flughafenstraße, Düsseldorf, Duitsland',
            'module' => 'taxi',
            'sessionId' => $sessionId,
            'quoteAddress' => [
                'label' => 'Luchthaven Düsseldorf (DUS), Flughafenstraße, Düsseldorf, Duitsland',
                'place_id' => 'ChIJDusseldorfAirport',
                'lat' => 51.2895,
                'lng' => 6.7667,
            ],
        ], $headers)->assertOk();

        $this->postJson('/ai-chat/message', [
            'message' => '2',
            'module' => 'taxi',
            'sessionId' => $sessionId,
        ], $headers)->assertOk();

        $this->postJson('/ai-chat/message', [
            'message' => 'Kleine ruimbagage: 1',
            'module' => 'taxi',
            'sessionId' => $sessionId,
            'quoteBaggage' => [
                'baggage' => ['small' => 1],
                'special_baggage' => [],
            ],
        ], $headers)->assertOk();

        $this->postJson('/ai-chat/message', [
            'message' => now()->addDay()->format('Y-m-d H:i'),
            'module' => 'taxi',
            'sessionId' => $sessionId,
        ], $headers)->assertOk();

        $final = $this->postJson('/ai-chat/message', [
            'message' => 'geen',
            'module' => 'taxi',
            'sessionId' => $sessionId,
        ], $headers);

        $final->assertOk();
        $reply = (string) $final->json('reply');
        $this->assertStringContainsString('prijsindicatie', mb_strtolower($reply));
        $this->assertStringNotContainsString('route niet berekenen', mb_strtolower($reply));
        $this->assertStringContainsString('book_step=confirm', $reply);
        $this->assertStringContainsString('book_distance_meters=', $reply);
        $this->assertStringContainsString('book_route_polyline=mockRoutePolyline', $reply);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'router.project-osrm.org');
        });
    }
}
