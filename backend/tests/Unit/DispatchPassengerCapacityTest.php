<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DispatchPassengerCapacityTest extends TestCase
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
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('status', 32)->default('pending_dispatch');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
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
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['ride_request_id', 'driver_id']);
        });

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('type', 20)->default('car');
            $table->string('license_plate')->nullable();
            $table->string('person_range', 10)->default('1-4');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
    }

    #[Test]
    public function a_ride_with_more_than_four_passengers_goes_only_to_five_to_eight_vehicles(): void
    {
        $company = Company::query()->create(['name' => 'Vloot', 'is_active' => true]);
        $smallDriver = User::factory()->create(['company_id' => $company->id]);
        $smallDriver->assignRole('chauffeur');
        $largeDriver = User::factory()->create(['company_id' => $company->id]);
        $largeDriver->assignRole('chauffeur');

        $small = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Auto',
            'person_range' => Vehicle::PERSON_RANGE_1_4,
            'active' => true,
        ]);
        $large = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Busje',
            'person_range' => Vehicle::PERSON_RANGE_5_8,
            'active' => true,
        ]);

        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $smallDriver->id,
            'company_id' => $company->id,
            'vehicle_id' => $small->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $largeDriver->id,
            'company_id' => $company->id,
            'vehicle_id' => $large->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'passengers' => 6,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startDispatch('module_taxi', $ride, (int) $company->id);

        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $smallDriver->id,
        ], 'module_taxi');
        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $largeDriver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
    }

    #[Test]
    public function a_ride_with_up_to_four_passengers_still_reaches_smaller_vehicles(): void
    {
        $company = Company::query()->create(['name' => 'Vloot klein', 'is_active' => true]);
        $smallDriver = User::factory()->create(['company_id' => $company->id]);
        $smallDriver->assignRole('chauffeur');
        $vehicle = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Auto',
            'person_range' => Vehicle::PERSON_RANGE_1_4,
            'active' => true,
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $smallDriver->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'passengers' => 3,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startDispatch('module_taxi', $ride, (int) $company->id);

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $smallDriver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
    }

    #[Test]
    public function a_ride_with_more_than_eight_passengers_goes_only_to_a_bus(): void
    {
        $company = Company::query()->create(['name' => 'Vloot bus', 'is_active' => true]);
        $vanDriver = User::factory()->create(['company_id' => $company->id]);
        $vanDriver->assignRole('chauffeur');
        $busDriver = User::factory()->create(['company_id' => $company->id]);
        $busDriver->assignRole('chauffeur');

        $van = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Busje',
            'person_range' => Vehicle::PERSON_RANGE_5_8,
            'active' => true,
        ]);
        $bus = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Bus',
            'person_range' => Vehicle::PERSON_RANGE_OVER_8,
            'active' => true,
        ]);

        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $vanDriver->id,
            'company_id' => $company->id,
            'vehicle_id' => $van->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $busDriver->id,
            'company_id' => $company->id,
            'vehicle_id' => $bus->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'passengers' => 10,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startDispatch('module_taxi', $ride, (int) $company->id);

        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $vanDriver->id,
        ], 'module_taxi');
        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $busDriver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
    }
}
