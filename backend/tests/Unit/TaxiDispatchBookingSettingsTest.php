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

    public function test_past_pickup_grace_hours_defaults_from_config(): void
    {
        config(['taxi-dispatch.past_pickup_grace_hours' => 3]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertSame(3, $service->pastPickupGraceHours(99999));
    }

    public function test_pickup_queue_cutoff_subtracts_grace_hours(): void
    {
        config(['taxi-dispatch.past_pickup_grace_hours' => 2]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $now = now()->startOfSecond();

        $cutoff = $service->pickupQueueCutoffAt(99999, $now);

        $this->assertTrue($cutoff->equalTo($now->copy()->subHours(2)));
    }

    public function test_scheduled_ride_is_overdue_after_pickup_plus_acceptance_ttl(): void
    {
        config(['taxi-dispatch.offer_ttl_seconds' => 300]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $now = now()->startOfSecond();

        $ride = new \App\Modules\NexaTaxi\Models\RideRequest([
            'company_id' => 1,
            'pickup_at' => $now->copy()->subMinutes(6),
        ]);

        $this->assertTrue($service->scheduledRideIsOverdue($ride, 1, $now));

        $ride->pickup_at = $now->copy()->subMinutes(4);
        $this->assertFalse($service->scheduledRideIsOverdue($ride, 1, $now));
    }
}
