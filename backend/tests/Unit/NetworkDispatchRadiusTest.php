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
    public function auto_network_waits_for_fallback_seconds_before_partner_offers(): void
    {
        $owner = Company::query()->create(['name' => 'Owner Auto', 'is_active' => true]);
        $partner = Company::query()->create(['name' => 'Partner Auto', 'is_active' => true]);

        $settings = app(TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(TaxiDispatchSettingsService::NETWORK_MODE_AUTO, $owner->id);
        $settings->setNetworkPartnerCompanyIds([(int) $partner->id], $owner->id);
        $settings->setNetworkMaxRadiusKm(25, $owner->id);
        $settings->setNetworkFallbackSeconds(120, $owner->id);

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

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_lat' => 52.3700,
            'pickup_lng' => 4.8900,
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        // Eigen vloot-aanbod 30s geleden — fallback (120s) nog niet voorbij.
        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => $owner->id,
            'driver_id' => 999001,
            'status' => RideDispatchOffer::STATUS_EXPIRED,
            'offered_at' => now()->subSeconds(30),
            'expires_at' => now()->subSeconds(5),
            'responded_at' => now()->subSeconds(5),
        ]);

        app(RideDispatchService::class)->escalateWaitingRide('module_taxi', (int) $ride->id);

        $this->assertDatabaseMissing('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $partnerDriver->id,
        ], 'module_taxi');

        // Eigen aanbod 130s geleden — fallback voorbij.
        RideDispatchOffer::on('module_taxi')
            ->where('ride_request_id', $ride->id)
            ->where('company_id', $owner->id)
            ->update(['offered_at' => now()->subSeconds(130)]);

        // Clear escalate debounce
        \Illuminate\Support\Facades\Cache::flush();

        app(RideDispatchService::class)->escalateWaitingRide('module_taxi', (int) $ride->id);

        $this->assertDatabaseHas('ride_dispatch_offers', [
            'ride_request_id' => $ride->id,
            'driver_id' => $partnerDriver->id,
            'company_id' => $partner->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
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
}
