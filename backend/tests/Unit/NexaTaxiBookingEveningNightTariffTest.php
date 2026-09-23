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

class NexaTaxiBookingEveningNightTariffTest extends TestCase
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

        DefaultRate::on('module_taxi')->create([
            'person_range' => '1-4',
            'base_fare' => 10,
            'min_fare' => 0,
            'price_per_km' => 2,
            'price_per_min' => 1,
            'evening_night_multiplier' => 1.5,
            'evening_night_from_hour' => 22,
            'evening_night_until_hour' => 6,
        ]);
    }

    #[Test]
    public function night_ride_uses_tarieven_multiplier_when_checkbox_is_on(): void
    {
        $quotes = $this->quotes([
            'use_evening_night_tariff' => true,
        ], '2026-05-20 23:15:00');

        // 10 + (10×2×1,5) + (40×1×1,5) = 100
        $this->assertSame(100.0, (float) $quotes['offers'][0]['price']);
    }

    #[Test]
    public function day_ride_does_not_apply_evening_night_multiplier(): void
    {
        $quotes = $this->quotes([
            'use_evening_night_tariff' => true,
        ], '2026-05-20 12:00:00');

        // 10 + (10×2) + (40×1) = 70 (40 min = 15 km/u-vloer over 10 km)
        $this->assertSame(70.0, (float) $quotes['offers'][0]['price']);
    }

    #[Test]
    public function night_ride_skips_surcharge_when_checkbox_is_off(): void
    {
        $quotes = $this->quotes([
            'use_evening_night_tariff' => false,
        ], '2026-05-20 23:15:00');

        $this->assertSame(70.0, (float) $quotes['offers'][0]['price']);
    }

    #[Test]
    public function custom_time_window_from_tarieven_is_respected(): void
    {
        DefaultRate::on('module_taxi')->where('person_range', '1-4')->update([
            'evening_night_from_hour' => 8,
            'evening_night_until_hour' => 18,
        ]);

        $inside = $this->quotes(['use_evening_night_tariff' => true], '2026-05-20 10:00:00');
        $outside = $this->quotes(['use_evening_night_tariff' => true], '2026-05-20 20:00:00');

        $this->assertSame(100.0, (float) $inside['offers'][0]['price']);
        $this->assertSame(70.0, (float) $outside['offers'][0]['price']);
    }

    #[Test]
    public function marketplace_quote_uses_platform_rates_not_tenant_rates(): void
    {
        DefaultRate::on('module_taxi')->create([
            'company_id' => 42,
            'person_range' => '1-4',
            'base_fare' => 100,
            'min_fare' => 0,
            'price_per_km' => 10,
            'price_per_min' => 10,
            'evening_night_multiplier' => 1.0,
            'evening_night_from_hour' => 22,
            'evening_night_until_hour' => 6,
        ]);

        $marketplace = $this->quotes(['use_evening_night_tariff' => false], '2026-05-20 12:00:00');
        $tenantSite = $this->quotes(['use_evening_night_tariff' => false], '2026-05-20 12:00:00', 42);

        $this->assertSame(70.0, (float) $marketplace['offers'][0]['price']);
        // 100 + (10×10) + (40×10) = 600
        $this->assertSame(600.0, (float) $tenantSite['offers'][0]['price']);
    }

    #[Test]
    public function tenant_without_own_rates_falls_back_to_platform(): void
    {
        $quotes = $this->quotes(['use_evening_night_tariff' => false], '2026-05-20 12:00:00', 99);

        $this->assertSame(70.0, (float) $quotes['offers'][0]['price']);
    }

    /**
     * @param  array<string, mixed>  $logic
     * @return array<string, mixed>
     */
    private function quotes(array $logic, string $pickupAt, ?int $tenantCompanyId = null): array
    {
        $config = app(NexaTaxiBookingPricingService::class)->mergeSectionConfig([
            'logic' => array_merge([
                'offer_display_mode' => 'person_range',
                'person_range_base_price_multiplier' => 1.0,
            ], $logic),
        ]);

        return app(NexaTaxiBookingPricingService::class)->buildQuotes($config, [
            'distance_meters' => 10000,
            // 40 min: gelijk aan 15 km/u-vloer over 10 km (geen free-flow-onderschatting).
            'duration_seconds' => 2400,
            'passengers' => 1,
            'return_trip' => false,
            'pickup_at' => $pickupAt,
            'baggage' => [],
            'special_baggage' => [],
        ], $tenantCompanyId);
    }
}
