<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\PaymentProvider;
use App\Services\PaymentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TenantMollieProviderResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_invoice_webhook_uses_active_provider_webhook(): void
    {
        $company = Company::create(['name' => 'Tenant Mollie BV', 'is_active' => true]);

        PaymentProvider::query()->create([
            'company_id' => $company->id,
            'name' => 'Mollie Tenant',
            'provider_type' => 'mollie',
            'is_active' => true,
            'config' => [
                'api_key' => Crypt::encryptString('test_tenantkey123456789'),
                'webhook_url' => 'https://tenant.example/api/tenant-customer-invoices/webhooks/mollie',
                'test_mode' => true,
            ],
        ]);

        $service = app(PaymentProviderService::class);

        $this->assertSame(
            'https://tenant.example/api/tenant-customer-invoices/webhooks/mollie',
            $service->mollieWebhookUrlForTenantInvoices($company->id)
        );
        $this->assertSame('test_tenantkey123456789', $service->mollieApiKeyForCompany($company->id));
    }

    public function test_inactive_provider_is_not_used_for_tenant_payments(): void
    {
        $company = Company::create(['name' => 'Inactive Mollie BV', 'is_active' => true]);

        PaymentProvider::query()->create([
            'company_id' => $company->id,
            'name' => 'Mollie Inactive',
            'provider_type' => 'mollie',
            'is_active' => false,
            'config' => [
                'api_key' => Crypt::encryptString('test_inactivekey1234567'),
                'webhook_url' => 'https://tenant.example/webhooks/mollie',
                'test_mode' => true,
            ],
        ]);

        config(['taxi-dispatch.allow_mollie_test_providers' => false]);

        $service = app(PaymentProviderService::class);

        $this->assertNull($service->mollieApiKeyForCompany($company->id));
        $this->assertNull($service->getMollieForCompany($company->id));
    }
}
