<?php

namespace Tests\Unit;

use App\Models\PlatformBillingSetting;
use App\Services\PlatformBilling\PlatformMollieService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PlatformMollieServiceConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_prefers_platform_billing_settings_over_env(): void
    {
        config(['platform-billing.mollie_api_key' => 'test_fromenv1234567890']);

        $settings = PlatformBillingSetting::current();
        $settings->mollie_api_key = Crypt::encryptString('test_fromdb12345678901');
        $settings->save();

        $service = app(PlatformMollieService::class);

        $this->assertSame('test_fromdb12345678901', $service->apiKey());
    }

    public function test_webhook_url_prefers_platform_billing_settings(): void
    {
        config(['platform-billing.webhook_url' => 'https://env.example/api/platform/webhooks/mollie']);

        $settings = PlatformBillingSetting::current();
        $settings->mollie_webhook_url = 'https://db.example/api/platform/webhooks/mollie';
        $settings->save();

        $service = app(PlatformMollieService::class);

        $this->assertSame('https://db.example/api/platform/webhooks/mollie', $service->webhookUrl());
    }

    public function test_falls_back_to_env_when_settings_empty(): void
    {
        config(['platform-billing.mollie_api_key' => 'test_envfallback123456']);

        $settings = PlatformBillingSetting::current();
        $settings->mollie_api_key = null;
        $settings->save();

        $service = app(PlatformMollieService::class);

        $this->assertSame('test_envfallback123456', $service->apiKey());
    }
}
