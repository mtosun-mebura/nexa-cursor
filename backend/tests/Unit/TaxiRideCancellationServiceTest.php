<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiMolliePaymentService;
use App\Modules\NexaTaxi\Services\TaxiRideCancellationService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\ModuleDatabaseService;
use App\Services\PaymentProviderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiRideCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $conn = 'module_taxi';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        config(['taxi-dispatch.unaccepted_auto_cancel_minutes' => 30]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi')->andReturnNull();
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
            $mock->shouldReceive('supportsModuleDatabases')->andReturn(true);
        });

        Schema::connection($this->conn)->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('ride_type', 40)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at')->nullable();
            $table->dateTime('pickup_proposal_at')->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('channel', 20)->nullable();
            $table->string('mollie_payment_id', 64)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 24)->nullable();
            $table->string('checkout_url', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('mollie_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 24)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function cancellationService(?TaxiMolliePaymentService $mollie = null): TaxiRideCancellationService
    {
        $mollie ??= Mockery::mock(TaxiMolliePaymentService::class);
        $payments = new TaxiRidePaymentService(
            app(TaxiDispatchSettingsService::class),
            $mollie,
            app(PaymentProviderService::class)
        );

        $status = Mockery::mock(\App\Modules\NexaTaxi\Services\TaxiCustomerRideStatusNotificationService::class);
        $status->shouldReceive('notify')->andReturn(false);
        $push = Mockery::mock(\App\Modules\NexaTaxi\Services\TaxiDriverInboxPushService::class);
        $push->shouldReceive('notifyDriver')->andReturnNull();

        return new TaxiRideCancellationService(
            app(TaxiDispatchSettingsService::class),
            $payments,
            $status,
            $push
        );
    }

    #[Test]
    public function auto_cancel_uses_default_thirty_minutes_after_pickup(): void
    {
        $service = $this->cancellationService();
        $pickup = now(ContractTransportTimezone::TIMEZONE)->subMinutes(31);

        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => ContractTransportTimezone::naiveUtcForWallClockQuery($pickup),
            'customer_name' => 'Klant',
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
        ]);

        $this->assertTrue($service->isDueForAutoCancel($ride));
    }

    #[Test]
    public function auto_cancel_waits_when_within_window(): void
    {
        $service = $this->cancellationService();
        $pickup = now(ContractTransportTimezone::TIMEZONE)->subMinutes(10);

        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => ContractTransportTimezone::naiveUtcForWallClockQuery($pickup),
            'customer_name' => 'Klant',
        ]);

        $this->assertFalse($service->isDueForAutoCancel($ride));
    }

    #[Test]
    public function auto_cancel_uses_pending_proposal_time_instead_of_old_pickup(): void
    {
        $company = Company::query()->create(['name' => 'Cancel Co', 'slug' => 'cancel-'.uniqid()]);
        GeneralSetting::set(
            TaxiDispatchSettingsService::KEY_UNACCEPTED_AUTO_CANCEL_MINUTES,
            '30',
            $company->id
        );

        $service = $this->cancellationService();
        $oldPickup = now(ContractTransportTimezone::TIMEZONE)->subHour();
        $newProposal = now(ContractTransportTimezone::TIMEZONE)->addHour();

        $ride = RideRequest::on($this->conn)->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => ContractTransportTimezone::naiveUtcForWallClockQuery($oldPickup),
            'pickup_proposal_at' => ContractTransportTimezone::naiveUtcForWallClockQuery($newProposal),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'customer_name' => 'Klant',
        ]);

        $this->assertFalse($service->isDueForAutoCancel($ride));

        $settings = app(TaxiDispatchSettingsService::class);
        $due = $settings->effectiveDispatchDueAt($ride);
        $this->assertNotNull($due);
        $this->assertSame($newProposal->format('Y-m-d H:i'), $due->format('Y-m-d H:i'));
    }

    #[Test]
    public function customer_cancel_marks_ride_cancelled_and_supersedes_offers(): void
    {
        $service = $this->cancellationService();
        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'Station',
            'dropoff_address' => 'Centrum',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
        ]);

        RideDispatchOffer::on($this->conn)->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => 9,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ]);

        $result = $service->cancelByCustomer($this->conn, $ride);

        $this->assertSame(RideRequest::STATUS_CANCELLED, $result['ride']->status);
        $this->assertSame(
            RideDispatchOffer::STATUS_SUPERSEDED,
            RideDispatchOffer::on($this->conn)->first()->status
        );
    }

    #[Test]
    public function cancel_refunds_prepaid_mollie_payment(): void
    {
        $mollie = Mockery::mock(TaxiMolliePaymentService::class);
        $mollie->shouldReceive('createRefund')->once()->andReturn([
            'id' => 're_test',
            'status' => 'refunded',
        ]);
        $mollie->shouldReceive('formatAmount')->andReturnUsing(fn ($a) => number_format($a, 2, '.', ''));

        $providers = Mockery::mock(PaymentProviderService::class);
        $providers->shouldReceive('mollieApiKeyForCompany')->andReturn('test_key');

        $payments = new TaxiRidePaymentService(
            app(TaxiDispatchSettingsService::class),
            $mollie,
            $providers
        );
        $status = Mockery::mock(\App\Modules\NexaTaxi\Services\TaxiCustomerRideStatusNotificationService::class);
        $status->shouldReceive('notify')->andReturn(false);
        $push = Mockery::mock(\App\Modules\NexaTaxi\Services\TaxiDriverInboxPushService::class);
        $push->shouldReceive('notifyDriver')->andReturnNull();

        $service = new TaxiRideCancellationService(
            app(TaxiDispatchSettingsService::class),
            $payments,
            $status,
            $push
        );

        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        RidePayment::on($this->conn)->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'channel' => RidePayment::CHANNEL_BOOKING,
            'mollie_payment_id' => 'tr_test',
            'amount' => 42.50,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $result = $service->cancelByCustomer($this->conn, $ride);

        $this->assertTrue($result['refunded']);
        $this->assertSame(RideRequest::PAYMENT_STATUS_REFUNDED, $result['ride']->payment_status);
        $this->assertSame(
            RidePayment::STATUS_REFUNDED,
            RidePayment::on($this->conn)->first()->status
        );
    }

    #[Test]
    public function accepted_ride_cannot_be_cancelled_by_customer(): void
    {
        $service = $this->cancellationService();
        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'driver_id' => 5,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        $this->assertFalse($service->isCancellableByCustomer($ride));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->cancelByCustomer($this->conn, $ride);
    }

    #[Test]
    public function settings_default_unaccepted_auto_cancel_is_thirty(): void
    {
        $service = app(TaxiDispatchSettingsService::class);
        $this->assertSame(30, $service->unacceptedAutoCancelMinutes(999991));
    }
}
