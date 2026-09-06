<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformInvoice;
use App\Models\User;
use App\Services\PlatformBilling\PlatformInvoicePdfService;
use App\Services\PlatformBilling\PlatformMollieService;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformInvoiceSendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->mock(PlatformInvoicePdfService::class, function ($mock): void {
            $mock->shouldReceive('generateAndStore')->andReturn(['bytes' => '%PDF-fake', 'path' => null]);
        });
    }

    private function superAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $invoiceOverrides
     * @param  array<string, mixed>  $profileOverrides
     * @return array{0: PlatformInvoice, 1: Company}
     */
    private function invoiceWithBilling(array $invoiceOverrides = [], array $profileOverrides = []): array
    {
        $company = Company::query()->create([
            'name' => 'Send Taxi BV',
            'email' => 'office@send-taxi.test',
            'is_active' => true,
        ]);

        CompanyBillingProfile::query()->create(array_merge([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'billing_email' => 'factuur@send-taxi.test',
            'auto_collect_enabled' => false,
        ], $profileOverrides));

        $invoice = PlatformInvoice::query()->create(array_merge([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-SEND-0001',
            'billing_period' => '2026-09',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'payment_terms_days' => 14,
            'line_items' => [
                [
                    'description' => 'Business — september 2026',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'total' => 100,
                    'type' => 'subscription',
                    'billing_period' => '2026-09',
                ],
            ],
        ], $invoiceOverrides));

        return [$invoice, $company];
    }

    public function test_index_shows_versturen_action(): void
    {
        [$invoice] = $this->invoiceWithBilling();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->assertSee('Versturen', false)
            ->assertSee(route('admin.platform-billing.invoices.send', $invoice), false);
    }

    public function test_super_admin_can_send_unpaid_invoice_email(): void
    {
        [$invoice] = $this->invoiceWithBilling(['status' => 'sent']);

        $this->mock(PlatformMollieService::class, function ($mock): void {
            $mock->shouldReceive('createOneOffPayment')->once()->andReturn([
                'id' => 'tr_test_send',
                '_links' => ['checkout' => ['href' => 'https://www.mollie.com/checkout/test-send']],
            ]);
            $mock->shouldReceive('checkoutUrl')->andReturnUsing(function (array $payment) {
                return $payment['_links']['checkout']['href'] ?? null;
            });
        });

        $this->actingAs($this->superAdmin())
            ->from(route('admin.platform-billing.invoices.index'))
            ->post(route('admin.platform-billing.invoices.send', $invoice))
            ->assertRedirect(route('admin.platform-billing.invoices.index'))
            ->assertSessionHas('success');

        $fresh = $invoice->fresh();
        $this->assertNotNull($fresh->sent_at);
        $this->assertSame('tr_test_send', $fresh->mollie_payment_id);
        $this->assertDatabaseHas('platform_payments', [
            'platform_invoice_id' => $invoice->id,
            'mollie_payment_id' => 'tr_test_send',
        ]);
    }

    public function test_super_admin_can_send_paid_invoice_email(): void
    {
        [$invoice] = $this->invoiceWithBilling([
            'status' => 'paid',
            'paid_at' => now(),
            'sent_at' => now()->subDay(),
            'mollie_payment_id' => null,
        ]);

        $this->mock(PlatformMollieService::class, function ($mock): void {
            $mock->shouldReceive('createOneOffPayment')->never();
        });

        $previousSentAt = $invoice->sent_at?->toDateTimeString();

        $this->actingAs($this->superAdmin())
            ->from(route('admin.platform-billing.invoices.show', $invoice))
            ->post(route('admin.platform-billing.invoices.send', $invoice))
            ->assertRedirect(route('admin.platform-billing.invoices.show', $invoice))
            ->assertSessionHas('success');

        $fresh = $invoice->fresh();
        $this->assertNotNull($fresh->sent_at);
        $this->assertNotSame($previousSentAt, $fresh->sent_at?->toDateTimeString());
        $this->assertNull($fresh->mollie_payment_id);
    }

    public function test_send_fails_without_billing_email(): void
    {
        $company = Company::query()->create([
            'name' => 'Geen Mail BV',
            'email' => null,
            'is_active' => true,
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'billing_email' => null,
            'auto_collect_enabled' => false,
        ]);

        $invoice = PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-SEND-0002',
            'billing_period' => '2026-09',
            'amount' => 50,
            'tax_amount' => 10.5,
            'total_amount' => 60.5,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'payment_terms_days' => 14,
            'line_items' => [],
        ]);

        $this->actingAs($this->superAdmin())
            ->from(route('admin.platform-billing.invoices.index'))
            ->post(route('admin.platform-billing.invoices.send', $invoice))
            ->assertRedirect(route('admin.platform-billing.invoices.index'))
            ->assertSessionHas('error');

        Mail::assertNothingOutgoing();
    }
}
