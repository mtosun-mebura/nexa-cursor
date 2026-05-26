<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('status', 32)->default('offered');
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

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $result['ride']->status);
        $this->assertSame($driver->id, (int) $result['ride']->driver_id);

        $otherOffer = RideDispatchOffer::on('module_taxi')
            ->where('driver_id', $other->id)
            ->first();
        $this->assertSame(RideDispatchOffer::STATUS_SUPERSEDED, $otherOffer->status);
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
}
