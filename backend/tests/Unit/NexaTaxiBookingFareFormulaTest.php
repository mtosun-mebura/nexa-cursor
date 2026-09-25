<?php

namespace Tests\Unit;

use App\Models\Module;
use App\Modules\NexaTaxi\Models\DefaultRate;
use App\Services\ModuleDatabaseService;
use App\Services\NexaTaxiBookingPricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Officiële NL-meterformule: start + (km × km-tarief) + (min × tijdtarief).
 */
class NexaTaxiBookingFareFormulaTest extends TestCase
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
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi')->andReturnNull();
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
            $mock->shouldReceive('supportsModuleDatabases')->andReturn(true);
        });

        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('person_range')->nullable();
            $table->boolean('active')->default(true);
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('price_per_min', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('default_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('person_range')->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('price_per_min', 10, 2)->nullable();
            $table->decimal('cleaning_costs', 10, 2)->nullable();
            $table->decimal('evening_night_multiplier', 4, 2)->default(1.20);
            $table->unsignedTinyInteger('evening_night_from_hour')->default(22);
            $table->unsignedTinyInteger('evening_night_until_hour')->default(6);
            $table->timestamps();
        });

        // Wettelijke maximumtarieven personenauto 2026.
        DefaultRate::on('module_taxi')->create([
            'person_range' => '1-4',
            'base_fare' => 4.31,
            'min_fare' => 59.41,
            'price_per_km' => 3.17,
            'price_per_min' => 0.52,
            'evening_night_multiplier' => 1.0,
            'evening_night_from_hour' => 22,
            'evening_night_until_hour' => 6,
        ]);
    }

    #[Test]
    public function official_2026_example_five_km_twenty_minutes(): void
    {
        // 5 km / 15 km/u-vloer = 20 min.
        $quotes = $this->quotes(5000, 1200);

        // 4,31 + (5 × 3,17) + (20 × 0,52) = 30,56
        $this->assertSame(30.56, (float) $quotes['offers'][0]['price']);
    }

    #[Test]
    public function longer_than_floor_duration_increases_fare(): void
    {
        $atFloor = $this->quotes(5000, 0);      // vloer 20 min
        $withExtra = $this->quotes(5000, 1500); // 25 min > vloer

        $this->assertSame(30.56, (float) $atFloor['offers'][0]['price']);
        $this->assertSame(33.16, (float) $withExtra['offers'][0]['price']);
        $this->assertGreaterThan(
            (float) $atFloor['offers'][0]['price'],
            (float) $withExtra['offers'][0]['price']
        );
    }

    #[Test]
    public function bijvank_to_station_style_route_is_not_distance_only(): void
    {
        // ~4,57 km; zonder tijdvloer zou dit €18,80 zijn (alleen km).
        $quotes = $this->quotes(4570, 0);
        $price = (float) $quotes['offers'][0]['price'];

        $this->assertGreaterThan(27.0, $price);
        $this->assertLessThan(30.0, $price);
        // 4,31 + 4,57×3,17 + (4,57/15×60)×0,52 ≈ 28,30
        $this->assertEqualsWithDelta(28.30, $price, 0.05);
    }

    #[Test]
    public function duration_floor_uses_city_average_speed(): void
    {
        $floored = app(NexaTaxiBookingPricingService::class)->ensureTaxiDurationFloor(4570, 0);
        $this->assertSame(1097, $floored); // 4.57 / 15 * 3600

        $kept = app(NexaTaxiBookingPricingService::class)->ensureTaxiDurationFloor(4570, 1500);
        $this->assertSame(1500, $kept);
    }

    /**
     * @return array<string, mixed>
     */
    private function quotes(int $distanceMeters, int $durationSeconds): array
    {
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => [
                'offer_display_mode' => 'person_range',
                'person_range_base_price_multiplier' => 1.0,
                'use_evening_night_tariff' => false,
            ],
        ]);

        return app(NexaTaxiBookingPricingService::class)->buildQuotes($config, [
            'distance_meters' => $distanceMeters,
            'duration_seconds' => $durationSeconds,
            'passengers' => 1,
            'return_trip' => false,
            'pickup_at' => '2026-05-20 12:00:00',
            'baggage' => [],
            'special_baggage' => [],
        ]);
    }
}
