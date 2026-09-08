<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Modules\NexaTaxi\Services\TaxiGpsLiveMapDemoService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiGpsLiveMapDemoServiceTest extends TestCase
{
    #[Test]
    public function empty_fleet_gets_one_driving_demo_taxi(): void
    {
        $service = app(TaxiGpsLiveMapDemoService::class);
        $vehicles = $service->apply([], 52.2215, 6.8937, 1000.0);

        $this->assertCount(1, $vehicles);
        $this->assertTrue($vehicles[0]['demo']);
        $this->assertTrue($vehicles[0]['is_online']);
        $this->assertSame('DEMO-1', $vehicles[0]['license_plate']);
        $this->assertNotEquals(52.2215, $vehicles[0]['lat']);
        $this->assertArrayHasKey('heading', $vehicles[0]);
        $this->assertGreaterThanOrEqual(0, $vehicles[0]['heading']);
        $this->assertLessThan(360, $vehicles[0]['heading']);
    }

    #[Test]
    public function existing_vehicle_moves_over_time(): void
    {
        $service = app(TaxiGpsLiveMapDemoService::class);
        $base = [
            'id' => 'driver-9',
            'lat' => 52.2215,
            'lng' => 6.8937,
            'license_plate' => '12-ABC-3',
        ];

        $first = $service->apply([$base], 52.2215, 6.8937, 1000.0);
        $later = $service->apply([$base], 52.2215, 6.8937, 1008.0);

        $this->assertTrue($first[0]['demo']);
        $this->assertSame('driver-9', $first[0]['id']);
        $this->assertNotEquals($first[0]['lat'], $later[0]['lat']);
        $this->assertNotEquals($first[0]['lng'], $later[0]['lng']);
        $this->assertArrayHasKey('heading', $first[0]);
    }

    #[Test]
    public function qa_plates_follow_amsterdam_street_loop(): void
    {
        $service = app(TaxiGpsLiveMapDemoService::class);
        $vehicles = $service->apply([
            ['id' => 'driver-1', 'license_plate' => '12-GPS-1'],
        ], 52.3728, 4.8936, 10.0);

        $this->assertTrue($vehicles[0]['lat'] > 52.36 && $vehicles[0]['lat'] < 52.39);
        $this->assertTrue($vehicles[0]['lng'] > 4.88 && $vehicles[0]['lng'] < 4.91);
        $this->assertArrayHasKey('heading', $vehicles[0]);
    }

    #[Test]
    public function configured_fleet_keeps_every_registered_plate_in_the_company_city(): void
    {
        $service = app(TaxiGpsLiveMapDemoService::class);
        $fleet = [
            ['id' => 'vehicle-1', 'license_plate' => 'G-123-AB', 'car_style' => 'sedan'],
            ['id' => 'vehicle-2', 'license_plate' => 'H-456-CD', 'car_style' => 'van'],
            ['id' => 'vehicle-3', 'license_plate' => 'K-789-EF', 'car_style' => 'bus'],
        ];

        $vehicles = $service->apply($fleet, 52.2215, 6.8937, 40.0);

        $this->assertCount(3, $vehicles);
        $this->assertSame(['G-123-AB', 'H-456-CD', 'K-789-EF'], array_column($vehicles, 'license_plate'));
        $this->assertTrue($vehicles[0]['demo']);
        $this->assertNotEquals($vehicles[0]['lat'], $vehicles[1]['lat']);
        foreach ($vehicles as $vehicle) {
            $this->assertTrue($vehicle['lat'] > 52.20 && $vehicle['lat'] < 52.24);
            $this->assertTrue($vehicle['lng'] > 6.88 && $vehicle['lng'] < 6.91);
        }
    }

    #[Test]
    public function company_without_coordinates_uses_city_center(): void
    {
        $company = Company::query()->create([
            'name' => 'Stadstaxi Enschede',
            'city' => 'Enschede',
            'is_active' => true,
        ]);
        $service = app(TaxiGpsLiveMapDemoService::class);
        [$lat, $lng] = $service->centerForCompany($company, 52.3676, 4.9041);

        $this->assertEqualsWithDelta(52.2205, $lat, 0.002);
        $this->assertEqualsWithDelta(6.8958, $lng, 0.002);
    }

    #[Test]
    public function gravenhage_company_centers_on_den_haag_not_amsterdam(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Demo Den Haag',
            'city' => "'s-Gravenhage",
            'is_active' => true,
        ]);
        $service = app(TaxiGpsLiveMapDemoService::class);
        [$lat, $lng] = $service->centerForCompany($company, 52.3676, 4.9041);

        $this->assertEqualsWithDelta(52.0705, $lat, 0.002);
        $this->assertEqualsWithDelta(4.3007, $lng, 0.002);
        $this->assertFalse(abs($lat - 52.3676) < 0.05);
    }

    #[Test]
    public function stored_amsterdam_coords_lose_to_enschede_city(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Enschede',
            'city' => 'Enschede',
            'latitude' => '52.3676',
            'longitude' => '4.9041',
            'is_active' => true,
        ]);
        $service = app(TaxiGpsLiveMapDemoService::class);
        [$lat, $lng] = $service->centerForCompany($company, 52.3676, 4.9041);

        $this->assertEqualsWithDelta(52.2205, $lat, 0.002);
        $this->assertEqualsWithDelta(6.8958, $lng, 0.002);
    }

    #[Test]
    public function heading_follows_the_path_direction(): void
    {
        $service = app(TaxiGpsLiveMapDemoService::class);
        $a = $service->apply([['id' => 'd1', 'license_plate' => '12-GPS-1']], 52.3728, 4.8936, 0.0);
        $b = $service->apply([['id' => 'd1', 'license_plate' => '12-GPS-1']], 52.3728, 4.8936, 2.0);

        $this->assertNotEquals($a[0]['lat'], $b[0]['lat']);
        $this->assertIsNumeric($a[0]['heading']);
        $this->assertIsNumeric($b[0]['heading']);
    }
}
