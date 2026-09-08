<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Services\ModuleDatabaseService;
use App\Services\NexaTaxiBookingPricingService;
use App\Services\TenantBookingLiveFleetService;
use App\Support\TenantPackageAddon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantBookingLiveFleetServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function merge_section_config_keeps_live_fleet_flags(): void
    {
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => [
                'show_live_fleet' => true,
                'show_live_fleet_demo' => true,
            ],
        ]);

        $this->assertTrue($config['logic']['show_live_fleet']);
        $this->assertTrue($config['logic']['show_live_fleet_demo']);
        $this->assertSame(1, $config['logic']['live_fleet_refresh_seconds']);
        $this->assertSame('#ea580c', $config['logic']['live_fleet_car_color']);
    }

    #[Test]
    public function v2_without_flags_returns_no_vehicles(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Tosun Test',
            'slug' => 'taxitosun-test',
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();

        $this->assertFalse($service->shouldShowOnBookingMap('component:taxi.boekingsmodule_v2', $config, $company));
        $this->assertSame([], $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, 52.22, 6.89));
    }

    #[Test]
    public function demo_flag_is_hidden_from_guests(): void
    {
        Http::fake();
        $company = Company::query()->create([
            'name' => 'Taxi Tosun',
            'slug' => 'taxitosun-guest-demo',
            'city' => 'Enschede',
            'latitude' => '52.2215',
            'longitude' => '6.8937',
            'package_key' => 'pro',
        ]);
        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => ['show_live_fleet_demo' => true],
        ]);

        $this->assertFalse($service->shouldShowOnBookingMap('component:taxi.boekingsmodule_v2', $config, $company));
        $this->assertSame([], $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null));
    }

    #[Test]
    public function demo_flag_returns_three_vehicles_only_for_super_admin(): void
    {
        Http::fake();
        $this->actingAsSuperAdmin();
        $company = Company::query()->create([
            'name' => 'Taxi Tosun',
            'slug' => 'taxitosun',
            'city' => 'Enschede',
            'latitude' => '52.2215',
            'longitude' => '6.8937',
            'package_key' => 'pro',
        ]);
        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => ['show_live_fleet_demo' => true],
        ]);

        $this->assertTrue($service->shouldShowOnBookingMap('component:taxi.boekingsmodule_v2', $config, $company));
        $this->assertTrue($service->showsOccupancyStatus('component:taxi.boekingsmodule_v2', $config, $company));

        $vehicles = $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null);
        $this->assertCount(3, $vehicles);
        $statuses = array_column($vehicles, 'status');
        $this->assertContains('free', $statuses);
        $this->assertContains('occupied', $statuses);
        $this->assertTrue($vehicles[0]['demo']);
        $this->assertSame('#ea580c', $vehicles[0]['color']);
        $this->assertArrayHasKey('heading', $vehicles[0]);
    }

    #[Test]
    public function guest_with_live_and_demo_flags_only_sees_real_vehicles(): void
    {
        $this->bindTaxiModuleSqlite();
        $company = Company::query()->create([
            'name' => 'Taxi Tosun Live',
            'slug' => 'taxitosun-live',
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $vehicle = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Vito',
            'type' => 'van',
            'active' => true,
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => 44,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.2215,
            'lng' => 6.8937,
            'location_updated_at' => now()->subMinutes(30),
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => [
                'show_live_fleet' => true,
                'show_live_fleet_demo' => true,
            ],
        ]);

        $this->assertTrue($service->shouldShowOnBookingMap('component:taxi.boekingsmodule_v2', $config, $company));
        $vehicles = $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null);
        $this->assertCount(1, $vehicles);
        $this->assertSame('tenant-44', $vehicles[0]['id']);
        $this->assertFalse($vehicles[0]['demo']);
    }

    #[Test]
    public function live_fleet_includes_online_vehicles_without_recent_ping(): void
    {
        $this->bindTaxiModuleSqlite();
        $company = Company::query()->create([
            'name' => 'Taxi Tosun Stale',
            'slug' => 'taxitosun-stale',
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => 51,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.22,
            'lng' => 6.89,
            'location_updated_at' => now()->subHours(4),
            'last_seen_at' => now()->subHours(4),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => 52,
            'company_id' => $company->id,
            'is_online' => false,
            'lat' => 52.23,
            'lng' => 6.90,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => ['show_live_fleet' => true],
        ]);

        $vehicles = $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, 52.22, 6.89);
        $this->assertCount(1, $vehicles);
        $this->assertSame('tenant-51', $vehicles[0]['id']);
        $this->assertSame('free', $vehicles[0]['status']);
    }

    #[Test]
    public function merge_clamps_refresh_seconds_and_car_color(): void
    {
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => [
                'live_fleet_refresh_seconds' => 99,
                'live_fleet_car_color' => 'not-a-color',
            ],
        ]);

        $this->assertSame(30, $config['logic']['live_fleet_refresh_seconds']);
        $this->assertSame('#ea580c', $config['logic']['live_fleet_car_color']);

        $ok = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => [
                'live_fleet_refresh_seconds' => 3,
                'live_fleet_car_color' => '#15803d',
            ],
        ]);
        $this->assertSame(3, $ok['logic']['live_fleet_refresh_seconds']);
        $this->assertSame('#15803d', $ok['logic']['live_fleet_car_color']);
    }

    #[Test]
    public function demo_vehicles_move_along_a_path_over_time(): void
    {
        Http::fake();
        $this->actingAsSuperAdmin();
        $company = Company::query()->create([
            'name' => 'Taxi Tosun',
            'slug' => 'taxitosun-path',
            'city' => 'Enschede',
            'latitude' => '52.2215',
            'longitude' => '6.8937',
            'package_key' => 'pro',
        ]);
        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => [
                'show_live_fleet_demo' => true,
                'live_fleet_car_color' => '#dc2626',
            ],
        ]);

        Carbon::setTestNow('2026-09-07 12:00:00');
        $first = $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null);
        Carbon::setTestNow('2026-09-07 12:00:25');
        $later = $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null);
        Carbon::setTestNow();

        $this->assertNotEquals($first[0]['lat'], $later[0]['lat']);
        $this->assertNotEquals($first[0]['lng'], $later[0]['lng']);
        $this->assertSame('#dc2626', $first[0]['color']);
        foreach ($first as $vehicle) {
            $this->assertTrue(abs($vehicle['lat'] - 52.2215) < 0.02);
            $this->assertTrue(abs($vehicle['lng'] - 6.8937) < 0.02);
        }
    }

    #[Test]
    public function demo_vehicles_follow_snapped_road_geometry_and_heading(): void
    {
        $road = [
            [52.2300, 6.8937],
            [52.2300, 6.8980],
            [52.2270, 6.8980],
            [52.2270, 6.8937],
        ];
        $encoded = $this->encodePolyline($road);
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['error' => ['status' => 'PERMISSION_DENIED']], 403),
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
            'router.project-osrm.org/*' => Http::response([
                'routes' => [['geometry' => $encoded]],
            ], 200),
        ]);
        $this->actingAsSuperAdmin();
        $company = Company::query()->create([
            'name' => 'Taxi Tosun',
            'slug' => 'taxitosun-roads',
            'city' => 'Enschede',
            'latitude' => '52.2215',
            'longitude' => '6.8937',
            'package_key' => 'pro',
        ]);
        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => ['show_live_fleet_demo' => true],
        ]);

        $vehicles = $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null);
        $this->assertCount(3, $vehicles);
        foreach ($vehicles as $vehicle) {
            $this->assertGreaterThan(52.2265, $vehicle['lat']);
            $this->assertLessThan(52.2305, $vehicle['lat']);
            $this->assertGreaterThan(6.8934, $vehicle['lng']);
            $this->assertLessThan(6.8983, $vehicle['lng']);
            $this->assertArrayHasKey('heading', $vehicle);
            $heading = (float) $vehicle['heading'];
            $alongStreet = $heading < 8 || abs($heading - 90) < 8 || abs($heading - 180) < 8 || abs($heading - 270) < 8 || abs($heading - 360) < 8;
            $this->assertTrue($alongStreet, 'heading '.$heading.' should follow the axis-aligned road');
        }
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     */
    private function encodePolyline(array $points): string
    {
        $factor = 1e5;
        $out = '';
        $prevLat = 0;
        $prevLng = 0;
        foreach ($points as $point) {
            $lat = (int) round($point[0] * $factor);
            $lng = (int) round($point[1] * $factor);
            $out .= $this->encodePolylineValue($lat - $prevLat);
            $out .= $this->encodePolylineValue($lng - $prevLng);
            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $out;
    }

    private function encodePolylineValue(int $value): string
    {
        $value = $value < 0 ? ~($value << 1) : ($value << 1);
        $out = '';
        while ($value >= 0x20) {
            $out .= chr((0x20 | ($value & 0x1F)) + 63);
            $value >>= 5;
        }

        return $out.chr($value + 63);
    }

    #[Test]
    public function live_fleet_requires_gps_addon_when_package_is_set(): void
    {
        $company = Company::query()->create([
            'name' => 'No GPS Co',
            'slug' => 'no-gps-co',
            'package_key' => 'pro',
            'package_addons' => [],
        ]);
        $service = app(TenantBookingLiveFleetService::class);
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => ['show_live_fleet' => true],
        ]);

        $this->assertFalse($service->shouldShowOnBookingMap('component:taxi.boekingsmodule_v2', $config, $company));
        $this->assertSame([], $service->vehiclesForSection('component:taxi.boekingsmodule_v2', $config, $company, null, null));
    }

    private function actingAsSuperAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        return $admin;
    }

    private function bindTaxiModuleSqlite(): void
    {
        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi');
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
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
        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->timestamps();
        });
    }
}
