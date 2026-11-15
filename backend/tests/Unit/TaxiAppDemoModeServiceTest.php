<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiAppDemoModeService;
use App\Services\ModuleDatabaseService;
use App\Services\NexaDemoAccountService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiAppDemoModeServiceTest extends TestCase
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
        config(['nexa_demo.enabled' => true]);

        Schema::connection($this->conn)->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->string('license_plate')->nullable();
            $table->unsignedSmallInteger('seats')->nullable();
            $table->string('person_range')->nullable();
            $table->boolean('active')->default(true);
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('price_per_min', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->nullable();
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
    public function ensure_fleet_preserves_admin_edited_vehicle_attributes(): void
    {
        $company = Company::query()->create([
            'name' => 'Nexa Taxi Demo',
            'slug' => 'nexa-taxi-demo',
            'is_active' => true,
        ]);

        $this->mock(ModuleDatabaseService::class, function ($mock) {
            $mock->shouldReceive('ensureModuleStorageReady')->andReturnNull();
            $mock->shouldReceive('getModuleConnectionName')->andReturn('module_taxi');
        });

        $this->mock(NexaDemoAccountService::class, function ($mock) use ($company) {
            $mock->shouldReceive('ensure')->andReturn([
                'company' => $company,
                'user' => null,
                'created' => false,
            ]);
        });

        Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'license_plate' => 'D-01-NEX',
            'name' => 'Nexa Taxi',
            'type' => Vehicle::TYPE_VAN,
            'seats' => 8,
            'person_range' => Vehicle::PERSON_RANGE_5_8,
            'active' => true,
            'base_fare' => 5.00,
            'price_per_km' => 2.00,
            'price_per_min' => 0.50,
            'min_fare' => 10.00,
        ]);

        app(TaxiAppDemoModeService::class)->ensureFleet();

        $vehicle = Vehicle::on($this->conn)
            ->where('company_id', $company->id)
            ->where('license_plate', 'D-01-NEX')
            ->first();

        $this->assertNotNull($vehicle);
        $this->assertSame('Nexa Taxi', $vehicle->name);
        $this->assertSame(Vehicle::TYPE_VAN, $vehicle->type);
        $this->assertSame(Vehicle::PERSON_RANGE_5_8, $vehicle->person_range);
        $this->assertEquals(5.00, (float) $vehicle->base_fare);
        $this->assertEquals(2.00, (float) $vehicle->price_per_km);
        $this->assertEquals(0.50, (float) $vehicle->price_per_min);
        $this->assertEquals(10.00, (float) $vehicle->min_fare);
    }

    #[Test]
    public function step_point_translates_along_city_route(): void
    {
        $service = app(TaxiAppDemoModeService::class);
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('stepPoint');
        $method->setAccessible(true);

        $a = $method->invoke($service, 8, 52.2215, 6.8936, 52.2215, 6.8936);
        // Force a later wall-clock sample by sleeping past the next second boundary.
        usleep(1_100_000);
        $b = $method->invoke($service, 8, $a['lat'], $a['lng'], 52.2215, 6.8936);

        $this->assertNotEquals($a['lat'], $b['lat']);
        $this->assertArrayHasKey('heading', $b);
        $movedMeters = abs($b['lat'] - $a['lat']) * 111320
            + abs($b['lng'] - $a['lng']) * 111320 * 0.6;
        $this->assertGreaterThan(5.0, $movedMeters);
    }
}
