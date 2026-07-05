<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Services\EnvService;
use App\Services\PaymentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxiDispatchBookingSettingsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_scheduled_return_leg_uses_return_at_for_overdue_check(): void
    {
        config(['taxi-dispatch.offer_ttl_seconds' => 300]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $now = now()->startOfSecond();

        $ride = new \App\Modules\NexaTaxi\Models\RideRequest([
            'company_id' => 1,
            'pickup_at' => $now->copy()->subHours(3),
            'return_at' => $now->copy()->subMinutes(6),
            'outbound_completed_at' => $now->copy()->subHour(),
            'booking_payload' => ['step_data' => ['return_trip' => true]],
        ]);

        $this->assertTrue($service->scheduledRideIsOverdue($ride, 1, $now));

        $ride->return_at = $now->copy()->subMinutes(4);
        $this->assertFalse($service->scheduledRideIsOverdue($ride, 1, $now));
    }

    public function test_booking_whatsapp_auto_send_defaults_off_without_explicit_dispatch_setting(): void
    {
        $env = $this->createMock(EnvService::class);
        $env->method('get')->willReturnCallback(function (string $key, $default = '') {
            return match ($key) {
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => '+31600112233',
                default => $default,
            };
        });

        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertFalse($service->bookingWhatsappEnabled(99999));
    }

    public function test_click_to_chat_disabled_when_admin_master_switch_is_off(): void
    {
        $company = Company::query()->create(['name' => 'Wa Co', 'slug' => 'wa-co-'.uniqid()]);

        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_ENABLED', '0', $company->id);
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_BOOKING_WHATSAPP_CLICK_TO_CHAT,
            '1',
            $company->id
        );
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_BOOKING_WHATSAPP_NUMBER,
            '+31600112233',
            $company->id
        );

        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $notifications = app(TaxiBookingNotificationService::class);

        $this->assertFalse($service->bookingWhatsappClickToChatEnabled((int) $company->id));
        $this->assertFalse($notifications->whatsappClientClickToChatEnabled((int) $company->id));
    }

    public function test_click_to_chat_enabled_when_admin_master_switch_is_on_and_dispatch_allows(): void
    {
        $company = Company::query()->create(['name' => 'Wa On Co', 'slug' => 'wa-on-'.uniqid()]);

        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_ENABLED', '1', $company->id);
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_BOOKING_WHATSAPP_NUMBER,
            '+31600112233',
            $company->id
        );

        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertTrue($service->bookingWhatsappClickToChatEnabled((int) $company->id));
    }
}
