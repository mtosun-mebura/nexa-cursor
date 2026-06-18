<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Services\ContractInvoiceService;
use Mockery;
use Tests\TestCase;

class ContractInvoiceServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_build_line_items_for_hybrid_contract(): void
    {
        $service = new ContractInvoiceService(
            Mockery::mock(\App\Services\InvoicePdfService::class),
            Mockery::mock(\App\Services\EmailTemplateService::class),
            Mockery::mock(\App\Services\EnvService::class),
            Mockery::mock(\App\Services\CompanyEmailLogoService::class),
        );

        $contract = new TransportContract([
            'name' => 'Schoolvervoer Roombeek',
            'billing_model' => 'hybrid',
            'monthly_amount' => 500.00,
            'price_per_ride' => 12.50,
        ]);

        $lines = $service->buildLineItems($contract, '2026-06', [
            'group_rides' => 18,
            'individual_rides' => 2,
            'total_rides' => 20,
        ]);

        $this->assertCount(2, $lines);
        $this->assertSame(1, $lines[0]['quantity']);
        $this->assertSame(500.0, $lines[0]['total']);
        $this->assertStringStartsWith("Contractvervoer juni 2026\n", $lines[0]['description']);
        $this->assertStringContainsString('Abonnement: Schoolvervoer Roombeek', $lines[0]['description']);
        $this->assertStringContainsString('Periode: juni 2026', $lines[0]['description']);
        $this->assertStringContainsString('Afgeronde ritten: 20 (18 groep, 2 individueel)', $lines[0]['description']);
        $this->assertSame(20, $lines[1]['quantity']);
        $this->assertSame(250.0, $lines[1]['total']);
    }

    public function test_previous_billing_period_returns_last_month(): void
    {
        $service = new ContractInvoiceService(
            Mockery::mock(\App\Services\InvoicePdfService::class),
            Mockery::mock(\App\Services\EmailTemplateService::class),
            Mockery::mock(\App\Services\EnvService::class),
            Mockery::mock(\App\Services\CompanyEmailLogoService::class),
        );

        $period = $service->previousBillingPeriod(
            \Carbon\Carbon::create(2026, 6, 16, 12, 0, 0, 'Europe/Amsterdam')
        );

        $this->assertSame('2026-05', $period);
    }

    public function test_build_line_items_for_fixed_monthly_contract(): void
    {
        $service = new ContractInvoiceService(
            Mockery::mock(\App\Services\InvoicePdfService::class),
            Mockery::mock(\App\Services\EmailTemplateService::class),
            Mockery::mock(\App\Services\EnvService::class),
            Mockery::mock(\App\Services\CompanyEmailLogoService::class),
        );

        $contract = new TransportContract([
            'name' => 'School A',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 750.00,
        ]);

        $lines = $service->buildLineItems($contract, '2026-06', [
            'group_rides' => 20,
            'individual_rides' => 0,
            'total_rides' => 20,
        ]);

        $this->assertCount(1, $lines);
        $this->assertSame(750.0, $lines[0]['total']);
    }

    public function test_build_line_items_for_per_ride_contract(): void
    {
        $service = new ContractInvoiceService(
            Mockery::mock(\App\Services\InvoicePdfService::class),
            Mockery::mock(\App\Services\EmailTemplateService::class),
            Mockery::mock(\App\Services\EnvService::class),
            Mockery::mock(\App\Services\CompanyEmailLogoService::class),
        );

        $contract = new TransportContract([
            'name' => 'School B',
            'billing_model' => 'per_ride',
            'price_per_ride' => 15.00,
        ]);

        $lines = $service->buildLineItems($contract, '2026-06', [
            'group_rides' => 18,
            'individual_rides' => 2,
            'total_rides' => 20,
        ]);

        $this->assertCount(1, $lines);
        $this->assertSame(20, $lines[0]['quantity']);
        $this->assertSame(300.0, $lines[0]['total']);
    }
}
