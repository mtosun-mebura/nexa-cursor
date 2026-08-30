<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\DriverPlanningController;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDriverPlanningTest extends TestCase
{
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
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('ride_type', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('source', 32)->nullable();
            $table->unsignedBigInteger('transport_contract_id')->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('customer_name');
            $table->timestamps();
        });

        $this->mock(ModuleDatabaseService::class, function ($mock) {
            $mock->shouldReceive('getModuleConnectionName')->andReturn('module_taxi');
        });

        Carbon::setTestNow(Carbon::parse('2026-08-26 12:00:00', 'Europe/Amsterdam'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function week_groups_own_rides_by_pickup_day_and_hides_others(): void
    {
        $driver = User::factory()->create();
        $other = User::factory()->create();

        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Dam 1, Amsterdam',
            'dropoff_address' => 'Schiphol',
            'pickup_at' => '2026-08-25 08:00:00',
            'customer_name' => 'Anna',
            'passengers' => 2,
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'Utrecht CS',
            'dropoff_address' => 'Bilthoven',
            'pickup_at' => '2026-08-24 09:30:00',
            'customer_name' => 'Bert',
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_CANCELLED,
            'pickup_address' => 'Geannuleerd',
            'dropoff_address' => 'Nergens',
            'pickup_at' => '2026-08-26 10:00:00',
            'customer_name' => 'Carla',
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $other->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Andere chauffeur',
            'dropoff_address' => 'Elders',
            'pickup_at' => '2026-08-25 11:00:00',
            'customer_name' => 'Dirk',
        ]);

        $payload = $this->planningPayload($driver, '2026-08-24');

        $this->assertSame('2026-08-24', $payload['from']);
        $this->assertSame('2026-08-30', $payload['to']);
        $this->assertSame('2026-08-26', $payload['today']);
        $this->assertCount(7, $payload['days']);

        $byDate = collect($payload['days'])->keyBy('date');
        $this->assertSame(1, $byDate['2026-08-24']['ride_count']);
        $this->assertSame('Bert', $byDate['2026-08-24']['rides'][0]['customer_name']);
        $this->assertSame('completed', $byDate['2026-08-24']['rides'][0]['status']);

        $this->assertSame(1, $byDate['2026-08-25']['ride_count']);
        $this->assertSame('Anna', $byDate['2026-08-25']['rides'][0]['customer_name']);
        $this->assertSame('Dam 1, Amsterdam', $byDate['2026-08-25']['rides'][0]['pickup_address']);

        $this->assertSame(0, $byDate['2026-08-26']['ride_count']);
        $this->assertTrue($byDate['2026-08-26']['is_today']);
    }

    #[Test]
    public function far_past_from_is_clamped_to_four_weeks_back(): void
    {
        $driver = User::factory()->create();
        $payload = $this->planningPayload($driver, '2020-01-06');

        $this->assertSame('2026-07-27', $payload['from']);
        $this->assertSame('2026-08-02', $payload['to']);
    }

    /**
     * @return array<string, mixed>
     */
    private function planningPayload(User $driver, string $from): array
    {
        $request = Request::create('/api/taxi/v1/driver/planning', 'GET', [
            'from' => $from,
        ]);
        $request->setUserResolver(fn () => $driver);
        $request->headers->set('Accept', 'application/json');

        $response = app(DriverPlanningController::class)->week(
            $request,
            app(ModuleDatabaseService::class)
        );

        $this->assertSame(200, $response->getStatusCode());

        return $response->getData(true)['data'];
    }
}
