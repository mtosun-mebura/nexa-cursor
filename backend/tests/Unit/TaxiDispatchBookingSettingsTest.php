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

    public function test_past_pickup_grace_minutes_defaults_from_config(): void
    {
        config(['taxi-dispatch.past_pickup_grace_minutes' => 90]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertSame(90, $service->pastPickupGraceMinutes(99999));
    }

    public function test_pickup_queue_cutoff_subtracts_grace_minutes(): void
    {
        config(['taxi-dispatch.past_pickup_grace_minutes' => 60]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $now = now('Europe/Amsterdam')->startOfSecond();

        $cutoff = $service->pickupQueueCutoffAt(99999, $now);

        $expectedWall = $now->copy()->subMinutes(60)->format('Y-m-d H:i:s');
        $this->assertSame($expectedWall, $cutoff->format('Y-m-d H:i:s'));
    }

    public function test_legacy_grace_hours_setting_converts_to_minutes(): void
    {
        $company = Company::query()->create(['name' => 'Grace Co', 'slug' => 'grace-'.uniqid()]);
        GeneralSetting::set(TaxiDispatchSettingsService::KEY_PAST_PICKUP_GRACE_HOURS, '2', $company->id);

        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertSame(120, $service->pastPickupGraceMinutes((int) $company->id));
    }

    public function test_scheduled_ride_is_overdue_after_pickup_plus_acceptance_ttl(): void
    {
        config(['taxi-dispatch.offer_ttl_seconds' => 300]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $now = \Illuminate\Support\Carbon::parse('2026-08-13 12:00:00', 'Europe/Amsterdam');

        $ride = new \App\Modules\NexaTaxi\Models\RideRequest([
            'company_id' => 1,
            'pickup_at' => \Illuminate\Support\Carbon::createFromFormat(
                'Y-m-d H:i:s',
                '2026-08-13 11:54:00',
                'UTC'
            ),
        ]);

        $this->assertTrue($service->scheduledRideIsOverdue($ride, 1, $now));

        $ride->pickup_at = \Illuminate\Support\Carbon::createFromFormat(
            'Y-m-d H:i:s',
            '2026-08-13 11:56:00',
            'UTC'
        );
        $this->assertFalse($service->scheduledRideIsOverdue($ride, 1, $now));
    }

    public function test_scheduled_return_leg_uses_return_at_for_overdue_check(): void
    {
        config(['taxi-dispatch.offer_ttl_seconds' => 300]);
        $env = $this->createMock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));
        $now = \Illuminate\Support\Carbon::parse('2026-08-13 12:00:00', 'Europe/Amsterdam');

        $ride = new \App\Modules\NexaTaxi\Models\RideRequest([
            'company_id' => 1,
            'pickup_at' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-13 08:00:00', 'UTC'),
            'return_at' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-13 11:54:00', 'UTC'),
            'outbound_completed_at' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-13 10:00:00', 'UTC'),
            'booking_payload' => ['step_data' => ['return_trip' => true]],
        ]);

        $this->assertTrue($service->scheduledRideIsOverdue($ride, 1, $now));

        $ride->return_at = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-13 11:56:00', 'UTC');
        $this->assertFalse($service->scheduledRideIsOverdue($ride, 1, $now));
    }

    public function test_driver_iso_keeps_amsterdam_wall_clock_when_app_timezone_is_utc(): void
    {
        $stored = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-13 08:25:00', 'UTC');
        $iso = \App\Modules\NexaTaxi\Support\ContractTransportTimezone::toDriverIso8601($stored);

        $this->assertSame('2026-08-13T08:25:00+02:00', $iso);
    }

    public function test_booking_whatsapp_auto_send_defaults_off_without_explicit_dispatch_setting(): void
    {
        $env = $this->createMock(EnvService::class);
        $env->method('get')->willReturnCallback(function (string $key, $default = '', ?int $companyId = null) {
            return match ($key) {
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => '+31600112233',
                default => $default,
            };
        });

        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $this->assertFalse($service->bookingWhatsappEnabled(99999));
    }

    public function test_whatsapp_api_token_forces_api_send_and_disables_click_to_chat(): void
    {
        $company = Company::query()->create(['name' => 'Api Wa Co', 'slug' => 'api-wa-'.uniqid()]);

        GeneralSetting::set('WHATSAPP_API_TOKEN', 'EAA-test-token');
        GeneralSetting::set('WHATSAPP_PHONE_NUMBER_ID', '123456789');
        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_ENABLED', '1', $company->id);
        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_NUMBER', '+31600112233', $company->id);
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_BOOKING_WHATSAPP_ENABLED,
            '0',
            $company->id
        );
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_CUSTOMER_ACCEPT_WHATSAPP_ENABLED,
            '0',
            $company->id
        );

        $service = app(TaxiDispatchSettingsService::class);
        $notifications = app(TaxiBookingNotificationService::class);

        $this->assertTrue($service->whatsappApiConfigured((int) $company->id));
        $this->assertTrue($service->bookingWhatsappEnabled((int) $company->id));
        $this->assertTrue($service->customerAcceptWhatsappEnabled((int) $company->id));
        $this->assertFalse($service->bookingWhatsappClickToChatEnabled((int) $company->id));
        $this->assertTrue($notifications->whatsappAutoSendEnabled((int) $company->id));
        $this->assertFalse($notifications->whatsappClientClickToChatEnabled((int) $company->id));
    }

    public function test_click_to_chat_disabled_when_admin_master_switch_is_off(): void
    {
        $company = Company::query()->create(['name' => 'Wa Co', 'slug' => 'wa-co-'.uniqid()]);

        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_ENABLED', '0', $company->id);
        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_NUMBER', '+31600112233', $company->id);

        $service = app(TaxiDispatchSettingsService::class);
        $notifications = app(TaxiBookingNotificationService::class);

        $this->assertFalse($service->bookingWhatsappClickToChatEnabled((int) $company->id));
        $this->assertFalse($notifications->whatsappClientClickToChatEnabled((int) $company->id));
    }

    public function test_click_to_chat_enabled_when_admin_master_switch_is_on(): void
    {
        $company = Company::query()->create(['name' => 'Wa On Co', 'slug' => 'wa-on-'.uniqid()]);

        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_ENABLED', '1', $company->id);
        GeneralSetting::set('WHATSAPP_CLICK_TO_CHAT_NUMBER', '+31600112233', $company->id);

        $service = app(TaxiDispatchSettingsService::class);

        $this->assertTrue($service->bookingWhatsappClickToChatEnabled((int) $company->id));
        $this->assertSame('+31600112233', $service->bookingWhatsappNumber((int) $company->id));
    }

    public function test_company_booking_notify_uses_platform_switch_and_tenant_number(): void
    {
        $company = Company::query()->create(['name' => 'Notify Co', 'slug' => 'notify-'.uniqid()]);

        GeneralSetting::set('WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED', '0');
        GeneralSetting::set('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', '+31699887766', $company->id);

        $service = app(TaxiDispatchSettingsService::class);

        $this->assertFalse($service->companyBookingWhatsappNotifyEnabled((int) $company->id));
        $this->assertSame('+31699887766', $service->companyBookingWhatsappNotifyNumber((int) $company->id));

        GeneralSetting::set('WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED', '1');
        $this->assertTrue($service->companyBookingWhatsappNotifyEnabled((int) $company->id));
    }
}
