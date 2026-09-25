<?php

namespace Tests\Feature;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsService;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDriverEarningsServiceTest extends TestCase
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
            $table->unsignedBigInteger('outbound_driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('ride_type', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->unsignedBigInteger('transport_contract_id')->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->json('booking_payload')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });

        $this->mock(ModuleDatabaseService::class, function ($mock) {
            $mock->shouldReceive('getModuleConnectionName')->andReturn('module_taxi');
        });

        Carbon::setTestNow(Carbon::parse('2026-09-17 12:00:00', 'Europe/Amsterdam'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function week_and_month_totals_only_include_rides_in_that_period(): void
    {
        $this->seedRide(10, '2026-09-17 09:00:00', 30); // donderdag deze week
        $this->seedRide(20, '2026-09-15 11:00:00', 20); // dinsdag deze week
        $this->seedRide(40, '2026-09-08 08:00:00', 10); // vorige week, zelfde maand
        $this->seedRide(50, '2026-08-20 16:00:00', 50); // vorige maand

        $service = app(TaxiDriverEarningsService::class);

        $day = $service->forDriverPeriod(1, 7, '2026-09-17', TaxiDriverEarningsService::PERIOD_DAY, false);
        $this->assertSame('day', $day['period']);
        $this->assertTrue($day['is_current']);
        $this->assertSame(30.0, $day['period_total']);
        $this->assertSame(1, $day['ride_count']);
        $this->assertSame('Totaal deze dag', $day['total_label']);

        $week = $service->forDriverPeriod(1, 7, '2026-09-17', TaxiDriverEarningsService::PERIOD_WEEK, false);
        $this->assertSame('week', $week['period']);
        $this->assertSame('2026-09-14', $week['from']);
        $this->assertSame('2026-09-17', $week['to']);
        $this->assertTrue($week['is_current']);
        $this->assertSame(50.0, $week['period_total']);
        $this->assertSame(2, $week['ride_count']);
        $this->assertSame('Totaal deze week', $week['total_label']);

        $month = $service->forDriverPeriod(1, 7, '2026-09-17', TaxiDriverEarningsService::PERIOD_MONTH, true);
        $this->assertSame('month', $month['period']);
        $this->assertSame('2026-09-01', $month['from']);
        $this->assertSame('2026-09-17', $month['to']);
        $this->assertTrue($month['is_current']);
        $this->assertSame(60.0, $month['period_total']);
        $this->assertSame(3, $month['ride_count']);
        $this->assertSame('Totaal deze maand', $month['total_label']);
        $this->assertNull($month['month']);
    }

    private function seedRide(int $id, string $completedAtAmsterdam, float $amount): void
    {
        $completedUtc = Carbon::parse($completedAtAmsterdam, 'Europe/Amsterdam')->utc();

        $ride = new RideRequest;
        $ride->setConnection('module_taxi');
        $ride->forceFill([
            'id' => $id,
            'company_id' => 1,
            'driver_id' => 7,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'quoted_price' => $amount,
            'customer_name' => 'Klant '.$id,
            'passengers' => 1,
            'created_at' => $completedUtc,
            'updated_at' => $completedUtc,
        ]);
        $ride->save();
    }
}
