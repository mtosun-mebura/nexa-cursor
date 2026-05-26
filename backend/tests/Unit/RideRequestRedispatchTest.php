<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RideRequestRedispatchTest extends TestCase
{
    #[Test]
    public function completed_ride_cannot_redispatch(): void
    {
        $ride = new RideRequest(['status' => RideRequest::STATUS_COMPLETED, 'company_id' => 1]);

        $this->assertFalse($ride->canRedispatchToDrivers());
    }

    #[Test]
    public function unpaid_pending_payment_ride_cannot_redispatch(): void
    {
        $ride = new RideRequest([
            'status' => RideRequest::STATUS_PENDING_PAYMENT,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'company_id' => 1,
        ]);

        $this->assertFalse($ride->canRedispatchToDrivers());
    }

    #[Test]
    public function assigned_ride_can_redispatch(): void
    {
        $ride = new RideRequest([
            'status' => RideRequest::STATUS_ASSIGNED,
            'company_id' => 2,
        ]);

        $this->assertTrue($ride->canRedispatchToDrivers());
    }
}
