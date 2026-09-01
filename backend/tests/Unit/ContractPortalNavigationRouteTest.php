<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Support\ContractPortalNavigationRoute;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractPortalNavigationRouteTest extends TestCase
{
    #[Test]
    public function it_uses_each_client_pickup_as_a_waypoint_then_shared_destination(): void
    {
        $route = ContractPortalNavigationRoute::fromDayItems([
            [
                'name' => 'Emma Jansen',
                'status_key' => 'planned',
                'pickup_address' => 'Kerkstraat 1, Utrecht',
                'destination_address' => 'Schoolplein 4, Utrecht',
                'legs' => [
                    [
                        'leg_key' => 'heen',
                        'leg_label' => 'Heen',
                        'status_key' => 'planned',
                        'picked_up' => false,
                        'planned_at' => '2026-08-29T07:10:00+02:00',
                        'pickup_address' => 'Kerkstraat 1, Utrecht',
                        'pickup_lat' => 52.0907,
                        'pickup_lng' => 5.1214,
                        'destination_address' => 'Schoolplein 4, Utrecht',
                        'destination_lat' => 52.0890,
                        'destination_lng' => 5.1300,
                    ],
                ],
            ],
            [
                'name' => 'Noah de Vries',
                'status_key' => 'planned',
                'pickup_address' => 'Dorpsstraat 12, De Bilt',
                'destination_address' => 'Schoolplein 4, Utrecht',
                'legs' => [
                    [
                        'leg_key' => 'heen',
                        'leg_label' => 'Heen',
                        'status_key' => 'planned',
                        'picked_up' => false,
                        'planned_at' => '2026-08-29T07:18:00+02:00',
                        'pickup_address' => 'Dorpsstraat 12, De Bilt',
                        'pickup_lat' => 52.1100,
                        'pickup_lng' => 5.1800,
                        'destination_address' => 'Schoolplein 4, Utrecht',
                        'destination_lat' => 52.0890,
                        'destination_lng' => 5.1300,
                    ],
                ],
            ],
        ]);

        $this->assertSame('heen', $route['leg_key']);
        $this->assertSame('Heen', $route['leg_label']);
        $this->assertCount(3, $route['stops']);
        $this->assertSame('pickup', $route['stops'][0]['kind']);
        $this->assertSame('Ophalen', $route['stops'][0]['label']);
        $this->assertSame('Emma Jansen', $route['stops'][0]['name']);
        $this->assertSame('Ophalen', $route['stops'][1]['label']);
        $this->assertSame('Noah de Vries', $route['stops'][1]['name']);
        $this->assertSame('dropoff', $route['stops'][2]['kind']);
        $this->assertSame('Schoolplein 4, Utrecht', $route['stops'][2]['address']);
    }

    #[Test]
    public function it_skips_absent_and_already_picked_up_clients(): void
    {
        $route = ContractPortalNavigationRoute::fromDayItems([
            [
                'name' => 'Afwezig',
                'status_key' => 'absent',
                'legs' => [
                    [
                        'leg_key' => 'heen',
                        'leg_label' => 'Heen',
                        'status_key' => 'absent',
                        'pickup_address' => 'A',
                        'destination_address' => 'School',
                        'planned_at' => '2026-08-29T07:00:00+02:00',
                    ],
                ],
            ],
            [
                'name' => 'Al in de bus',
                'status_key' => 'picked_up',
                'legs' => [
                    [
                        'leg_key' => 'heen',
                        'leg_label' => 'Heen',
                        'status_key' => 'picked_up',
                        'picked_up' => true,
                        'pickup_address' => 'B',
                        'destination_address' => 'School',
                        'planned_at' => '2026-08-29T07:05:00+02:00',
                    ],
                ],
            ],
            [
                'name' => 'Nog ophalen',
                'status_key' => 'planned',
                'legs' => [
                    [
                        'leg_key' => 'heen',
                        'leg_label' => 'Heen',
                        'status_key' => 'planned',
                        'picked_up' => false,
                        'pickup_address' => 'C-straat 3',
                        'destination_address' => 'School',
                        'planned_at' => '2026-08-29T07:20:00+02:00',
                    ],
                ],
            ],
        ]);

        $this->assertCount(2, $route['stops']);
        $this->assertSame('Ophalen', $route['stops'][0]['label']);
        $this->assertSame('Nog ophalen', $route['stops'][0]['name']);
        $this->assertSame('Afzetten', $route['stops'][1]['label']);
    }

    #[Test]
    public function it_uses_retour_wave_when_morning_is_done(): void
    {
        $route = ContractPortalNavigationRoute::fromDayItems([
            [
                'name' => 'Emma',
                'status_key' => 'planned',
                'legs' => [
                    [
                        'leg_key' => 'heen',
                        'leg_label' => 'Heen',
                        'status_key' => 'completed',
                        'pickup_address' => 'Thuis',
                        'destination_address' => 'School',
                        'planned_at' => '2026-08-29T07:10:00+02:00',
                    ],
                    [
                        'leg_key' => 'retour',
                        'leg_label' => 'Retour',
                        'status_key' => 'planned',
                        'picked_up' => false,
                        'pickup_address' => 'School',
                        'destination_address' => 'Thuis Emma',
                        'planned_at' => '2026-08-29T15:10:00+02:00',
                    ],
                ],
            ],
        ]);

        $this->assertSame('retour', $route['leg_key']);
        $this->assertSame('School', $route['stops'][0]['address']);
        $this->assertSame('Thuis Emma', $route['stops'][1]['address']);
    }

    #[Test]
    public function it_falls_back_to_client_pickups_when_there_is_no_ride_today(): void
    {
        $route = ContractPortalNavigationRoute::fromDayItems([
            [
                'name' => 'Mehmet',
                'status_key' => 'none',
                'pickup_address' => 'Laan 1, Enschede',
                'pickup_lat' => 52.22,
                'pickup_lng' => 6.89,
                'destination_address' => null,
                'legs' => [],
            ],
            [
                'name' => 'Mert',
                'status_key' => 'none',
                'pickup_address' => 'Laan 2, Enschede',
                'legs' => [],
            ],
        ]);

        $this->assertCount(2, $route['stops']);
        $this->assertSame('Ophalen', $route['stops'][0]['label']);
        $this->assertSame('Mehmet', $route['stops'][0]['name']);
        $this->assertSame('Ophalen', $route['stops'][1]['label']);
        $this->assertSame('Mert', $route['stops'][1]['name']);
    }
}
