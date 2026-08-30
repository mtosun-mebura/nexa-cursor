<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Services\PlatformBilling\PlatformDunningService;
use App\Services\PlatformBilling\PlatformInvoicePdfService;
use App\Services\PlatformBilling\PlatformMollieService;
use App\Services\PlatformBilling\TenantBillingAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformDunningServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-02-01 10:00:00'));
        PlatformBillingSetting::current()->update([
            'payment_terms_days' => 14,
            'dunning_first_interval_days' => 1,
            'dunning_interval_days' => 14,
        ]);

        $this->mock(PlatformInvoicePdfService::class, function ($mock): void {
            $mock->shouldReceive('generateAndStore')->andReturn(['bytes' => '%PDF-fake', 'path' => null]);
        });
        $this->mock(PlatformMollieService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(false);
            $mock->shouldReceive('listCustomerPayments')->andReturn([]);
            $mock->shouldReceive('listSubscriptionPayments')->andReturn([]);
            $mock->shouldReceive('fetchPayment')->andReturn(null);
            $mock->shouldReceive('mapStatus')->andReturn('pending');
            $mock->shouldReceive('fetchCustomerMandates')->andReturn([]);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sends_first_reminder_after_first_interval(): void
    {
        $invoice = $this->makeUnpaidInvoice();

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_FIRST, $action);
        $this->assertNotNull($invoice->fresh()->first_reminder_sent_at);
        $this->assertNull($invoice->fresh()->second_reminder_sent_at);
    }

    public function test_does_not_send_first_reminder_before_first_interval(): void
    {
        PlatformBillingSetting::current()->update([
            'dunning_first_interval_days' => 20,
            'dunning_interval_days' => 7,
        ]);

        $invoice = $this->makeUnpaidInvoice([
            'due_date' => '2026-01-15',
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_NONE, $action);
        $this->assertNull($invoice->fresh()->first_reminder_sent_at);
    }

    public function test_does_not_remind_before_due_date(): void
    {
        $invoice = $this->makeUnpaidInvoice([
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-03',
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_NONE, $action);
        $this->assertNull($invoice->fresh()->first_reminder_sent_at);
    }

    public function test_sends_second_reminder_after_dunning_interval(): void
    {
        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-18 09:00:00',
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_SECOND, $action);
        $this->assertNotNull($invoice->fresh()->second_reminder_sent_at);
        $this->assertNull($invoice->fresh()->blocked_at);
    }

    public function test_uses_second_interval_not_first_for_second_reminder(): void
    {
        PlatformBillingSetting::current()->update([
            'payment_terms_days' => 14,
            'dunning_first_interval_days' => 14,
            'dunning_interval_days' => 7,
        ]);

        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-25 09:00:00',
            'payment_terms_days' => 14,
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_SECOND, $action);
        $this->assertNotNull($invoice->fresh()->second_reminder_sent_at);
    }

    public function test_does_not_send_second_reminder_before_dunning_interval(): void
    {
        PlatformBillingSetting::current()->update([
            'payment_terms_days' => 7,
            'dunning_interval_days' => 14,
        ]);

        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-25 09:00:00',
            'payment_terms_days' => 7,
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_NONE, $action);
        $this->assertNull($invoice->fresh()->second_reminder_sent_at);
    }

    public function test_blocks_bookings_after_second_reminder_term(): void
    {
        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-04 09:00:00',
            'second_reminder_sent_at' => '2026-01-18 09:00:00',
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_BLOCK, $action);
        $invoice->refresh();
        $this->assertNotNull($invoice->blocked_at);
        $profile = CompanyBillingProfile::query()->where('company_id', $invoice->company_id)->first();
        $this->assertSame(TenantBillingAccessService::BOOKINGS, $profile->access_restriction);
        $this->assertSame(TenantBillingAccessService::SOURCE_DUNNING, $profile->access_restriction_source);
    }

    public function test_blocks_fully_when_tenant_mode_is_full(): void
    {
        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-04 09:00:00',
            'second_reminder_sent_at' => '2026-01-18 09:00:00',
        ], overdueBlockMode: TenantBillingAccessService::FULL);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_BLOCK, $action);
        $profile = CompanyBillingProfile::query()->where('company_id', $invoice->company_id)->first();
        $this->assertSame(TenantBillingAccessService::FULL, $profile->access_restriction);
    }

    public function test_does_not_block_when_waived(): void
    {
        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-04 09:00:00',
            'second_reminder_sent_at' => '2026-01-18 09:00:00',
            'block_waived_at' => '2026-01-31 12:00:00',
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_NONE, $action);
        $this->assertNull($invoice->fresh()->blocked_at);
    }

    public function test_marks_paid_from_mollie_and_skips_reminder(): void
    {
        $this->mock(PlatformMollieService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('fetchPayment')->andReturn([
                'id' => 'tr_paid_1',
                'status' => 'paid',
                'customerId' => 'cst_1',
                'amount' => ['value' => '121.00'],
                'metadata' => [],
            ]);
            $mock->shouldReceive('mapStatus')->with('paid')->andReturn('paid');
            $mock->shouldReceive('listCustomerPayments')->andReturn([]);
            $mock->shouldReceive('listSubscriptionPayments')->andReturn([]);
            $mock->shouldReceive('fetchCustomerMandates')->andReturn([]);
        });

        $invoice = $this->makeUnpaidInvoice(['mollie_payment_id' => 'tr_paid_1']);
        PlatformPayment::query()->create([
            'company_id' => $invoice->company_id,
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPayment::TYPE_INVOICE,
            'mollie_payment_id' => 'tr_paid_1',
            'amount' => 121,
            'currency' => 'EUR',
            'status' => 'pending',
        ]);

        $action = app(PlatformDunningService::class)->processInvoice($invoice, now());

        $this->assertSame(PlatformDunningService::ACTION_PAID, $action);
        $this->assertTrue($invoice->fresh()->isPaid());
        $this->assertNull($invoice->fresh()->first_reminder_sent_at);
    }

    public function test_clears_dunning_block_when_invoice_is_paid(): void
    {
        $invoice = $this->makeUnpaidInvoice([
            'first_reminder_sent_at' => '2026-01-04 09:00:00',
            'second_reminder_sent_at' => '2026-01-18 09:00:00',
            'blocked_at' => '2026-02-01 08:00:00',
        ]);
        $profile = CompanyBillingProfile::query()->where('company_id', $invoice->company_id)->first();
        $profile->update([
            'access_restriction' => TenantBillingAccessService::BOOKINGS,
            'access_restriction_source' => TenantBillingAccessService::SOURCE_DUNNING,
            'access_restricted_at' => now(),
            'access_restricted_invoice_id' => $invoice->id,
        ]);

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        app(PlatformDunningService::class)->clearRestrictionIfSettled((int) $invoice->company_id);

        $profile->refresh();
        $this->assertSame(TenantBillingAccessService::NONE, $profile->access_restriction);
        $this->assertNull($profile->access_restricted_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUnpaidInvoice(array $overrides = [], string $overdueBlockMode = TenantBillingAccessService::BOOKINGS): PlatformInvoice
    {
        $company = Company::query()->create([
            'name' => 'Dunning Taxi '.$overdueBlockMode.uniqid(),
            'email' => 'billing-'.uniqid().'@example.test',
            'is_active' => true,
        ]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_CUSTOM,
            'custom_monthly_amount' => 100,
            'billing_email' => $company->email,
            'overdue_block_mode' => $overdueBlockMode,
        ]);

        return PlatformInvoice::query()->create(array_merge([
            'company_id' => $company->id,
            'invoice_number' => 'SAAS-DUN-'.uniqid(),
            'billing_period' => '2026-01',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'payment_terms_days' => 14,
            'sent_at' => '2026-01-01 08:00:00',
            'line_items' => [],
        ], $overrides));
    }
}
