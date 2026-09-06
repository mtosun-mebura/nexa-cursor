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
}
