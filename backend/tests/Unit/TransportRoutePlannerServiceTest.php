<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Services\TransportRoutePlannerService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TransportRoutePlannerServiceTest extends TestCase
{
    public function test_pickup_closest_to_destination_is_last_before_arrival(): void
    {
        $service = new TransportRoutePlannerService;

        $group = new TransportGroup([
            'destination_address' => 'Openbare Basisschool (OBS) Roombeek, Bosuilstraat, Enschede, Nederland',
            'destination_lat' => 52.2322563,
            'destination_lng' => 6.8961133,
            'destination_arrival_time' => '08:00',
        ]);

        $template = new TransportRouteTemplate([
            'driver_start_mode' => 'first_stop',
            'buffer_seconds' => 120,
        ]);

        $members = new Collection([
            $this->memberWithPassenger(1, 'Burcu Tosun-Aksakal', 'Zeelandstraat 16', 52.2037369, 6.8826177),
            $this->memberWithPassenger(2, 'Mehmet Ali Tosun', 'Deurningerstraat 155', 52.2290894, 6.8894108),
            $this->memberWithPassenger(3, 'Mert Tosun', 'Maanstraat 58', 52.2308440, 6.8589445),
        ]);

        $result = $service->planRoute($group, $template, $members);
        $pickupAddresses = array_map(
            fn (array $stop) => $stop['address'],
            array_values(array_filter($result['stops'], fn (array $stop) => $stop['stop_type'] === 'pickup'))
        );

        $this->assertSame([
            'Zeelandstraat 16, Enschede, Nederland',
            'Maanstraat 58, Enschede, Nederland',
            'Deurningerstraat 155, Enschede, Nederland',
        ], $pickupAddresses);
    }

    private function memberWithPassenger(
        int $passengerId,
        string $name,
        string $address,
        float $lat,
        float $lng,
    ): TransportGroupMember {
        $passenger = new TransportPassenger([
            'id' => $passengerId,
            'first_name' => $name,
            'last_name' => '',
            'pickup_address' => $address.', Enschede, Nederland',
            'pickup_lat' => $lat,
            'pickup_lng' => $lng,
        ]);
        $passenger->id = $passengerId;

        $member = new TransportGroupMember([
            'transport_passenger_id' => $passengerId,
        ]);
        $member->setRelation('passenger', $passenger);

        return $member;
    }
}
