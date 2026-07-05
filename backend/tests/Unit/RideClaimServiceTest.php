<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideClaimService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RideClaimServiceTest extends TestCase
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
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('transport_contract_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('ride_type', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('source', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_note')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('wave')->default(1);
            $table->timestamp('offered_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedSmallInteger('sequence');
            $table->string('stop_type', 24)->index();
            $table->unsignedBigInteger('transport_passenger_id')->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('planned_at')->nullable();
            $table->string('status', 24)->default('planned')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_accept_assigns_driver_atomically(): void
    {
        $driver = User::factory()->create();
        $other = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $other->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertSame($driver->id, (int) $result['ride']->driver_id);

        $otherOffer = RideDispatchOffer::on('module_taxi')
            ->where('driver_id', $other->id)
            ->first();
        $this->assertSame(RideDispatchOffer::STATUS_SUPERSEDED, $otherOffer->status);
    }

    public function test_start_moves_accepted_ride_to_assigned(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);
        $started = $claim->startRide('module_taxi', $driver, $ride->id);

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $started->status);
    }

    public function test_complete_marks_ride_completed_for_assigned_driver(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);
        $completed = $claim->completeRide('module_taxi', $driver, $ride->id);

        $this->assertSame(RideRequest::STATUS_COMPLETED, $completed->status);
    }

    public function test_complete_rejects_accepted_ride_without_start(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);

        $this->expectException(ValidationException::class);
        $claim->completeRide('module_taxi', $driver, $ride->id);
    }

    public function test_release_clears_driver_and_redispatches(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $claim = app(RideClaimService::class);
        $released = $claim->releaseAcceptedRide('module_taxi', $driver, $ride->id);

        $this->assertNull($released->driver_id);
        $this->assertSame(RideRequest::STATUS_PENDING_DISPATCH, $released->status);

        $driverOffer = RideDispatchOffer::on('module_taxi')
            ->where('ride_request_id', $ride->id)
            ->where('driver_id', $driver->id)
            ->first();
        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $driverOffer->status);
    }

    public function test_release_blocked_for_contract_ride(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'transport_contract_id' => 99,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
            'payment_method' => 'contract',
            'source' => 'contract',
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);

        $this->expectException(ValidationException::class);
        $claim->releaseAcceptedRide('module_taxi', $driver, $ride->id);
    }

    public function test_decline_pending_offer_marks_declined(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $declined = $claim->declineOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $declined->fresh()->status);
    }

    public function test_decline_expired_offer_marks_declined(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_EXPIRED,
            'offered_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinute(),
            'responded_at' => now()->subMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $declined = $claim->declineOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $declined->fresh()->status);
    }

    public function test_accept_declined_offer_assigns_driver(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'offered_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinute(),
            'responded_at' => now()->subMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertSame($driver->id, (int) $result['ride']->driver_id);
        $this->assertSame(RideDispatchOffer::STATUS_ACCEPTED, $result['offer']->fresh()->status);
    }

    public function test_accept_declined_overdue_ride_with_new_pickup_at(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->subHours(2),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        $newPickup = now()->addDay()->startOfMinute();
        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer(
            'module_taxi',
            $driver,
            $offer->id,
            $newPickup->toIso8601String()
        );

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertTrue($result['ride']->pickup_at->equalTo($newPickup));
    }

    public function test_accept_declined_overdue_ride_keeps_pickup_at_without_change(): void
    {
        $driver = User::factory()->create();
        $originalPickup = now()->subHours(2)->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => $originalPickup,
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer('module_taxi', $driver, $offer->id, null);

        $this->assertTrue($result['ride']->pickup_at->equalTo($originalPickup));
    }
}
