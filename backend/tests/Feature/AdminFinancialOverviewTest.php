<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\FinancialOverviews\FinancialOverviewPeriod;
use App\Services\FinancialOverviews\TenantFinancialOverviewService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class AdminFinancialOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_admin_can_open_overzichten(): void
    {
        [$user] = $this->companyAdmin();

        $this->actingAs($user)
            ->get(route('admin.payments.overzichten'))
            ->assertOk()
            ->assertSee('Overzichten', false)
            ->assertSee('Facturen', false)
            ->assertSee('Inkomsten', false);
    }

    #[Test]
    public function company_admin_sees_overzichten_under_betalingen(): void
    {
        [$user] = $this->companyAdmin();

        $html = $this->actingAs($user)
            ->get(route('admin.payments.overzichten'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Facturen', $html);
        $this->assertStringContainsString('Inkomsten', $html);
        $this->assertStringContainsString('BTW-overzicht', $html);
        $this->assertStringContainsString('Factuurregister', $html);
        $this->assertStringContainsString('1e kwartaal', $html);
        $this->assertStringContainsString('data-overview-preview-url', $html);
    }

    #[Test]
    public function staff_cannot_open_overzichten(): void
    {
        $company = Company::query()->create(['name' => 'Staff Co', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['staff']);

        $this->actingAs($user)
            ->get(route('admin.payments.overzichten'))
            ->assertForbidden();
    }

    #[Test]
    public function company_admin_downloads_income_pdf_for_own_tenant(): void
    {
        [$user, $company] = $this->companyAdmin();
        $this->invoice($company, 'OWN-1', '2026-04-10', 'paid', '2026-04-12');

        $this->actingAs($user)
            ->post(route('admin.payments.overzichten.download'), [
                'report_type' => TenantFinancialOverviewService::REPORT_INCOME,
                'period_type' => 'quarter',
                'year' => 2026,
                'quarter' => 2,
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function company_admin_downloads_invoice_zip_for_period(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive ontbreekt.');
        }

        [$user, $company] = $this->companyAdmin();
        $this->invoice($company, 'OWN-ZIP-1', '2026-04-10', 'sent');

        $response = $this->actingAs($user)
            ->post(route('admin.payments.overzichten.download'), [
                'report_type' => TenantFinancialOverviewService::REPORT_INVOICES,
                'period_type' => 'quarter',
                'year' => 2026,
                'quarter' => 2,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('zip', strtolower((string) $response->headers->get('content-type')));

        $tmp = tempnam(sys_get_temp_dir(), 'nexa-zip-test-');
        file_put_contents($tmp, $response->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $this->assertGreaterThanOrEqual(1, $zip->numFiles);
        $this->assertStringEndsWith('.pdf', $zip->getNameIndex(0));
        $zip->close();
        @unlink($tmp);
    }

    #[Test]
    public function company_admin_cannot_include_other_tenant_invoices(): void
    {
        [$user, $company] = $this->companyAdmin();
        $other = Company::query()->create(['name' => 'Andere', 'is_active' => true]);
        $this->invoice($other, 'OTHER-1', '2026-04-10', 'paid', '2026-04-11');

        $html = $this->actingAs($user)
            ->get(route('admin.payments.overzichten', [
                'period_type' => 'quarter',
                'year' => 2026,
                'quarter' => 2,
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Facturen: 0', $html);
        $this->assertSame($company->id, $user->company_id);
    }

    #[Test]
    public function company_admin_gets_live_preview_counts_as_json(): void
    {
        [$user, $company] = $this->companyAdmin();
        $this->invoice($company, 'Q3-1', '2026-08-10', 'paid', '2026-08-11');

        $this->actingAs($user)
            ->getJson(route('admin.payments.overzichten.preview', [
                'period_type' => 'quarter',
                'year' => 2026,
                'quarter' => 3,
            ]))
            ->assertOk()
            ->assertJsonPath('invoices', 1)
            ->assertJsonPath('income', 1)
            ->assertJsonPath('audit', 1)
            ->assertJsonPath('label', '3e kwartaal 2026')
            ->assertJsonPath('start', '01-07-2026')
            ->assertJsonPath('end', '30-09-2026');
    }

    #[Test]
    public function staff_cannot_preview_overzichten(): void
    {
        $company = Company::query()->create(['name' => 'Staff Preview Co', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['staff']);

        $this->actingAs($user)
            ->getJson(route('admin.payments.overzichten.preview', [
                'period_type' => 'month',
                'year' => 2026,
                'month' => 8,
            ]))
            ->assertForbidden();
    }

    #[Test]
    public function invoices_for_period_include_contract_and_mollie_ride_invoices(): void
    {
        [$user, $company] = $this->companyAdmin();
        $this->setupTaxiPayments();

        $this->invoice($company, 'CONTRACT-1', '2026-04-10', 'sent', null, Invoice::MODULE_TAXI_CONTRACT);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_at' => '2026-04-08 09:00:00',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'customer_name' => 'Ritklant',
            'quoted_price' => 42.50,
            'final_price' => 42.50,
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);
        RidePayment::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => $company->id,
            'channel' => RidePayment::CHANNEL_BOOKING,
            'mollie_payment_id' => 'tr_overview_ride_1',
            'amount' => 42.50,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => '2026-04-08 09:15:00',
        ]);

        $cashRide = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_at' => '2026-04-09 10:00:00',
            'pickup_address' => 'C',
            'dropoff_address' => 'D',
            'customer_name' => 'Contant',
            'quoted_price' => 20,
            'final_price' => 20,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);
        RidePayment::on('module_taxi')->create([
            'ride_request_id' => $cashRide->id,
            'company_id' => $company->id,
            'channel' => RidePayment::CHANNEL_CASH,
            'mollie_payment_id' => null,
            'amount' => 20,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => '2026-04-09 10:05:00',
        ]);

        $period = FinancialOverviewPeriod::fromInput([
            'period_type' => 'quarter',
            'year' => 2026,
            'quarter' => 2,
        ]);
        $invoices = app(TenantFinancialOverviewService::class)
            ->invoicesForPeriod((int) $company->id, $period);

        $this->assertCount(2, $invoices);
        $this->assertTrue($invoices->contains(fn (Invoice $invoice) => $invoice->invoice_number === 'CONTRACT-1'));
        $this->assertTrue($invoices->contains(
            fn (Invoice $invoice) => $invoice->module === Invoice::MODULE_TAXI
                && (int) $invoice->module_reference_id === (int) $ride->id
        ));
        $this->assertFalse($invoices->contains(
            fn (Invoice $invoice) => (int) $invoice->module_reference_id === (int) $cashRide->id
        ));
    }

    #[Test]
    public function invoices_for_period_include_existing_ride_invoice_when_mollie_paid_in_period(): void
    {
        [$user, $company] = $this->companyAdmin();
        $this->setupTaxiPayments();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_at' => '2026-03-20 09:00:00',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'customer_name' => 'Ritklant',
            'quoted_price' => 30,
            'final_price' => 30,
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);
        $existing = $this->invoice($company, 'RIT-OLD', '2026-03-20', 'paid', '2026-03-20', Invoice::MODULE_TAXI);
        $existing->update(['module_reference_id' => $ride->id]);
        $ride->update(['invoice_id' => $existing->id]);

        RidePayment::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => $company->id,
            'channel' => RidePayment::CHANNEL_BOOKING,
            'mollie_payment_id' => 'tr_overview_ride_old',
            'amount' => 30,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => '2026-04-02 12:00:00',
        ]);

        $period = FinancialOverviewPeriod::fromInput([
            'period_type' => 'month',
            'year' => 2026,
            'month' => 4,
        ]);
        $invoices = app(TenantFinancialOverviewService::class)
            ->invoicesForPeriod((int) $company->id, $period);

        $this->assertTrue($invoices->contains(fn (Invoice $invoice) => $invoice->id === $existing->id));
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function companyAdmin(): array
    {
        $company = Company::query()->create([
            'name' => 'Overzicht Tenant '.uniqid(),
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return [$user, $company];
    }

    private function invoice(
        Company $company,
        string $number,
        string $invoiceDate,
        string $status,
        ?string $paidDate = null,
        string $module = Invoice::MODULE_CUSTOMER
    ): Invoice {
        return Invoice::query()->create([
            'invoice_number' => $number,
            'company_id' => $company->id,
            'module' => $module,
            'customer_name' => 'Klant Test',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => $status,
            'invoice_date' => $invoiceDate,
            'due_date' => $invoiceDate,
            'paid_date' => $paidDate,
            'line_items' => [],
        ]);
    }

    private function setupTaxiPayments(): void
    {
        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('status', 32)->default('completed');
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 20)->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('channel', 20);
            $table->string('mollie_payment_id', 64)->nullable();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 24)->default('open');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
}
