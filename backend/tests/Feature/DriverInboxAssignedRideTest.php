<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\DriverDispatchController;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverInboxAssignedRideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.module_taxi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'taxi-dispatch.overdue_auto_archive_days' => 2,
            'taxi-dispatch.offer_ttl_seconds' => 300,
        ]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('fulfilling_company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('transport_contract_id')->nullable();
            $table->string('status', 32)->default('pending_dispatch');
            $table->string('source', 32)->nullable();
            $table->string('ride_type', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->json('booking_payload')->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->dateTime('pickup_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->dateTime('pickup_proposal_at')->nullable();
            $table->timestamp('pickup_proposal_sent_at')->nullable();
            $table->timestamp('pickup_proposal_responded_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('wave')->default(1);
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
    }

    #[Test]
    public function accepted_marketplace_ride_stays_on_the_driver_home_after_the_archive_window(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Tosun', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');

        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.89,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $accepted = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'ride_type' => 'standard',
            'status' => RideRequest::STATUS_ACCEPTED,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'payment_method' => 'booking',
            'pickup_address' => 'Damrak 1',
            'dropoff_address' => 'Centraal',
            'pickup_lat' => 52.37,
            'pickup_lng' => 4.89,
            'pickup_at' => now('Europe/Amsterdam')->subDays(5)->format('Y-m-d H:i:s'),
            'customer_name' => 'Klant',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => ['channel' => 'customer_app', 'radius_km' => 50],
            ],
        ]);

        $offered = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'ride_type' => 'standard',
            'status' => RideRequest::STATUS_OFFERED,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'pickup_address' => 'Leidseplein',
            'dropoff_address' => 'Museumplein',
            'pickup_at' => now('Europe/Amsterdam')->addHour()->format('Y-m-d H:i:s'),
            'customer_name' => 'Volgende klant',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => ['channel' => 'customer_app'],
            ],
        ]);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $offered->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $request = Request::create('/api/taxi/v1/driver/dispatch/inbox', 'GET');
        $request->setUserResolver(fn () => $driver);
        $request->attributes->set('taxi_company_id', $company->id);

        $response = app(DriverDispatchController::class)->inbox(
            $request,
            app(ModuleDatabaseService::class),
            app(RideDispatchService::class)
        );

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true)['data'];

        $visibleIds = collect($data['overdue_scheduled_rides'] ?? [])
            ->pluck('id')
            ->merge(collect($data['scheduled_rides'] ?? [])->pluck('id'))
            ->merge(collect($data['parked_assigned_rides'] ?? [])->pluck('id'))
            ->when(
                ! empty($data['active_ride']['id']),
                fn ($ids) => $ids->push($data['active_ride']['id'])
            )
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertContains((int) $accepted->id, $visibleIds);
        $this->assertNotContains(
            (int) $accepted->id,
            collect($data['archived_overdue_scheduled_rides'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $offerRideIds = collect($data['offers'] ?? [])->pluck('ride.id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains((int) $offered->id, $offerRideIds);
    }
}
