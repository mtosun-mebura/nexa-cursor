<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Services\ModuleDatabaseService;
use App\Services\NearestTaxiTenantResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NearestTaxiTenantResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi');
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
        });

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        \App\Modules\NexaTaxi\Support\TaxiDispatchSchema::resetCache();
    }

    #[Test]
    public function it_picks_the_company_with_the_closest_online_taxi_not_the_office(): void
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        // Vestiging ver weg (Maastricht), maar chauffeur staat in Amsterdam bij de klant.
        $farOfficeNearDriver = Company::query()->create([
            'name' => 'Taxi Ver Weg Kantoor',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 50.8514,
            'longitude' => 5.6910,
            'accepts_nexa_suite_bookings' => true,
        ]);
        // Vestiging dichtbij, maar chauffeur ver weg.
        $nearOfficeFarDriver = Company::query()->create([
            'name' => 'Taxi Dichtbij Kantoor',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 52.3676,
            'longitude' => 4.9041,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $farOfficeNearDriver->modules()->attach($taxi->id);
        $nearOfficeFarDriver->modules()->attach($taxi->id);

        Vehicle::on('module_taxi')->create(['company_id' => $farOfficeNearDriver->id, 'name' => 'Sedan A', 'active' => true]);
        Vehicle::on('module_taxi')->create(['company_id' => $nearOfficeFarDriver->id, 'name' => 'Sedan B', 'active' => true]);

        $this->putOnlineDriver(101, (int) $farOfficeNearDriver->id, 52.3700, 4.9000);
        $this->putOnlineDriver(102, (int) $nearOfficeFarDriver->id, 51.9244, 4.4777);

        $match = app(NearestTaxiTenantResolver::class)->resolve(52.37, 4.90);

        $this->assertNotNull($match);
        $this->assertSame($farOfficeNearDriver->id, $match['company']->id);
        $this->assertLessThan(2, $match['distance_km']);
    }

    #[Test]
    public function it_returns_multiple_companies_with_taxis_within_radius(): void
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $amsterdam = Company::query()->create([
            'name' => 'Taxi Amsterdam',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 50.0,
            'longitude' => 5.0,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $amstelveen = Company::query()->create([
            'name' => 'Taxi Amstelveen',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 50.0,
            'longitude' => 5.0,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $maastricht = Company::query()->create([
            'name' => 'Taxi Maastricht',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 52.37,
            'longitude' => 4.90,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $amsterdam->modules()->attach($taxi->id);
        $amstelveen->modules()->attach($taxi->id);
        $maastricht->modules()->attach($taxi->id);

        Vehicle::on('module_taxi')->create(['company_id' => $amsterdam->id, 'name' => 'Sedan A', 'active' => true]);
        Vehicle::on('module_taxi')->create(['company_id' => $amstelveen->id, 'name' => 'Sedan W', 'active' => true]);
        Vehicle::on('module_taxi')->create(['company_id' => $maastricht->id, 'name' => 'Sedan Z', 'active' => true]);

        $this->putOnlineDriver(201, (int) $amsterdam->id, 52.3676, 4.9041);
        $this->putOnlineDriver(202, (int) $amstelveen->id, 52.3089, 4.8503);
        $this->putOnlineDriver(203, (int) $maastricht->id, 50.8514, 5.6910);

        $matches = app(NearestTaxiTenantResolver::class)->resolveNearby(52.37, 4.90, 5, 10);

        $this->assertCount(2, $matches);
        $this->assertEqualsCanonicalizing(
            [$amsterdam->id, $amstelveen->id],
            array_map(static fn (array $match) => $match['company']->id, $matches)
        );
    }

    #[Test]
    public function it_ignores_company_office_without_nearby_online_taxi(): void
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $enschede = Company::query()->create([
            'name' => 'Taxi Royaal',
            'is_active' => true,
            'is_main' => true,
            'city' => 'Enschede',
            'latitude' => 52.2205,
            'longitude' => 6.8958,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $enschede->modules()->attach($taxi->id);
        Vehicle::on('module_taxi')->create(['company_id' => $enschede->id, 'name' => 'Sedan E', 'active' => true]);

        // Alleen vestiging dichtbij; geen online chauffeur → geen match.
        $matches = app(NearestTaxiTenantResolver::class)->resolveNearby(52.2291, 6.8894, 5, 10);

        $this->assertSame([], $matches);
    }

    #[Test]
    public function it_does_not_fall_back_to_taxis_outside_the_radius(): void
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $maastricht = Company::query()->create([
            'name' => 'Taxi Maastricht',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 52.2291,
            'longitude' => 6.8894,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $maastricht->modules()->attach($taxi->id);
        Vehicle::on('module_taxi')->create(['company_id' => $maastricht->id, 'name' => 'Sedan Z', 'active' => true]);
        $this->putOnlineDriver(301, (int) $maastricht->id, 50.8514, 5.6910);

        $matches = app(NearestTaxiTenantResolver::class)->resolveNearby(52.2291, 6.8894, 5, 10);

        $this->assertSame([], $matches);
    }

    #[Test]
    public function it_normalizes_marketplace_radius(): void
    {
        $this->assertSame(10.0, NearestTaxiTenantResolver::normalizeRadiusKm(null));
        $this->assertSame(10.0, NearestTaxiTenantResolver::normalizeRadiusKm(0));
        $this->assertSame(1.0, NearestTaxiTenantResolver::normalizeRadiusKm(0.4));
        $this->assertSame(25.0, NearestTaxiTenantResolver::normalizeRadiusKm(25));
        $this->assertSame(100.0, NearestTaxiTenantResolver::normalizeRadiusKm(999));
    }

    private function putOnlineDriver(int $driverId, int $companyId, float $lat, float $lng): void
    {
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => $driverId,
            'company_id' => $companyId,
            'is_online' => true,
            'lat' => $lat,
            'lng' => $lng,
            'last_seen_at' => now(),
            'location_updated_at' => now(),
        ]);
    }
}
