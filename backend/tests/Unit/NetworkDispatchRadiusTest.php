<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\DriverDeviceToken;
use App\Models\User;
use App\Services\CompanyEntitlementService;
use App\Support\TenantPackageCapability;
use App\Modules\NexaTaxi\Jobs\StartRideDispatchJob;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            $table->string('payment_status', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('channel', 32);
            $table->string('mollie_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 32);
            $table->timestamp('paid_at')->nullable();
            $table->json('mollie_payload')->nullable();
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

    #[Test]
    public function paid_booking_offers_the_online_driver_and_not_offline_or_other_company_drivers(): void
    {
        $company = Company::query()->create(['name' => 'Paid Taxi', 'is_active' => true]);
        $other = Company::query()->create(['name' => 'Other Taxi', 'is_active' => true]);

        $online = User::factory()->create(['company_id' => $company->id]);
        $online->assignRole('chauffeur');
        $offline = User::factory()->create(['company_id' => $company->id]);
        $offline->assignRole('chauffeur');
        $otherDriver = User::factory()->create(['company_id' => $other->id]);
        $otherDriver->assignRole('chauffeur');
        $busy = User::factory()->create(['company_id' => $company->id]);
        $busy->assignRole('chauffeur');

        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $online->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.3702,
            'lng' => 4.8952,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $offline->id,
            'company_id' => $company->id,
            'is_online' => false,
            'lat' => 52.3702,
            'lng' => 4.8952,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $otherDriver->id,
            'company_id' => $other->id,
            'is_online' => true,
            'lat' => 52.3702,
            'lng' => 4.8952,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);
        // Online, maar zonder GPS: marktplaatsstraal slaat deze chauffeur over.
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $busy->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => null,
            'lng' => null,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $pickupAt = now('Europe/Amsterdam')->addHours(2)->format('Y-m-d H:i:s');
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'status' => RideRequest::STATUS_PENDING_PAYMENT,
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'pickup_address' => 'Dam',
            'dropoff_address' => 'Station',
            'pickup_lat' => 52.3702,
            'pickup_lng' => 4.8952,
            'passengers' => 1,
            'pickup_at' => $pickupAt,
            'customer_name' => 'Klant',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'channel' => 'customer_app',
                    'radius_km' => 50,
                    'candidate_company_ids' => [(int) $company->id],
                    'settings_company_id' => (int) $company->id,
                ],
                'step_data' => ['app' => 'customer'],
            ],
        ]);

        $payment = RidePayment::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => null,
            'channel' => RidePayment::CHANNEL_BOOKING,
            'mollie_payment_id' => 'tr_test_paid',
            'amount' => 18.50,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_OPEN,
        ]);

        app(TaxiRidePaymentService::class)->applyMollieStatus('module_taxi', $payment, [
            'id' => 'tr_test_paid',
            'status' => 'paid',
        ]);

        $this->assertSame(RideRequest::STATUS_OFFERED, $ride->fresh()->status);
        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $online->id,
            'company_id' => $company->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $offline->id,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $otherDriver->id,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $busy->id,
        ], 'module_taxi');

        $cutoff = app(TaxiDispatchSettingsService::class)->pickupQueueCutoffAt((int) $company->id);
        $inboxIds = RideDispatchOffer::on('module_taxi')
            ->inboxForDriver((int) $online->id, $cutoff)
            ->pluck('ride_request_id')
            ->all();
        $this->assertContains($ride->id, $inboxIds);

        app(TaxiRidePaymentService::class)->applyMollieStatus('module_taxi', $payment->fresh(), [
            'id' => 'tr_test_paid',
            'status' => 'paid',
        ]);
        $this->assertSame(1, RideDispatchOffer::on('module_taxi')->where('ride_request_id', $ride->id)->count());

        $website = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'source' => RideRequest::SOURCE_BOOKING,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'payment_method' => RideRequest::PAYMENT_METHOD_DRIVER,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
            'pickup_address' => 'Website',
            'dropoff_address' => 'Bestemming',
            'pickup_lat' => 52.3702,
            'pickup_lng' => 4.8952,
            'passengers' => 1,
            'pickup_at' => $pickupAt,
            'customer_name' => 'Website klant',
        ]);
        // Standaardconnectie opent ná de taxi-transactie. afterCommit hing de callback
        // aan die transactie; een rollback gooide het aanbod weg.
        DB::connection('module_taxi')->beginTransaction();
        DB::beginTransaction();
        try {
            StartRideDispatchJob::dispatchWhenCommitted('module_taxi', (int) $website->id, (int) $company->id);
            DB::connection('module_taxi')->commit();
        } finally {
            DB::rollBack();
        }

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $website->id,
            'driver_id' => $online->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $website->id,
            'driver_id' => $offline->id,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $website->id,
            'driver_id' => $otherDriver->id,
        ], 'module_taxi');
    }

    #[Test]
    public function start_package_booking_still_reaches_an_online_driver(): void
    {
        $company = Company::query()->create([
            'name' => 'Start Taxi',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $entitlements = app(CompanyEntitlementService::class);
        $this->assertFalse($entitlements->allows($company, TenantPackageCapability::DISPATCH));
        $this->assertFalse($entitlements->allows($company, TenantPackageCapability::DRIVER_APP));
        $this->assertTrue($entitlements->allows($company, TenantPackageCapability::WEBSITE_BOOKING));

        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.3702,
            'lng' => 4.8952,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'source' => RideRequest::SOURCE_BOOKING,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Start',
            'dropoff_address' => 'Bestemming',
            'pickup_lat' => 52.3702,
            'pickup_lng' => 4.8952,
            'pickup_at' => now('Europe/Amsterdam')->addHour()->format('Y-m-d H:i:s'),
            'customer_name' => 'Start klant',
        ]);

        StartRideDispatchJob::dispatchWhenCommitted('module_taxi', (int) $ride->id, (int) $company->id);

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
    }

    #[Test]
    public function creating_an_offer_sends_one_expo_push_per_driver_device(): void
    {
        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-1']],
            ]),
        ]);

        $company = Company::query()->create([
            'name' => 'Push Taxi',
            'is_active' => true,
        ]);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $driver->assignRole('chauffeur');
        $token = 'ExponentPushToken[offer-device]';
        DriverDeviceToken::query()->create([
            'user_id' => $driver->id,
            'token' => $token,
            'platform' => 'android',
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.3702,
            'lng' => 4.8952,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'source' => RideRequest::SOURCE_BOOKING,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Damrak 1',
            'dropoff_address' => 'Centraal',
            'pickup_lat' => 52.3702,
            'pickup_lng' => 4.8952,
            'pickup_at' => now('Europe/Amsterdam')->addHour()->format('Y-m-d H:i:s'),
            'final_price' => 18.50,
            'customer_name' => 'Push klant',
        ]);

        StartRideDispatchJob::dispatchWhenCommitted('module_taxi', (int) $ride->id, (int) $company->id);
        StartRideDispatchJob::dispatchWhenCommitted('module_taxi', (int) $ride->id, (int) $company->id);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($token) {
            $body = $request->data();
            $message = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return str_contains($request->url(), 'exp.host/--/api/v2/push/send')
                && ($message['to'] ?? null) === $token
                && ($message['title'] ?? null) === 'Nieuwe rit'
                && ($message['sound'] ?? null) === 'default'
                && ($message['priority'] ?? null) === 'high'
                && ($message['channelId'] ?? null) === 'driver-offers-system'
                && ($message['interruptionLevel'] ?? null) === 'time-sensitive'
                && ($message['data']['type'] ?? null) === 'driver_offer'
                && str_contains((string) ($message['body'] ?? ''), 'Damrak 1');
        });
    }

    #[Test]
    public function paid_marketplace_ride_with_past_pickup_still_reaches_the_online_driver_inbox(): void
    {
        $company = Company::query()->create(['name' => 'Past Pickup Taxi', 'is_active' => true]);
        $other = Company::query()->create(['name' => 'Other Past Taxi', 'is_active' => true]);

        $online = User::factory()->create(['company_id' => $company->id]);
        $online->assignRole('chauffeur');
        $offline = User::factory()->create(['company_id' => $company->id]);
        $offline->assignRole('chauffeur');
        $otherDriver = User::factory()->create(['company_id' => $other->id]);
        $otherDriver->assignRole('chauffeur');

        foreach ([[$online, true], [$offline, false], [$otherDriver, true]] as [$driver, $isOnline]) {
            DriverAvailability::on('module_taxi')->create([
                'driver_id' => $driver->id,
                'company_id' => $driver->company_id,
                'is_online' => $isOnline,
                'lat' => 52.2290,
                'lng' => 6.8894,
                'last_seen_at' => now(),
                'location_updated_at' => now(),
            ]);
        }

        $stalePickup = now('Europe/Amsterdam')->subHours(6)->format('Y-m-d H:i:s');
        $futurePickup = now('Europe/Amsterdam')->addHours(3)->format('Y-m-d H:i:s');
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'status' => RideRequest::STATUS_PENDING_PAYMENT,
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'pickup_address' => 'Roombeek',
            'dropoff_address' => 'Station Enschede',
            'pickup_lat' => 52.2290,
            'pickup_lng' => 6.8894,
            'passengers' => 1,
            'pickup_at' => $stalePickup,
            'customer_name' => 'Klant',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'channel' => 'customer_app',
                    'radius_km' => 50,
                    'candidate_company_ids' => [(int) $company->id],
                    'settings_company_id' => (int) $company->id,
                ],
                'step_data' => ['app' => 'customer'],
            ],
        ]);
        $payment = RidePayment::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => null,
            'channel' => RidePayment::CHANNEL_BOOKING,
            'mollie_payment_id' => 'tr_past_pickup',
            'amount' => 16.00,
            'currency' => 'EUR',
            'status' => RidePayment::STATUS_OPEN,
        ]);

        app(TaxiRidePaymentService::class)->applyMollieStatus('module_taxi', $payment, [
            'id' => 'tr_past_pickup',
            'status' => 'paid',
        ]);

        $ride = $ride->fresh();
        $cutoff = app(TaxiDispatchSettingsService::class)->pickupQueueCutoffAt((int) $company->id);
        $this->assertTrue($ride->pickup_at->greaterThanOrEqualTo($cutoff));
        $this->assertNotSame($stalePickup, $ride->pickup_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $online->id,
            'company_id' => $company->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $offline->id,
        ], 'module_taxi');
        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $otherDriver->id,
        ], 'module_taxi');

        $dispatch = app(RideDispatchService::class);
        $dispatch->expireOffersForPastPickups('module_taxi', (int) $company->id);
        $inboxIds = RideDispatchOffer::on('module_taxi')
            ->inboxForDriver((int) $online->id, $cutoff)
            ->pluck('ride_request_id')
            ->all();
        $this->assertContains($ride->id, $inboxIds);

        $scheduled = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Later',
            'dropoff_address' => 'Bestemming',
            'pickup_lat' => 52.2290,
            'pickup_lng' => 6.8894,
            'passengers' => 1,
            'pickup_at' => $futurePickup,
            'customer_name' => 'Later',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'channel' => 'customer_app',
                    'radius_km' => 50,
                    'candidate_company_ids' => [(int) $company->id],
                ],
                'step_data' => ['app' => 'customer'],
            ],
        ]);
        $dispatch->startDispatchForCompanies('module_taxi', $scheduled, [(int) $company->id], false);
        $this->assertSame($futurePickup, $scheduled->fresh()->pickup_at->format('Y-m-d H:i:s'));

        $expired = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'status' => RideRequest::STATUS_OFFERED,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'pickup_address' => 'Al betaald',
            'dropoff_address' => 'Wacht',
            'pickup_lat' => 52.2290,
            'pickup_lng' => 6.8894,
            'passengers' => 1,
            'pickup_at' => $stalePickup,
            'customer_name' => 'Wacht',
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'channel' => 'customer_app',
                    'radius_km' => 50,
                    'candidate_company_ids' => [(int) $company->id],
                ],
                'step_data' => ['app' => 'customer'],
            ],
        ]);
        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $expired->id,
            'company_id' => $company->id,
            'driver_id' => $online->id,
            'status' => RideDispatchOffer::STATUS_EXPIRED,
            'wave' => 1,
            'offered_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinutes(5),
            'responded_at' => now()->subMinutes(5),
        ]);

        $dispatch->expireOffersForPastPickups('module_taxi', (int) $company->id);
        $dispatch->syncPendingOffersForDriver('module_taxi', (int) $company->id, (int) $online->id);

        $revivedCutoff = app(TaxiDispatchSettingsService::class)->pickupQueueCutoffAt((int) $company->id);
        $revivedIds = RideDispatchOffer::on('module_taxi')
            ->inboxForDriver((int) $online->id, $revivedCutoff)
            ->pluck('ride_request_id')
            ->all();
        $this->assertContains($expired->id, $revivedIds);
        $this->assertTrue($expired->fresh()->pickup_at->greaterThanOrEqualTo($revivedCutoff));
    }
}
