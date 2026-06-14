<?php

namespace Tests\Unit;

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

        $service->shouldReceive('findInvoiceForRide')->with($ride)->andReturn(null);
        $service->shouldReceive('ensureInvoiceForPaidRide')
            ->once()
            ->with('module_taxi', $ride, false)
            ->andThrow(ValidationException::withMessages([
                'amount' => ['Geen geldig factuurbedrag voor deze rit.'],
            ]));

        $payload = $service->driverInvoicePayload($ride);

        $this->assertFalse($payload['has_invoice']);
        $this->assertNull($payload['invoice_id']);
        $this->assertSame('klant@example.com', $payload['customer_email']);
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
}
