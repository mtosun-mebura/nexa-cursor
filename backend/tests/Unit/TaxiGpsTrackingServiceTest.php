<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingService;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiGpsTrackingServiceTest extends TestCase
{
    private string $conn = 'module_taxi';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection($this->conn)->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->string('license_plate')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function online_and_offline_views_are_exclusive(): void
    {
        $company = Company::query()->create(['name' => 'GPS Co', 'is_active' => true, 'package_key' => 'pro']);
        $online = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Online', 'last_name' => 'Chauffeur']);
        $offline = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Offline', 'last_name' => 'Chauffeur']);
        $vehicle = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Vito',
            'type' => 'van',
            'license_plate' => '12-ABC-3',
            'active' => true,
        ]);

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $online->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.3700,
            'lng' => 4.8900,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $offline->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => false,
            'lat' => 52.3800,
            'lng' => 4.9000,
            'location_updated_at' => now()->subMinutes(20),
            'last_seen_at' => now()->subMinutes(20),
        ]);

        $service = app(TaxiGpsTrackingService::class);
        $onlineOnly = $service->positions((int) $company->id, $this->conn, 'online');
        $this->assertCount(1, $onlineOnly['vehicles']);
        $this->assertSame('online', $onlineOnly['view']);
        $this->assertSame('12-ABC-3', $onlineOnly['vehicles'][0]['license_plate']);
        $this->assertSame('Online Chauffeur', $onlineOnly['vehicles'][0]['driver_name']);
        $this->assertTrue($onlineOnly['vehicles'][0]['is_online']);
        $this->assertNotEmpty($onlineOnly['server_now']);
        $this->assertArrayHasKey('completed_rides', $onlineOnly);
        $this->assertSame([], $onlineOnly['completed_rides']);

        $offlineOnly = $service->positions((int) $company->id, $this->conn, 'offline');
        $this->assertCount(1, $offlineOnly['vehicles']);
        $this->assertSame('offline', $offlineOnly['view']);
        $this->assertSame('Offline Chauffeur', $offlineOnly['vehicles'][0]['driver_name']);
        $this->assertFalse($offlineOnly['vehicles'][0]['is_online']);
    }

    #[Test]
    public function live_map_shows_one_marker_per_active_vehicle(): void
    {
        $company = Company::query()->create(['name' => 'Live GPS Co', 'is_active' => true, 'package_key' => 'pro']);
        $inCar = User::factory()->create(['company_id' => $company->id, 'first_name' => 'In', 'last_name' => 'Auto']);
        $withoutCar = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Zonder', 'last_name' => 'Auto']);
        $secondCar = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Tweede', 'last_name' => 'Auto']);
        $mercedes = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Mercedes E',
            'type' => 'sedan',
            'license_plate' => 'AB-123-CD',
            'active' => true,
        ]);
        $bmw = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'BMW 5',
            'type' => 'sedan',
            'license_plate' => 'WE-456-RT',
            'active' => true,
        ]);

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $inCar->id,
            'company_id' => $company->id,
            'vehicle_id' => $mercedes->id,
            'is_online' => true,
            'lat' => 52.2289,
            'lng' => 6.8896,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $withoutCar->id,
            'company_id' => $company->id,
            'vehicle_id' => null,
            'is_online' => true,
            'lat' => 52.2290,
            'lng' => 6.8897,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $secondCar->id,
            'company_id' => $company->id,
            'vehicle_id' => $bmw->id,
            'is_online' => true,
            'lat' => 52.2210,
            'lng' => 6.8950,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        \App\Modules\NexaTaxi\Models\RideRequest::on($this->conn)->create([
            'company_id' => $company->id,
            'driver_id' => $withoutCar->id,
            'vehicle_id' => $mercedes->id,
            'status' => \App\Modules\NexaTaxi\Models\RideRequest::STATUS_ACCEPTED,
        ]);

        $live = app(TaxiGpsTrackingService::class)->positions((int) $company->id, $this->conn, 'online');
        $plates = array_column($live['vehicles'], 'license_plate');
        sort($plates);

        $this->assertSame(['AB-123-CD', 'WE-456-RT'], $plates);
        $this->assertSame('vehicle-'.$mercedes->id, $live['vehicles'][0]['id']);
        $this->assertCount(2, array_unique(array_column($live['vehicles'], 'id')));
    }

    #[Test]
    public function two_online_drivers_in_the_same_car_keep_the_newest_position(): void
    {
        $company = Company::query()->create(['name' => 'Shared Car Co', 'is_active' => true, 'package_key' => 'pro']);
        $first = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Oud', 'last_name' => 'Signaal']);
        $second = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Nieuw', 'last_name' => 'Signaal']);
        $car = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Vito',
            'type' => 'van',
            'license_plate' => 'AB-123-CD',
            'active' => true,
        ]);

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $first->id,
            'company_id' => $company->id,
            'vehicle_id' => $car->id,
            'is_online' => true,
            'lat' => 52.10,
            'lng' => 6.80,
            'location_updated_at' => now()->subMinutes(4),
            'last_seen_at' => now()->subMinutes(4),
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $second->id,
            'company_id' => $company->id,
            'vehicle_id' => $car->id,
            'is_online' => true,
            'lat' => 52.22,
            'lng' => 6.89,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $live = app(TaxiGpsTrackingService::class)->positions((int) $company->id, $this->conn, 'online');
        $this->assertCount(1, $live['vehicles']);
        $this->assertSame('AB-123-CD', $live['vehicles'][0]['license_plate']);
        $this->assertSame('Nieuw Signaal', $live['vehicles'][0]['driver_name']);
        $this->assertEqualsWithDelta(52.22, $live['vehicles'][0]['lat'], 0.0001);
    }

    #[Test]
    public function offline_code_unlocks_only_after_a_matching_code(): void
    {
        $company = Company::query()->create(['name' => 'PIN Co', 'is_active' => true, 'package_key' => 'pro']);
        $settings = app(TaxiGpsTrackingSettingsService::class);

        $this->assertFalse($settings->hasOfflineCode((int) $company->id));
        $settings->setOfflineCode('4829', (int) $company->id);
        $this->assertTrue($settings->hasOfflineCode((int) $company->id));
        $this->assertFalse($settings->codeMatches('0000', (int) $company->id));
        $this->assertTrue($settings->codeMatches('4829', (int) $company->id));

        $this->assertFalse($settings->isOfflineUnlocked((int) $company->id));
        $settings->unlockOffline((int) $company->id);
        $this->assertTrue($settings->isOfflineUnlocked((int) $company->id));
        $settings->lockOffline((int) $company->id);
        $this->assertFalse($settings->isOfflineUnlocked((int) $company->id));
    }

    #[Test]
    public function appearance_fleet_lists_plates_and_drivers(): void
    {
        $company = Company::query()->create(['name' => 'Fleet Co', 'is_active' => true, 'package_key' => 'pro']);
        $driver = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Ahmed', 'last_name' => 'Hassan']);
        $withDriver = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Vito',
            'type' => 'van',
            'license_plate' => '12-GPS-1',
            'active' => true,
        ]);
        $withoutDriver = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Golf',
            'type' => 'car',
            'license_plate' => '34-GPS-2',
            'active' => true,
        ]);

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'vehicle_id' => $withDriver->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.89,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $settings = app(TaxiGpsTrackingSettingsService::class);
        $appearance = $settings->normalizeAppearance([
            'car_color_mode' => 'per_vehicle',
            'vehicle_colors' => [(string) $withDriver->id => '#1d4ed8'],
        ]);

        $service = app(TaxiGpsTrackingService::class);
        $fleet = $service->appearanceFleet((int) $company->id, $this->conn, $appearance);

        $this->assertCount(2, $fleet);
        $byPlate = collect($fleet)->keyBy('license_plate');
        $this->assertSame('Ahmed Hassan', $byPlate['12-GPS-1']['driver_name']);
        $this->assertSame('#1d4ed8', $byPlate['12-GPS-1']['color']);
        $this->assertSame('Geen chauffeur', $byPlate['34-GPS-2']['driver_name']);
        $this->assertSame($withoutDriver->id, $byPlate['34-GPS-2']['id']);
    }

    #[Test]
    public function positions_use_stored_vehicle_color_in_per_vehicle_mode(): void
    {
        $company = Company::query()->create(['name' => 'Color Co', 'is_active' => true, 'package_key' => 'pro']);
        $driver = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Lisa', 'last_name' => 'de Vries']);
        $vehicle = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Golf',
            'type' => 'car',
            'license_plate' => '34-GPS-2',
            'active' => true,
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.89,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $settings = app(TaxiGpsTrackingSettingsService::class);
        $settings->setAppearance([
            'car_color_mode' => 'per_vehicle',
            'vehicle_colors' => [(string) $vehicle->id => '#15803d'],
        ], (int) $company->id);

        $online = app(TaxiGpsTrackingService::class)->positions((int) $company->id, $this->conn, 'online');
        $this->assertCount(1, $online['vehicles']);
        $this->assertSame('#15803d', $online['vehicles'][0]['color']);

        $settings->setAppearance([
            'car_color_mode' => 'single',
            'car_color' => '#111827',
            'vehicle_colors' => [(string) $vehicle->id => '#15803d'],
        ], (int) $company->id);

        $single = app(TaxiGpsTrackingService::class)->positions((int) $company->id, $this->conn, 'online');
        $this->assertSame('#111827', $single['vehicles'][0]['color']);
        $this->assertSame('sedan', $single['vehicles'][0]['car_style']);
    }

    #[Test]
    public function positions_use_type_color_in_single_mode(): void
    {
        $company = Company::query()->create(['name' => 'Type Color Co', 'is_active' => true, 'package_key' => 'pro']);
        $driver = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Marco', 'last_name' => 'Jansen']);
        $vehicle = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Sprinter',
            'type' => 'van',
            'license_plate' => '56-GPS-3',
            'active' => true,
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.89,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $settings = app(TaxiGpsTrackingSettingsService::class);
        $settings->setAppearance([
            'car_color_mode' => 'single',
            'type_colors' => [
                'sedan' => '#111827',
                'van' => '#15803d',
                'bus' => '#dc2626',
            ],
        ], (int) $company->id);

        $online = app(TaxiGpsTrackingService::class)->positions((int) $company->id, $this->conn, 'online');
        $this->assertCount(1, $online['vehicles']);
        $this->assertSame('van', $online['vehicles'][0]['car_style']);
        $this->assertSame('#15803d', $online['vehicles'][0]['color']);
    }

    #[Test]
    public function configured_fleet_for_demo_uses_all_active_plates(): void
    {
        $company = Company::query()->create(['name' => 'Demo Vloot Co', 'is_active' => true, 'package_key' => 'pro']);
        $driver = User::factory()->create(['company_id' => $company->id, 'first_name' => 'Soraya', 'last_name' => 'El Idrissi']);
        $first = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'E-Klasse',
            'type' => 'car',
            'license_plate' => 'G-111-AB',
            'active' => true,
        ]);
        Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Vito',
            'type' => 'van',
            'license_plate' => 'H-222-CD',
            'active' => true,
        ]);
        Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Oude bus',
            'type' => 'bus',
            'license_plate' => 'K-333-EF',
            'active' => false,
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'vehicle_id' => $first->id,
            'is_online' => true,
            'lat' => 52.22,
            'lng' => 6.89,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $online = app(TaxiGpsTrackingService::class)->positions((int) $company->id, $this->conn, 'online');
        $this->assertCount(1, $online['vehicles']);

        $fleet = app(TaxiGpsTrackingService::class)->configuredFleetForDemo((int) $company->id, $this->conn);
        $this->assertCount(2, $fleet);
        $this->assertSame(['G-111-AB', 'H-222-CD'], array_column($fleet, 'license_plate'));
        $this->assertSame('Soraya El Idrissi', $fleet[0]['driver_name']);
        $this->assertSame('Vito', $fleet[1]['driver_name']);
        $this->assertSame('vehicle-'.$first->id, $fleet[0]['id']);
    }
}
