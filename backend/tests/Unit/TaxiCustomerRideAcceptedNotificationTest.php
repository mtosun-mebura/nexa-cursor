<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiCustomerRideAcceptedNotificationService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiCustomerRideAcceptedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dispatch_settings_default_customer_accept_email_on(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV']);
        $settings = app(TaxiDispatchSettingsService::class);

        $this->assertTrue($settings->customerAcceptNotificationEnabled($company->id));
        $this->assertTrue($settings->customerAcceptEmailEnabled($company->id));
        $this->assertFalse($settings->customerAcceptWhatsappEnabled($company->id));
        $this->assertFalse($settings->customerAcceptSmsEnabled($company->id));
    }

    #[Test]
    public function it_skips_all_channels_when_customer_accept_disabled(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV 2']);
        $driver = User::factory()->create(['company_id' => $company->id]);
        GeneralSetting::set(TaxiDispatchSettingsService::KEY_CUSTOMER_ACCEPT_ENABLED, '0', $company->id);

        $ride = new RideRequest([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'customer_email' => 'piet@example.com',
            'customer_phone' => '0612345678',
        ]);
        $ride->id = 100;

        app(TaxiCustomerRideAcceptedNotificationService::class)
            ->notifyAfterRideAssigned((string) config('database.default'), $ride, $driver);

        $this->assertFalse(
            app(TaxiCustomerRideAcceptedNotificationService::class)
                ->channelAlreadySent((string) config('database.default'), 100, 'email')
        );
    }

    #[Test]
    public function plain_message_replaces_placeholders(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Co']);
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_CUSTOMER_ACCEPT_PLAIN_MESSAGE,
            'Hoi {{CUSTOMER_NAME}}, chauffeur {{DRIVER_NAME}}',
            $company->id
        );

        $driver = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Eva', 'last_name' => 'Rit']);
        $ride = new RideRequest([
            'company_id' => $company->id,
            'customer_name' => 'Klaas',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);

        $vars = app(TaxiCustomerRideAcceptedNotificationService::class)
            ->buildVariables($ride, $driver, $company->id);

        $this->assertSame('Klaas', $vars['CUSTOMER_NAME']);
        $this->assertStringContainsString('Eva', $vars['DRIVER_NAME']);

        $message = app(TaxiDispatchSettingsService::class)->customerAcceptPlainMessage($company->id);
        $rendered = str_replace('{{CUSTOMER_NAME}}', $vars['CUSTOMER_NAME'], $message);
        $rendered = str_replace('{{DRIVER_NAME}}', $vars['DRIVER_NAME'], $rendered);
        $this->assertStringContainsString('Hoi Klaas', $rendered);
    }
}
