<?php

namespace Tests\Unit;

use App\Services\EnvService;
use App\Services\WhatsAppBusinessService;
use PHPUnit\Framework\TestCase;

class WhatsAppBusinessServiceTest extends TestCase
{
    public function test_normalize_recipient_strips_plus_prefix(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new WhatsAppBusinessService($env);

        $this->assertSame('31612345678', $service->normalizeRecipientForApi('+31612345678'));
        $this->assertSame('31612345678', $service->normalizeRecipientForApi('0612345678'));
    }

    public function test_is_configured_requires_token_and_phone_id(): void
    {
        $env = $this->createMock(EnvService::class);
        $env->method('get')->willReturnMap([
            ['WHATSAPP_API_TOKEN', '', ''],
            ['WHATSAPP_PHONE_NUMBER_ID', '', ''],
        ]);

        $this->assertFalse((new WhatsAppBusinessService($env))->isConfigured());

        $env2 = $this->createMock(EnvService::class);
        $env2->method('get')->willReturnMap([
            ['WHATSAPP_API_TOKEN', '', 'token'],
            ['WHATSAPP_PHONE_NUMBER_ID', '', '123'],
        ]);

        $this->assertTrue((new WhatsAppBusinessService($env2))->isConfigured());
    }
}
