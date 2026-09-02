<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\DriverAvailabilityController;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DriverAvailabilityLocationTest extends TestCase
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
            $table->string('license_plate')->nullable();
            $table->boolean('active')->default(true);
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
    public function going_offline_without_coords_keeps_last_known_position(): void
    {
        $company = Company::query()->create(['name' => 'Loc Co', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($driver);

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $driver->id,
            'company_id' => $company->id,
            'is_online' => true,
            'lat' => 52.1234567,
            'lng' => 4.1234567,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $request = Request::create('/api/taxi/v1/driver/availability', 'PUT', [
            'is_online' => false,
        ]);
        $request->setUserResolver(fn () => $driver);
        $request->attributes->set('taxi_company_id', $company->id);

        $response = app(DriverAvailabilityController::class)->update($request, app(ModuleDatabaseService::class));
        $payload = $response->getData(true);

        $this->assertFalse($payload['data']['is_online']);
        $this->assertEquals(52.1234567, (float) $payload['data']['lat']);
        $this->assertEquals(4.1234567, (float) $payload['data']['lng']);

        $row = DriverAvailability::on($this->conn)->where('driver_id', $driver->id)->first();
        $this->assertFalse($row->is_online);
        $this->assertEquals(52.1234567, (float) $row->lat);
    }
}
