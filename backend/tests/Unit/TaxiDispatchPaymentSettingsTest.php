<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\PaymentProvider;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Services\EnvService;
use App\Services\PaymentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class TaxiDispatchPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mollie_uses_payment_provider_per_company(): void
    {
        $company = Company::create(['name' => 'Test Taxi BV', 'is_active' => true]);
        $companyId = (int) $company->id;

        PaymentProvider::create([
            'company_id' => $companyId,
            'name' => 'Mollie Test',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString('test_1234567890abcdef'),
                'webhook_url' => 'https://example.com/webhooks/mollie',
                'test_mode' => true,
            ],
        ]);

        $paymentProviders = app(PaymentProviderService::class);
        $this->assertTrue($paymentProviders->isMollieConfiguredForCompany($companyId));
        $this->assertSame('test_1234567890abcdef', $paymentProviders->mollieApiKeyForCompany($companyId));
        $this->assertSame(
            'https://example.com/webhooks/mollie',
            $paymentProviders->mollieWebhookUrlForCompany($companyId)
        );
    }

    public function test_payment_options_require_mollie_provider(): void
    {
        $company = Company::create(['name' => 'Taxi 2', 'is_active' => true]);
        $companyId = (int) $company->id;

        $env = Mockery::mock(EnvService::class);
        $service = new TaxiDispatchSettingsService($env, app(PaymentProviderService::class));

        $service->setPaymentBookingEnabled(true, $companyId);
        $service->setPaymentDriverEnabled(true, $companyId);

        $options = $service->paymentOptionsForTenant($companyId);
        $this->assertFalse($options['booking']);
        $this->assertFalse($options['mollie_configured']);

        PaymentProvider::create([
            'company_id' => $companyId,
            'name' => 'Mollie',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString('test_key'),
            ],
        ]);

        $options = $service->paymentOptionsForTenant($companyId);
        $this->assertTrue($options['booking']);
        $this->assertTrue($options['driver']);
        $this->assertTrue($options['mollie_configured']);
    }

    public function test_inactive_mollie_test_provider_allowed_in_non_production(): void
    {
        config(['taxi-dispatch.allow_mollie_test_providers' => true]);

        $company = Company::create(['name' => 'Taxi Test', 'is_active' => true]);
        $companyId = (int) $company->id;

        PaymentProvider::create([
            'company_id' => $companyId,
            'name' => 'Mollie Test inactief',
            'provider_type' => 'mollie',
            'is_active' => false,
            'config' => [
                'api_key' => Crypt::encryptString('test_abcdefghijklmnop'),
                'test_mode' => true,
            ],
        ]);

        $paymentProviders = app(PaymentProviderService::class);
        $this->assertTrue($paymentProviders->isMollieConfiguredForCompany($companyId));
        $this->assertStringStartsWith('test_', $paymentProviders->mollieApiKeyForCompany($companyId));
    }

    public function test_localhost_webhook_not_sent_to_mollie_api(): void
    {
        $company = Company::create(['name' => 'Webhook Test', 'is_active' => true]);
        $companyId = (int) $company->id;

        PaymentProvider::create([
            'company_id' => $companyId,
            'name' => 'Mollie',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString('test_1234567890abcdef'),
                'webhook_url' => 'http://localhost:8085/api/taxi/webhooks/mollie',
            ],
        ]);

        $paymentProviders = app(PaymentProviderService::class);
        $this->assertSame(
            'http://localhost:8085/api/taxi/webhooks/mollie',
            $paymentProviders->mollieWebhookUrlForCompany($companyId)
        );
        $this->assertNull($paymentProviders->mollieWebhookUrlForPayment($companyId));
        $this->assertFalse($paymentProviders->isMollieReachableWebhookUrl('http://192.168.2.32:8085/api/taxi/webhooks/mollie'));
        $this->assertTrue($paymentProviders->isMollieReachableWebhookUrl('https://example.ngrok-free.app/api/taxi/webhooks/mollie'));
    }
}
