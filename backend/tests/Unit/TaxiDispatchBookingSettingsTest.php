<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Services\EnvService;
use App\Services\PaymentProviderService;
use Tests\TestCase;

class TaxiDispatchBookingSettingsTest extends TestCase
{
    public function test_booking_whatsapp_number_falls_back_to_env(): void
    {
        $env = $this->createMock(EnvService::class);
        $env->method('get')->willReturnCallback(function (string $key, $default = '') {
            return match ($key) {
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => '+31600112233',
                default => $default,
            };
        });

        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertSame('+31600112233', $service->bookingWhatsappNumber(99999));
    }

    public function test_booking_customer_email_enabled_defaults_true(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertTrue($service->bookingCustomerEmailEnabled(99999));
    }

    public function test_customer_email_required_when_booking_or_accept_email_enabled(): void
    {
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertTrue($service->customerEmailRequiredForBooking(99999));
    }
}
