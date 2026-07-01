<?php

namespace Tests\Unit;

use App\Models\InvoiceSetting;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRideInvoiceService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\InvoicePdfService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class TaxiRideInvoiceServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_driver_invoice_payload_does_not_throw_when_invoice_creation_fails(): void
    {
        $service = Mockery::mock(
            TaxiRideInvoiceService::class,
            [
                Mockery::mock(InvoicePdfService::class),
                Mockery::mock(EmailTemplateService::class),
                Mockery::mock(EnvService::class),
                Mockery::mock(CompanyEmailLogoService::class),
            ]
        )->makePartial();

        $ride = Mockery::mock(RideRequest::class)->makePartial();
        $ride->payment_status = RideRequest::PAYMENT_STATUS_PAID;
        $ride->customer_email = 'klant@example.com';
        $ride->customer_name = 'Klant';
        $ride->shouldReceive('fresh')->andReturnSelf();
        $ride->shouldReceive('getConnectionName')->andReturn('module_taxi');
        $ride->shouldReceive('requiresPerLegDriverPayment')->andReturn(false);

        $service->shouldReceive('findInvoiceForRide')->with($ride, Mockery::any())->andReturn(null);
        $service->shouldReceive('findInvoiceForRide')->with($ride)->andReturn(null);
        $service->shouldReceive('resolveSendableInvoiceBillingPeriod')->with($ride)->andReturn('');
        $service->shouldReceive('ensureInvoiceForPaidRide')
            ->once()
            ->with('module_taxi', $ride, false)
            ->andThrow(ValidationException::withMessages([
                'amount' => ['Geen geldig factuurbedrag voor deze rit.'],
            ]));

        $payload = $service->driverInvoicePayload($ride);

        $this->assertFalse($payload['has_invoice']);
        $this->assertNull($payload['invoice_id']);
        $this->assertNull($payload['invoice_number']);
        $this->assertSame('klant@example.com', $payload['customer_email']);
        $this->assertTrue($payload['can_send']);
    }

    public function test_preview_next_invoice_number_does_not_increment_counter(): void
    {
        $settings = new InvoiceSetting([
            'invoice_number_prefix' => 'NX',
            'invoice_number_format' => '{prefix}{year}-{number}',
            'next_invoice_number' => 42,
            'current_year' => (int) date('Y'),
        ]);

        $preview = $settings->previewNextInvoiceNumber();

        $this->assertStringEndsWith('0042', $preview);
        $this->assertSame(42, $settings->next_invoice_number);
        $this->assertSame($preview, $settings->generateInvoiceNumber());
        $this->assertSame(43, $settings->next_invoice_number);
    }

    public function test_driver_invoice_payload_uses_persisted_invoice_number(): void
    {
        $invoice = new \App\Models\Invoice([
            'invoice_number' => 'NX2026-0042',
            'status' => 'paid',
            'total_amount' => 25.00,
            'customer_email' => 'factuur@example.com',
        ]);
        $invoice->id = 99;

        $service = Mockery::mock(
            TaxiRideInvoiceService::class,
            [
                Mockery::mock(InvoicePdfService::class),
                Mockery::mock(EmailTemplateService::class),
                Mockery::mock(EnvService::class),
                Mockery::mock(CompanyEmailLogoService::class),
            ]
        )->makePartial();

        $ride = Mockery::mock(RideRequest::class)->makePartial();
        $ride->payment_status = RideRequest::PAYMENT_STATUS_PAID;
        $ride->customer_email = 'klant@example.com';
        $ride->shouldReceive('getConnectionName')->andReturn('module_taxi');
        $ride->shouldReceive('requiresPerLegDriverPayment')->andReturn(false);

        $service->shouldReceive('findInvoiceForRide')->with($ride, Mockery::any())->andReturn(null);
        $service->shouldReceive('findInvoiceForRide')->with($ride)->andReturn($invoice);
        $service->shouldReceive('resolveSendableInvoiceBillingPeriod')->with($ride)->andReturn('');

        $payload = $service->driverInvoicePayload($ride);

        $this->assertTrue($payload['has_invoice']);
        $this->assertSame('NX2026-0042', $payload['invoice_number']);
        $this->assertTrue($payload['can_send']);
    }

    public function test_ride_summary_includes_invoice_payload_without_throwing(): void
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $settings->shouldReceive('paymentOptionsForTenant')->andReturn([
            'booking' => false,
            'driver' => true,
            'mollie_configured' => true,
        ]);

        $this->app->instance(TaxiDispatchSettingsService::class, $settings);
        $this->app->instance(TaxiRidePaymentService::class, new TaxiRidePaymentService(
            $settings,
            new \App\Modules\NexaTaxi\Services\TaxiMolliePaymentService(),
            app(\App\Services\PaymentProviderService::class)
        ));

        $invoiceService = Mockery::mock(TaxiRideInvoiceService::class);
        $invoiceService->shouldReceive('driverInvoicePayload')->once()->andReturn([
            'has_invoice' => false,
            'invoice_id' => null,
            'invoice_number' => null,
            'customer_email' => null,
            'customer_name' => null,
            'total_amount' => null,
            'invoice_sent' => false,
            'can_send' => true,
        ]);
        $this->app->instance(TaxiRideInvoiceService::class, $invoiceService);

        $ride = new RideRequest([
            'status' => RideRequest::STATUS_ASSIGNED,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'passengers' => 1,
        ]);
        $ride->id = 42;

        $summary = TaxiDispatchOfferResource::rideSummary($ride);

        $this->assertSame(42, $summary['id']);
        $this->assertFalse($summary['invoice']['has_invoice']);
    }

    public function test_resolve_sendable_billing_period_prioritizes_return_invoice(): void
    {
        $service = new TaxiRideInvoiceService(
            Mockery::mock(InvoicePdfService::class),
            Mockery::mock(EmailTemplateService::class),
            Mockery::mock(EnvService::class),
            Mockery::mock(CompanyEmailLogoService::class),
        );

        $ride = Mockery::mock(RideRequest::class)->makePartial();
        $ride->shouldReceive('requiresPerLegDriverPayment')->andReturn(true);
        $ride->shouldReceive('returnPaidAmount')->andReturn(25.0);
        $ride->shouldReceive('outboundPaidAmount')->andReturn(25.0);

        $returnInvoice = new \App\Models\Invoice(['status' => 'paid']);
        $outboundInvoice = new \App\Models\Invoice(['status' => 'sent']);

        $partial = Mockery::mock($service)->makePartial();
        $partial->shouldReceive('findInvoiceForRide')
            ->with($ride, RideRequest::INVOICE_BILLING_TERUG)
            ->andReturn($returnInvoice);
        $partial->shouldReceive('findInvoiceForRide')
            ->with($ride, RideRequest::INVOICE_BILLING_HEEN)
            ->andReturn($outboundInvoice);

        $this->assertSame(
            RideRequest::INVOICE_BILLING_TERUG,
            $partial->resolveSendableInvoiceBillingPeriod($ride)
        );
    }

    public function test_resolve_sendable_billing_period_allows_outbound_after_return_sent(): void
    {
        $service = new TaxiRideInvoiceService(
            Mockery::mock(InvoicePdfService::class),
            Mockery::mock(EmailTemplateService::class),
            Mockery::mock(EnvService::class),
            Mockery::mock(CompanyEmailLogoService::class),
        );

        $ride = Mockery::mock(RideRequest::class)->makePartial();
        $ride->shouldReceive('requiresPerLegDriverPayment')->andReturn(true);
        $ride->shouldReceive('returnPaidAmount')->andReturn(null);
        $ride->shouldReceive('outboundPaidAmount')->andReturn(25.0);

        $outboundInvoice = new \App\Models\Invoice(['status' => 'paid']);

        $partial = Mockery::mock($service)->makePartial();
        $partial->shouldReceive('findInvoiceForRide')
            ->with($ride, RideRequest::INVOICE_BILLING_HEEN)
            ->andReturn($outboundInvoice);

        $this->assertSame(
            RideRequest::INVOICE_BILLING_HEEN,
            $partial->resolveSendableInvoiceBillingPeriod($ride)
        );
    }

    public function test_driver_invoice_payload_includes_leg_label_for_return_trip(): void
    {
        $invoice = new \App\Models\Invoice([
            'invoice_number' => 'NX2026-0099',
            'status' => 'paid',
            'total_amount' => 25.00,
            'customer_email' => 'factuur@example.com',
        ]);
        $invoice->id = 101;

        $service = Mockery::mock(
            TaxiRideInvoiceService::class,
            [
                Mockery::mock(InvoicePdfService::class),
                Mockery::mock(EmailTemplateService::class),
                Mockery::mock(EnvService::class),
                Mockery::mock(CompanyEmailLogoService::class),
            ]
        )->makePartial();

        $ride = Mockery::mock(RideRequest::class)->makePartial();
        $ride->customer_email = 'klant@example.com';
        $ride->shouldReceive('requiresPerLegDriverPayment')->andReturn(true);
        $ride->shouldReceive('invoiceLegLabelForBillingPeriod')->with(RideRequest::INVOICE_BILLING_HEEN)->andReturn('Heenrit');
        $ride->shouldReceive('getConnectionName')->andReturn('module_taxi');

        $service->shouldReceive('resolveSendableInvoiceBillingPeriod')->with($ride)->andReturn(RideRequest::INVOICE_BILLING_HEEN);
        $service->shouldReceive('findInvoiceForRide')->with($ride, RideRequest::INVOICE_BILLING_HEEN)->andReturn($invoice);
        $service->shouldReceive('findInvoiceForRide')->with($ride, RideRequest::INVOICE_BILLING_TERUG)->andReturn(null);

        $payload = $service->driverInvoicePayload($ride);

        $this->assertSame('heen', $payload['invoice_leg']);
        $this->assertSame('Heenrit', $payload['invoice_leg_label']);
        $this->assertTrue($payload['can_send']);
        $this->assertFalse($payload['outbound_invoice_sent']);
        $this->assertFalse($payload['return_invoice_sent']);
    }
}
