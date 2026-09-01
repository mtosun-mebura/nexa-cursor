<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use PHPUnit\Framework\TestCase;

class RideRequestEarningsAmountTest extends TestCase
{
    public function test_full_amount_for_completing_driver(): void
    {
        $ride = new RideRequest([
            'driver_id' => 10,
            'outbound_driver_id' => null,
            'final_price' => 42.5,
            'quoted_price' => 40,
        ]);

        $this->assertSame(42.5, $ride->earningsAmountForDriver(10));
        $this->assertNull($ride->earningsAmountForDriver(99));
    }

    public function test_falls_back_to_quoted_price(): void
    {
        $ride = new RideRequest([
            'driver_id' => 5,
            'quoted_price' => 18.75,
        ]);

        $this->assertSame(18.75, $ride->earningsAmountForDriver(5));
    }

    public function test_outbound_driver_gets_leg_share_on_return_trip(): void
    {
        $ride = new RideRequest([
            'driver_id' => 20,
            'outbound_driver_id' => 10,
            'quoted_price' => 40,
            'booking_payload' => [
                'return_trip' => true,
                'logic' => ['return_price_multiplier' => 2],
            ],
        ]);

        $this->assertSame(20.0, $ride->earningsAmountForDriver(10));
        $this->assertSame(40.0, $ride->earningsAmountForDriver(20));
    }
}
