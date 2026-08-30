<?php

namespace Tests\Unit;

use App\Services\EnvService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppBusinessServiceTest extends TestCase
{
    public function test_normalize_recipient_strips_plus_prefix(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new WhatsAppBusinessService($env);

        $this->assertSame('31612345678', $service->normalizeRecipientForApi('+31612345678'));
        $this->assertSame('31612345678', $service->normalizeRecipientForApi('0612345678'));
    }

    public function test_is_outside_customer_care_window_error(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new WhatsAppBusinessService($env);

        $this->assertTrue($service->isOutsideCustomerCareWindowError('(#131047) Re-engagement message'));
        $this->assertFalse($service->isOutsideCustomerCareWindowError('Invalid OAuth access token'));
    }

    public function test_is_configured_requires_token_and_phone_id(): void
    {
        $env = $this->createMock(EnvService::class);
        $env->method('get')->willReturnCallback(function (string $key, $default = '', ?int $companyId = null) {
            return $default;
        });

        $this->assertFalse((new WhatsAppBusinessService($env))->isConfigured());

        $env2 = $this->createMock(EnvService::class);
        $env2->method('get')->willReturnCallback(function (string $key, $default = '', ?int $companyId = null) {
            return match ($key) {
                'WHATSAPP_API_TOKEN' => 'token',
                'WHATSAPP_PHONE_NUMBER_ID' => '123',
                default => $default,
            };
        });

        $this->assertTrue((new WhatsAppBusinessService($env2))->isConfigured(999));
        $this->assertTrue((new WhatsAppBusinessService($env2))->isConfigured());
    }

    public function test_prefix_message_with_tenant_adds_bold_name(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new WhatsAppBusinessService($env);

        $this->assertSame('Hallo', $service->prefixMessageWithTenant('Hallo', null));
    }

    public function test_template_parameters_flatten_newlines_and_tabs(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new WhatsAppBusinessService($env);

        $method = new \ReflectionMethod(WhatsAppBusinessService::class, 'sanitizeTemplateParameter');
        $method->setAccessible(true);

        $this->assertSame(
            'Ophalen: Dam 1 Afzetten: Centraal Prijs: €25',
            $method->invoke($service, "Ophalen: Dam 1\nAfzetten: Centraal\nPrijs: €25")
        );
        $this->assertSame('a b', $method->invoke($service, "a\tb"));
        $this->assertSame('veel   spaties', $method->invoke($service, 'veel     spaties'));
    }

    public function test_extract_outbound_message_id_from_graph_json(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new WhatsAppBusinessService($env);

        $this->assertSame(
            'wamid.HBgNMzE2MTIzNDU2NzgVAgARGBI4QjYwRkYwAA==',
            $service->extractOutboundMessageId([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.HBgNMzE2MTIzNDU2NzgVAgARGBI4QjYwRkYwAA=='],
                ],
            ])
        );
        $this->assertNull($service->extractOutboundMessageId(['messages' => []]));
        $this->assertNull($service->extractOutboundMessageId([]));
    }

    public function test_send_template_returns_wamid_from_graph_response(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '31612345678', 'wa_id' => '31612345678']],
                'messages' => [['id' => 'wamid.HBgNMzE2MTIzNDU2NzgVAgARGBI4QjYwRkYwAA==']],
            ], 200),
        ]);

        $env = $this->createMock(EnvService::class);
        $env->method('get')->willReturnCallback(function (string $key, $default = '', ?int $companyId = null) {
            return match ($key) {
                'WHATSAPP_API_TOKEN' => 'token',
                'WHATSAPP_PHONE_NUMBER_ID' => '123',
                'WHATSAPP_API_VERSION' => 'v18.0',
                default => is_string($default) ? $default : '',
            };
        });

        $result = (new WhatsAppBusinessService($env))->sendTemplate(
            '+31612345678',
            'rit_ophaal_voorstel',
            'nl',
            ['Dam 1', 'CS']
        );

        $this->assertTrue($result['ok']);
        $this->assertSame(
            'wamid.HBgNMzE2MTIzNDU2NzgVAgARGBI4QjYwRkYwAA==',
            $result['wamid']
        );
    }
}
