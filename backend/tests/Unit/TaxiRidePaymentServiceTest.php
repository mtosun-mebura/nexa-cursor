<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Mockery;
use Tests\TestCase;

class TaxiRidePaymentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_complete_when_no_payment_method(): void
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $service = new TaxiRidePaymentService(
            $settings,
            new \App\Modules\NexaTaxi\Services\TaxiMolliePaymentService(),
            app(\App\Services\PaymentProviderService::class)
        );

        $ride = new RideRequest([
            'payment_method' => null,
            'payment_status' => null,
        ]);

        $this->assertTrue($service->canCompleteRide($ride));
    }

    public function test_requires_payment_for_driver_method_until_paid(): void
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $settings->shouldReceive('paymentDriverEnabled')->with(5)->andReturn(true);

        $service = new TaxiRidePaymentService(
            $settings,
            new \App\Modules\NexaTaxi\Services\TaxiMolliePaymentService(),
            app(\App\Services\PaymentProviderService::class)
        );

        $ride = new RideRequest([
            'company_id' => 5,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
        ]);

        $this->assertFalse($service->canCompleteRide($ride));

        $ride->payment_status = RideRequest::PAYMENT_STATUS_PAID;
        $this->assertTrue($service->canCompleteRide($ride));
    }

    public function test_driver_payment_error_skipped_when_paid_or_not_driver_method(): void
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $settings->shouldReceive('paymentOptionsForTenant')->andReturn([
            'booking' => false,
            'driver' => true,
            'mollie_configured' => true,
        ]);

        $service = new TaxiRidePaymentService(
            $settings,
            new \App\Modules\NexaTaxi\Services\TaxiMolliePaymentService(),
            app(\App\Services\PaymentProviderService::class)
        );

        $paidRide = new RideRequest([
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);
        $this->assertNull($service->driverPaymentErrorMessage($paidRide));

        $bookingRide = new RideRequest([
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
        ]);
        $this->assertNull($service->driverPaymentErrorMessage($bookingRide));

        $summary = $service->paymentSummaryForRide($paidRide);
        $this->assertArrayHasKey('payment_error', $summary);
        $this->assertNull($summary['payment_error']);
    }

    public function test_cash_paid_rejects_already_paid_ride(): void
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $settings->shouldReceive('paymentDriverEnabled')->with(5)->andReturn(true);

        $service = new TaxiRidePaymentService(
            $settings,
            new \App\Modules\NexaTaxi\Services\TaxiMolliePaymentService(),
            app(\App\Services\PaymentProviderService::class)
        );

        $ride = new RideRequest([
            'id' => 1,
            'company_id' => 5,
            'driver_id' => 10,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->markDriverCashPaid('module_taxi', $ride);
    }

    public function test_validate_payment_method_auto_selects_when_only_one_enabled(): void
    {
        $settings = Mockery::mock(TaxiDispatchSettingsService::class);
        $settings->shouldReceive('paymentOptionsForTenant')->with(3)->andReturn([
            'booking' => true,
            'driver' => false,
            'mollie_configured' => true,
        ]);

        $service = new TaxiRidePaymentService(
            $settings,
            new \App\Modules\NexaTaxi\Services\TaxiMolliePaymentService(),
            app(\App\Services\PaymentProviderService::class)
        );

        $this->assertSame(
            RideRequest::PAYMENT_METHOD_BOOKING,
            $service->validatePaymentMethodChoice(null, 3)
        );
    }
}
