<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\DriverAvailabilityController;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\DriverScheduleService;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiDriverScheduleTest extends TestCase
{
    private string $conn = 'module_taxi';

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->andReturnNull();
            $mock->shouldReceive('getModuleConnectionName')->andReturn('module_taxi');
        });

        Schema::connection($this->conn)->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('type')->nullable();
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
        Schema::connection($this->conn)->create('driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedBigInteger('series_id')->nullable();
            $table->string('weekdays', 32)->nullable();
            $table->boolean('repeat_weekly')->default(false);
            $table->date('repeat_until')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });

        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', ContractTransportTimezone::TIMEZONE));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function weekly_repeat_creates_multiple_shifts_and_blocks_overlap(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();

        $created = $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            true,
            '2026-09-25',
            $driver->id
        );

        $this->assertCount(1, $created);
        $this->assertTrue((bool) $created[0]->repeat_weekly);
        $this->assertSame('2026-09-25', $created[0]->repeat_until?->toDateString() ?? (string) $created[0]->repeat_until);

        $events = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-09-11',
            '2026-09-26',
            (int) $driver->id,
            null
        );
        $this->assertCount(3, $events);
        $this->assertSame('2026-09-11T08:00:00', $events[0]['start']);
        $this->assertSame('2026-09-18T08:00:00', $events[1]['start']);
        $this->assertSame('2026-09-25T08:00:00', $events[2]['start']);

        $this->expectException(ValidationException::class);
        $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '09:00',
            '11:00',
            null,
            false,
            null,
            $driver->id
        );
    }

    #[Test]
    public function open_ended_weekly_repeat_keeps_appearing_without_until(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();

        $created = $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            true,
            null,
            $driver->id,
            [5]
        );

        $this->assertCount(1, $created);
        $this->assertNull($created[0]->repeat_until);

        $later = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-10-09',
            '2026-10-10',
            (int) $driver->id,
            null
        );
        $this->assertCount(1, $later);
        $this->assertSame('2026-10-09T08:00:00', $later[0]['start']);
    }

    #[Test]
    public function unchecked_weekly_repeat_runs_open_ended_and_ignores_until(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();

        $created = $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            false,
            '2026-09-25',
            $driver->id,
            [1, 5]
        );

        $this->assertCount(1, $created);
        $this->assertFalse((bool) $created[0]->repeat_weekly);
        $this->assertNull($created[0]->repeat_until);

        $events = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-09-11',
            '2026-09-15',
            (int) $driver->id,
            null
        );
        $this->assertCount(2, $events);
        $this->assertSame('2026-09-11T08:00:00', $events[0]['start']);
        $this->assertSame('2026-09-14T08:00:00', $events[1]['start']);

        $nextWeek = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-09-18',
            '2026-09-19',
            (int) $driver->id,
            null
        );
        $this->assertCount(1, $nextWeek);
        $this->assertSame('2026-09-18T08:00:00', $nextWeek[0]['start']);

        $later = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-10-09',
            '2026-10-10',
            (int) $driver->id,
            null
        );
        $this->assertCount(1, $later);
        $this->assertSame('2026-10-09T08:00:00', $later[0]['start']);
    }

    #[Test]
    public function clearing_until_date_makes_existing_schedule_open_ended(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();

        $created = $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            true,
            '2026-09-25',
            $driver->id,
            [5]
        );

        $this->service()->updateShift(
            $created[0],
            $this->conn,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            [5],
            true,
            null
        );

        $created[0]->refresh();
        $this->assertTrue((bool) $created[0]->repeat_weekly);
        $this->assertNull($created[0]->repeat_until);

        $later = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-10-09',
            '2026-10-10',
            (int) $driver->id,
            null
        );
        $this->assertCount(1, $later);
        $this->assertSame('2026-10-09T08:00:00', $later[0]['start']);
    }

    #[Test]
    public function unchecking_weekly_repeat_clears_until_and_keeps_running(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();

        $created = $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            true,
            '2026-09-25',
            $driver->id,
            [5]
        );

        $this->service()->updateShift(
            $created[0],
            $this->conn,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '12:00',
            null,
            [5],
            false,
            '2026-09-25'
        );

        $created[0]->refresh();
        $this->assertFalse((bool) $created[0]->repeat_weekly);
        $this->assertNull($created[0]->repeat_until);

        $later = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-10-09',
            '2026-10-10',
            (int) $driver->id,
            null
        );
        $this->assertCount(1, $later);
        $this->assertSame('2026-10-09T08:00:00', $later[0]['start']);
    }

    #[Test]
    public function vehicles_api_hides_car_selected_by_another_online_driver(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();
        $other = User::factory()->create(['company_id' => $company->id]);
        $other->assignRole('chauffeur');
        $second = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Tweede auto',
            'license_plate' => 'XX-999-YY',
            'active' => true,
        ]);

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $other->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($driver);
        $request = Request::create('/api/taxi/v1/driver/vehicles', 'GET');
        $request->setUserResolver(fn () => $driver);
        $request->attributes->set('taxi_company_id', $company->id);

        $payload = app(DriverAvailabilityController::class)
            ->vehicles($request, app(ModuleDatabaseService::class))
            ->getData(true);

        $ids = collect($payload['data'])->pluck('id')->all();
        $this->assertFalse($payload['locked']);
        $this->assertNotContains((int) $vehicle->id, $ids);
        $this->assertContains((int) $second->id, $ids);
    }

    #[Test]
    public function scheduled_driver_gets_locked_vehicle_and_other_driver_cannot_see_it(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();
        $other = User::factory()->create(['company_id' => $company->id]);
        $other->assignRole('chauffeur');

        $this->service()->createShifts(
            $this->conn,
            $company->id,
            $driver->id,
            $vehicle->id,
            '2026-09-11',
            '08:00',
            '17:00',
            'Ochtenddienst',
            false,
            null,
            $driver->id
        );

        $locked = $this->service()->vehiclesPayloadForDriver($this->conn, (int) $company->id, (int) $driver->id);
        $this->assertTrue($locked['locked']);
        $this->assertSame((int) $vehicle->id, $locked['assigned_vehicle']['id']);

        $otherPayload = $this->service()->vehiclesPayloadForDriver($this->conn, (int) $company->id, (int) $other->id);
        $this->assertFalse($otherPayload['locked']);
        $this->assertSame([], $otherPayload['data']);

        $events = $this->service()->agendaEvents(
            $this->conn,
            (int) $company->id,
            '2026-09-11',
            '2026-09-12',
            (int) $driver->id,
            null
        );
        $this->assertCount(1, $events);
        $this->assertSame('driver_schedule', $events[0]['extendedProps']['event_kind']);
        $this->assertSame('Ochtenddienst', $events[0]['extendedProps']['notes']);
        $this->assertSame((int) $vehicle->id, $events[0]['extendedProps']['vehicle_id']);
    }

    #[Test]
    public function persist_rejects_vehicle_taken_by_another_driver(): void
    {
        [$company, $driver, $vehicle] = $this->companyDriverAndVehicle();
        $other = User::factory()->create(['company_id' => $company->id]);
        $other->assignRole('chauffeur');

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $other->id,
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($driver);
        $request = Request::create('/api/taxi/v1/driver/availability', 'PUT', [
            'is_online' => true,
            'vehicle_id' => $vehicle->id,
        ]);
        $request->setUserResolver(fn () => $driver);
        $request->attributes->set('taxi_company_id', $company->id);

        $response = app(DriverAvailabilityController::class)->update($request, app(ModuleDatabaseService::class));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('vehicle_occupied', $response->getData(true)['code'] ?? null);
    }

    /**
     * @return array{0: Company, 1: User, 2: Vehicle}
     */
    private function companyDriverAndVehicle(): array
    {
        $company = Company::query()->create(['name' => 'Plan Co', 'is_active' => true]);
        $driver = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Ahmet',
            'last_name' => 'Kaya',
        ]);
        $driver->assignRole('chauffeur');
        $vehicle = Vehicle::on($this->conn)->create([
            'company_id' => $company->id,
            'name' => 'Mercedes E klasse',
            'license_plate' => 'AB-123-CD',
            'active' => true,
        ]);

        return [$company, $driver, $vehicle];
    }

    private function service(): DriverScheduleService
    {
        return app(DriverScheduleService::class);
    }
}
