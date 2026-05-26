<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\Payment;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Services\AdminPaymentOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPaymentOverviewServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function open_and_paid_status_buckets_match_module_type(): void
    {
        $this->assertContains('pending', AdminPaymentOverviewService::PAYMENT_MODULES['skillmatching']['open']);
        $this->assertContains('paid', AdminPaymentOverviewService::PAYMENT_MODULES['skillmatching']['paid']);
        $this->assertContains(RidePayment::STATUS_OPEN, AdminPaymentOverviewService::PAYMENT_MODULES['taxi']['open']);
        $this->assertContains(RidePayment::STATUS_PAID, AdminPaymentOverviewService::PAYMENT_MODULES['taxi']['paid']);
    }

    #[Test]
    public function tenant_summary_counts_skillmatching_and_taxi_separately(): void
    {
        if (! Schema::hasTable('ride_payments')) {
            $this->markTestSkipped('ride_payments required');
        }

        $taxiModule = Module::query()->firstOrCreate(
            ['name' => 'taxi'],
            ['display_name' => 'Nexa Taxi', 'installed' => true, 'active' => true]
        );
        $taxiModule->update(['installed' => true, 'active' => true]);

        $company = Company::query()->create(['name' => 'Pay Co', 'slug' => 'pay-co-'.uniqid(), 'is_active' => true]);
        $company->modules()->sync([$taxiModule->id]);

        DB::table('ride_payments')->insert([
            'ride_request_id' => 1,
            'company_id' => $company->id,
            'channel' => RidePayment::CHANNEL_DRIVER,
            'amount' => 10,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_OPEN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ride_payments')->insert([
            'ride_request_id' => 1,
            'company_id' => $company->id,
            'channel' => RidePayment::CHANNEL_DRIVER,
            'amount' => 25,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = app(AdminPaymentOverviewService::class)->summarizeCompany($company->fresh('modules'));

        $this->assertSame(1, $summary['open_count']);
        $this->assertSame(1, $summary['paid_count']);
    }

    #[Test]
    public function dashboard_financials_include_taxi_revenue_for_tenant(): void
    {
        if (! Schema::hasTable('ride_payments')) {
            $this->markTestSkipped('ride_payments required');
        }

        $taxiModule = Module::query()->firstOrCreate(
            ['name' => 'taxi'],
            ['display_name' => 'Nexa Taxi', 'installed' => true, 'active' => true]
        );
        $taxiModule->update(['installed' => true, 'active' => true]);

        $company = Company::query()->create(['name' => 'Dash Co', 'slug' => 'dash-co-'.uniqid(), 'is_active' => true]);
        $company->modules()->sync([$taxiModule->id]);

        DB::table('ride_payments')->insert([
            'ride_request_id' => 2,
            'company_id' => $company->id,
            'channel' => RidePayment::CHANNEL_DRIVER,
            'amount' => 40,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $financials = app(AdminPaymentOverviewService::class)->dashboardFinancials($company->id);

        $this->assertSame(40.0, $financials['total_revenue']);
        $this->assertSame(1, $financials['paid_payments']);
        $this->assertNull($financials['tenant_rows']);
    }

    #[Test]
    public function paid_invoices_appear_on_voldaan_when_no_payment_row_exists(): void
    {
        $taxiModule = Module::query()->firstOrCreate(
            ['name' => 'taxi'],
            [
                'display_name' => 'Nexa Taxi',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
            ]
        );
        $taxiModule->update(['installed' => true, 'active' => true]);

        $company = Company::query()->create(['name' => 'Inv Co', 'slug' => 'inv-co-'.uniqid(), 'is_active' => true]);
        $company->modules()->sync([$taxiModule->id]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TAXI-'.uniqid(),
            'company_id' => $company->id,
            'module' => Invoice::MODULE_TAXI,
            'module_reference_id' => 99,
            'amount' => 80,
            'tax_amount' => 16.8,
            'total_amount' => 96.8,
            'currency' => 'EUR',
            'status' => 'paid',
            'invoice_date' => now(),
            'due_date' => now(),
            'paid_date' => now(),
        ]);

        $paid = app(AdminPaymentOverviewService::class)->collectPaidPayments($company->id, null);

        $this->assertCount(1, $paid);
        $this->assertSame('paid', $paid->first()->status);
        $this->assertSame($invoice->invoice_number, $paid->first()->reference);
        $this->assertSame(96.8, $paid->first()->amount);
    }
}
