<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
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
    }

    #[Test]
    public function it_picks_the_closest_active_taxi_tenant(): void
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
            'latitude' => 52.3676,
            'longitude' => 4.9041,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $rotterdam = Company::query()->create([
            'name' => 'Taxi Rotterdam',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 51.9244,
            'longitude' => 4.4777,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $amsterdam->modules()->attach($taxi->id);
        $rotterdam->modules()->attach($taxi->id);

        Vehicle::on('module_taxi')->create(['company_id' => $amsterdam->id, 'name' => 'Sedan A', 'active' => true]);
        Vehicle::on('module_taxi')->create(['company_id' => $rotterdam->id, 'name' => 'Sedan R', 'active' => true]);

        $match = app(NearestTaxiTenantResolver::class)->resolve(52.37, 4.90);

        $this->assertNotNull($match);
        $this->assertSame($amsterdam->id, $match['company']->id);
        $this->assertLessThan(5, $match['distance_km']);
    }

    #[Test]
    public function it_returns_multiple_nearby_tenants_within_radius(): void
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
            'latitude' => 52.3676,
            'longitude' => 4.9041,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $amstelveen = Company::query()->create([
            'name' => 'Taxi Amstelveen',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 52.3089,
            'longitude' => 4.8503,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $maastricht = Company::query()->create([
            'name' => 'Taxi Maastricht',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 50.8514,
            'longitude' => 5.6910,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $amsterdam->modules()->attach($taxi->id);
        $amstelveen->modules()->attach($taxi->id);
        $maastricht->modules()->attach($taxi->id);

        Vehicle::on('module_taxi')->create(['company_id' => $amsterdam->id, 'name' => 'Sedan A', 'active' => true]);
        Vehicle::on('module_taxi')->create(['company_id' => $amstelveen->id, 'name' => 'Sedan W', 'active' => true]);
        Vehicle::on('module_taxi')->create(['company_id' => $maastricht->id, 'name' => 'Sedan Z', 'active' => true]);

        $matches = app(NearestTaxiTenantResolver::class)->resolveNearby(52.37, 4.90, 5, 10);

        $this->assertCount(2, $matches);
        $this->assertEqualsCanonicalizing(
            [$amsterdam->id, $amstelveen->id],
            array_map(static fn (array $match) => $match['company']->id, $matches)
        );
    }

    #[Test]
    public function it_includes_tenants_with_a_main_office_flag(): void
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

        $matches = app(NearestTaxiTenantResolver::class)->resolveNearby(52.2291, 6.8894, 5, 10);

        $this->assertCount(1, $matches);
        $this->assertSame($enschede->id, $matches[0]['company']->id);
        $this->assertLessThan(5, $matches[0]['distance_km']);
    }

    #[Test]
    public function it_does_not_fall_back_to_tenants_outside_the_radius(): void
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
            'latitude' => 50.8514,
            'longitude' => 5.6910,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $maastricht->modules()->attach($taxi->id);
        Vehicle::on('module_taxi')->create(['company_id' => $maastricht->id, 'name' => 'Sedan Z', 'active' => true]);

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
}
