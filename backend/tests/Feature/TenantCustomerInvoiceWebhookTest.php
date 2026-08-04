<?php

namespace Tests\Feature;

use App\Services\PlatformBilling\TenantCustomerInvoicePaymentService;
use Tests\TestCase;

class TenantCustomerInvoiceWebhookTest extends TestCase
{
    public function test_webhook_accepts_mollie_payment_id(): void
    {
        $this->mock(TenantCustomerInvoicePaymentService::class)
            ->shouldReceive('syncPaymentFromMollie')
            ->once()
            ->with('tr_test123');

        $response = $this->postJson(route('api.tenant-customer-invoices.webhooks.mollie'), [
            'id' => 'tr_test123',
        ]);

        $response->assertOk();
    }

    public function test_webhook_rejects_missing_payment_id(): void
    {
        $response = $this->postJson(route('api.tenant-customer-invoices.webhooks.mollie'), []);

        $response->assertStatus(400);
    }
}
