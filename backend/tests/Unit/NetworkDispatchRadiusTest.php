<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NetworkDispatchRadiusTest extends TestCase
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
            $table->unsignedBigInteger('fulfilling_company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('status', 32)->default('pending_dispatch');
            $table->string('source', 32)->nullable();
            $table->json('booking_payload')->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
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
            $table->boolean('active')->default(true);
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
    public function haversine_and_radius_helpers_work(): void
    {
        $eligibility = app(TaxiDriverEligibilityService::class);

        // ~0 km
        $this->assertLessThan(0.05, $eligibility->haversineKm(52.3700, 4.8900, 52.3700, 4.8900));
        // ~11 km north
        $km = $eligibility->haversineKm(52.3700, 4.8900, 52.4700, 4.8900);
        $this->assertGreaterThan(10, $km);
        $this->assertLessThan(12, $km);

        $this->assertTrue($eligibility->isWithinRadiusKm(52.37, 4.89, 52.37, 4.89, 25));
        $this->assertFalse($eligibility->isWithinRadiusKm(52.37, 4.89, 52.70, 4.89, 25));
    }

    #[Test]
    public function network_partner_dispatch_only_offers_drivers_within_max_radius(): void
    {
        $owner = Company::query()->create(['name' => 'Owner', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner', 'is_active' => true]);

        $settings = app(TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_MANUAL, $owner->id);
        $settings->setNetworkPartnerCompanyIds([(int) $partner->id], $owner->id);
        $settings->setNetworkMaxRadiusKm(10, $owner->id);

        $near = User::factory()->create(['company_id' => $partner->id]);
        $near->assignRole('chauffeur');
        $far = User::factory()->create(['company_id' => $partner->id]);
        $far->assignRole('chauffeur');

        // Pickup Amsterdam
        $pickupLat = 52.3700;
        $pickupLng = 4.8900;

        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $near->id,
            'company_id' => $partner->id,
            'is_online' => true,
            'lat' => 52.3750, // ~0.5 km
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $far->id,
            'company_id' => $partner->id,
            'is_online' => true,
            'lat' => 52.5200, // ~16+ km
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_lat' => $pickupLat,
            'pickup_lng' => $pickupLng,
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startNetworkPartnerDispatch('module_taxi', $ride);

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $near->id,
            'company_id' => $partner->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');

        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $far->id,
        ], 'module_taxi');
    }

    #[Test]
    public function own_website_ride_stays_on_fleet_while_a_vehicle_is_free(): void
    {
        $owner = Company::query()->create(['name' => 'Owner Auto', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner Auto', 'is_active' => true]);
        $marketplace = Company::query()->create(['name' => 'Marketplace Other', 'is_active' => true]);

        $settings = app(TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_AUTO, $owner->id);
        $settings->setNetworkPartnerCompanyIds([(int) $partner->id], $owner->id);
        $settings->setNetworkMaxRadiusKm(25, $owner->id);
        $settings->setNetworkFallbackSeconds(120, $owner->id);

        $freeVehicle = \App\Modules\NexaTaxi\Models\Vehicle::on('module_taxi')->create([
            'company_id' => $owner->id,
            'name' => 'Vrij',
            'active' => true,
        ]);
        \App\Modules\NexaTaxi\Models\Vehicle::on('module_taxi')->create([
            'company_id' => $owner->id,
            'name' => 'Bezet',
            'active' => true,
        ]);

        $ownDriver = User::factory()->create(['company_id' => $owner->id]);
        $ownDriver->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $ownDriver->id,
            'company_id' => $owner->id,
            'vehicle_id' => $freeVehicle->id,
            'is_online' => true,
            'lat' => 52.5200,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $partnerDriver = User::factory()->create(['company_id' => $partner->id]);
        $partnerDriver->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $partnerDriver->id,
            'company_id' => $partner->id,
            'is_online' => true,
            'lat' => 52.3700,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $otherDriver = User::factory()->create(['company_id' => $marketplace->id]);
        $otherDriver->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $otherDriver->id,
            'company_id' => $marketplace->id,
            'is_online' => true,
            'lat' => 52.3700,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'source' => RideRequest::SOURCE_BOOKING,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_lat' => 52.3700,
            'pickup_lng' => 4.8900,
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
            'booking_payload' => [
                'marketplace' => [
                    'radius_km' => 10,
                    'candidate_company_ids' => [(int) $owner->id, (int) $marketplace->id],
                ],
            ],
        ]);

        app(RideDispatchService::class)->startDispatchForCompanies(
            'module_taxi',
            $ride,
            [(int) $owner->id, (int) $marketplace->id],
            true
        );

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $ownDriver->id,
            'company_id' => $owner->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');

        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $partnerDriver->id,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $otherDriver->id,
        ], 'module_taxi');

        \Illuminate\Support\Facades\Cache::flush();
        app(RideDispatchService::class)->escalateWaitingRide('module_taxi', (int) $ride->id);

        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $partnerDriver->id,
        ], 'module_taxi');
    }

    #[Test]
    public function network_dispatch_skips_when_pickup_coords_missing(): void
    {
        $owner = Company::query()->create(['name' => 'Owner NoCoords', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner NoCoords', 'is_active' => true]);

        $settings = app(TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_MANUAL, $owner->id);
        $settings->setNetworkPartnerCompanyIds([(int) $partner->id], $owner->id);
        $settings->setNetworkMaxRadiusKm(25, $owner->id);

        $driver = User::factory()->create(['company_id' => $partner->id]);
        $driver->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driver->id,
            'company_id' => $partner->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.89,
            'last_seen_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startNetworkPartnerDispatch('module_taxi', $ride);

        $this->assertSame(0, RideDispatchOffer::on('module_taxi')->where('ride_request_id', $ride->id)->count());
    }

    #[Test]
    public function own_fleet_dispatch_reaches_online_driver_outside_default_marketplace_radius(): void
    {
        $owner = Company::query()->create(['name' => 'Owner Fleet', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $owner->id]);
        $driver->assignRole('chauffeur');

        // ~16 km van de pickup; de marktplaats-standaardstraal is 10 km.
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driver->id,
            'company_id' => $owner->id,
            'is_online' => true,
            'lat' => 52.5200,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Dam',
            'dropoff_address' => 'Station',
            'pickup_lat' => 52.3700,
            'pickup_lng' => 4.8900,
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startDispatchForCompanies(
            'module_taxi',
            $ride,
            [(int) $owner->id],
            true
        );

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $driver->id,
            'company_id' => $owner->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
    }

    #[Test]
    public function own_website_ride_reaches_network_only_when_every_own_vehicle_is_busy(): void
    {
        $owner = Company::query()->create(['name' => 'Owner Full', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner Full', 'is_active' => true]);

        $settings = app(TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_AUTO, $owner->id);
        $settings->setNetworkPartnerCompanyIds([(int) $partner->id], $owner->id);
        $settings->setNetworkMaxRadiusKm(10, $owner->id);

        $vehicle = \App\Modules\NexaTaxi\Models\Vehicle::on('module_taxi')->create([
            'company_id' => $owner->id,
            'name' => 'Enige auto',
            'active' => true,
        ]);

        $ownDriver = User::factory()->create(['company_id' => $owner->id]);
        $ownDriver->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $ownDriver->id,
            'company_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.5200,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'driver_id' => $ownDriver->id,
            'vehicle_id' => $vehicle->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'source' => RideRequest::SOURCE_BOOKING,
            'pickup_address' => 'Onderweg',
            'dropoff_address' => 'Bestemming',
            'pickup_lat' => 52.40,
            'pickup_lng' => 4.90,
            'pickup_at' => now()->subMinutes(10),
            'customer_name' => 'Bezet',
        ]);

        $nearPartner = User::factory()->create(['company_id' => $partner->id]);
        $nearPartner->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $nearPartner->id,
            'company_id' => $partner->id,
            'is_online' => true,
            'lat' => 52.3750,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $farPartner = User::factory()->create(['company_id' => $partner->id]);
        $farPartner->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $farPartner->id,
            'company_id' => $partner->id,
            'is_online' => true,
            'lat' => 52.5200,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'source' => RideRequest::SOURCE_BOOKING,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Dam',
            'dropoff_address' => 'Station',
            'pickup_lat' => 52.3700,
            'pickup_lng' => 4.8900,
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        app(RideDispatchService::class)->startDispatchForCompanies(
            'module_taxi',
            $ride,
            [(int) $owner->id],
            true
        );

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $ownDriver->id,
            'company_id' => $owner->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $nearPartner->id,
            'company_id' => $partner->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $farPartner->id,
        ], 'module_taxi');
    }

    #[Test]
    public function customer_app_ride_is_marketplace_within_configured_radius(): void
    {
        $nearCompany = Company::query()->create(['name' => 'Near Tenant', 'is_active' => true]);
        $farCompany = Company::query()->create(['name' => 'Far Tenant', 'is_active' => true]);

        $near = User::factory()->create(['company_id' => $nearCompany->id]);
        $near->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $near->id,
            'company_id' => $nearCompany->id,
            'is_online' => true,
            'lat' => 52.3750,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $far = User::factory()->create(['company_id' => $farCompany->id]);
        $far->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $far->id,
            'company_id' => $farCompany->id,
            'is_online' => true,
            'lat' => 52.5200,
            'lng' => 4.8900,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'App pickup',
            'dropoff_address' => 'App dropoff',
            'pickup_lat' => 52.3700,
            'pickup_lng' => 4.8900,
            'pickup_at' => now()->addHour(),
            'customer_name' => 'App-klant',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'channel' => 'customer_app',
                    'radius_km' => 10,
                    'candidate_company_ids' => [(int) $nearCompany->id, (int) $farCompany->id],
                ],
                'step_data' => ['app' => 'customer'],
            ],
        ]);

        $this->assertTrue($ride->isCustomerAppMarketplaceBooking());
        $this->assertFalse($ride->isOwnTenantFleetRide());

        app(RideDispatchService::class)->startDispatchForCompanies(
            'module_taxi',
            $ride,
            [(int) $nearCompany->id, (int) $farCompany->id],
            false
        );

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $near->id,
            'company_id' => $nearCompany->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $far->id,
        ], 'module_taxi');
        $this->assertNull($ride->fresh()->company_id);
    }

    #[Test]
    public function marketplace_offer_reaches_online_driver_with_stale_ping_finished_ride_and_contract_role(): void
    {
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur-inkomsten', 'guard_name' => 'web']);

        $company = Company::query()->create(['name' => 'Dual Role Taxi', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');
        $driver->assignRole('chauffeur-inkomsten');
        $driver->assignRole('contractant');

        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.3720,
            'lng' => 4.8910,
            'last_seen_at' => now()->subMinutes(30),
            'location_updated_at' => now()->subMinutes(30),
        ]);

        RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'Klaar',
            'dropoff_address' => 'Klaar',
            'pickup_lat' => 52.37,
            'pickup_lng' => 4.89,
            'pickup_at' => now()->subHours(5),
            'customer_name' => 'Vorige klant',
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Nu',
            'dropoff_address' => 'Bestemming',
            'pickup_lat' => 52.3700,
            'pickup_lng' => 4.8900,
            'pickup_at' => now()->addMinutes(5),
            'customer_name' => 'Nieuwe klant',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'channel' => 'customer_app',
                    'radius_km' => 10,
                    'candidate_company_ids' => [(int) $company->id],
                ],
                'step_data' => ['app' => 'customer'],
            ],
        ]);

        app(RideDispatchService::class)->startDispatchForCompanies(
            'module_taxi',
            $ride,
            [(int) $company->id],
            false
        );

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
    }
}
