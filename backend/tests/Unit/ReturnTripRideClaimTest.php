<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideClaimService;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReturnTripRideClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32);
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->dateTime('return_at')->nullable();
            $table->dateTime('outbound_completed_at')->nullable();
            $table->unsignedBigInteger('outbound_driver_id')->nullable();
            $table->dateTime('return_started_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('status', 32);
            $table->unsignedSmallInteger('wave')->default(1);
            $table->dateTime('offered_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->string('stop_type', 32);
            $table->string('status', 32)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        $this->ensureCompany();
        GeneralSetting::set(TaxiDispatchSettingsService::KEY_PAYMENT_DRIVER_ENABLED, '1', 1);
    }

    private function returnRide(int $driverId, array $overrides = []): RideRequest
    {
        $this->ensureCompany();

        return RideRequest::on('module_taxi')->create(array_merge([
            'company_id' => 1,
            'driver_id' => $driverId,
            'status' => RideRequest::STATUS_ASSIGNED,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
            'pickup_address' => 'Hoofdstraat 1',
            'dropoff_address' => 'Station',
            'pickup_at' => now()->subHour(),
            'return_at' => now()->addHours(2),
            'customer_name' => 'Test Klant',
            'quoted_price' => 50,
            'booking_payload' => ['step_data' => ['return_trip' => true]],
        ], $overrides));
    }

    private function ensureCompany(): void
    {
        if (! Company::query()->whereKey(1)->exists()) {
            Company::query()->create(['id' => 1, 'name' => 'Test Co', 'slug' => 'test-co-'.uniqid()]);
        }
    }

    #[Test]
    public function completing_outbound_requires_payment_first(): void
    {
        $driver = User::factory()->create();
        $ride = $this->returnRide($driver->id);

        $service = app(RideClaimService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->completeRide('module_taxi', $driver, (int) $ride->id);
    }

    #[Test]
    public function completing_outbound_keeps_ride_assigned_and_sets_outbound_timestamp(): void
    {
        $driver = User::factory()->create();
        $ride = $this->returnRide($driver->id, [
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        $service = app(RideClaimService::class);
        $result = $service->completeRide('module_taxi', $driver, (int) $ride->id);

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $result->status);
        $this->assertNotNull($result->outbound_completed_at);
        $this->assertNull($result->return_started_at);
        $this->assertSame(RideRequest::RETURN_LEG_WAITING, $result->currentReturnLeg());
        $this->assertSame(RideRequest::PAYMENT_STATUS_NOT_REQUIRED, $result->payment_status);
    }

    #[Test]
    public function driver_can_release_return_leg_for_redispatch(): void
    {
        $driverA = User::factory()->create();
        $driverB = User::factory()->create();
        $ride = $this->returnRide($driverA->id, [
            'outbound_completed_at' => now(),
            'outbound_driver_id' => $driverA->id,
        ]);

        $this->mock(RideDispatchService::class, function ($mock): void {
            $mock->shouldReceive('startDispatch')->once();
        });

        $service = app(RideClaimService::class);
        $released = $service->releaseReturnLeg('module_taxi', $driverA, (int) $ride->id);

        $this->assertNull($released->driver_id);
        $this->assertSame(RideRequest::STATUS_PENDING_DISPATCH, $released->status);
        $this->assertNotNull($released->outbound_completed_at);
        $this->assertSame((int) $driverA->id, (int) $released->outbound_driver_id);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $released->id,
            'driver_id' => $driverB->id,
            'company_id' => 1,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $accepted = $service->acceptOffer('module_taxi', $driverB, (int) RideDispatchOffer::on('module_taxi')->first()->id);
        $this->assertSame((int) $driverB->id, (int) $accepted['ride']->driver_id);
        $this->assertSame(RideRequest::STATUS_ACCEPTED, $accepted['ride']->status);
    }

    #[Test]
    public function driver_can_accept_new_ride_while_waiting_for_return_leg(): void
    {
        $driver = User::factory()->create();
        $this->returnRide($driver->id, [
            'outbound_completed_at' => now(),
            'outbound_driver_id' => $driver->id,
        ]);

        $newRide = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => null,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
            'pickup_address' => 'Kerkstraat 2',
            'dropoff_address' => 'Luchthaven',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Andere klant',
            'quoted_price' => 30,
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $newRide->id,
            'driver_id' => $driver->id,
            'company_id' => 1,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = app(RideClaimService::class);
        $accepted = $service->acceptOffer('module_taxi', $driver, (int) $offer->id);

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $accepted['ride']->status);
        $this->assertSame((int) $driver->id, (int) $accepted['ride']->driver_id);
    }

    #[Test]
    public function driver_can_start_new_ride_while_waiting_for_return_leg(): void
    {
        $driver = User::factory()->create();
        $this->returnRide($driver->id, [
            'outbound_completed_at' => now(),
            'outbound_driver_id' => $driver->id,
        ]);

        $newRide = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
            'pickup_address' => 'Kerkstraat 2',
            'dropoff_address' => 'Luchthaven',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Andere klant',
            'quoted_price' => 30,
        ]);

        $service = app(RideClaimService::class);
        $started = $service->startRide('module_taxi', $driver, (int) $newRide->id);

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $started->status);
    }

    #[Test]
    public function waiting_return_leg_does_not_block_other_rides(): void
    {
        $driver = User::factory()->create();
        $ride = $this->returnRide($driver->id, [
            'outbound_completed_at' => now(),
            'outbound_driver_id' => $driver->id,
        ]);

        $this->assertFalse($ride->fresh()->blocksDriverFromOtherRides());
    }

    #[Test]
    public function completing_return_leg_marks_ride_completed(): void
    {
        $driver = User::factory()->create();
        $ride = $this->returnRide($driver->id, [
            'outbound_completed_at' => now()->subHour(),
            'outbound_driver_id' => $driver->id,
            'return_started_at' => now()->subMinutes(10),
        ]);

        $this->mock(TaxiRidePaymentService::class, function ($mock): void {
            $mock->shouldReceive('canCompleteRide')->andReturn(true);
        });

        $service = app(RideClaimService::class);
        $completed = $service->completeRide('module_taxi', $driver, (int) $ride->id);

        $this->assertSame(RideRequest::STATUS_COMPLETED, $completed->status);
    }
}
