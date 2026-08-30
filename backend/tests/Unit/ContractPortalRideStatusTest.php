<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Support\ContractPortalRideStatus;
use Carbon\Carbon;
use Tests\TestCase;

class ContractPortalRideStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_planned_ride_in_the_past_becomes_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 22:10:00', 'Europe/Amsterdam'));

        $this->assertSame(
            'expired',
            ContractPortalRideStatus::applyExpiry(
                'planned',
                Carbon::parse('2026-08-28 07:43:00', 'Europe/Amsterdam')
            )
        );
        $this->assertSame('Verlopen', ContractPortalRideStatus::label('expired'));
    }

    public function test_future_planned_ride_stays_planned(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 06:00:00', 'Europe/Amsterdam'));

        $this->assertSame(
            'planned',
            ContractPortalRideStatus::applyExpiry(
                'planned',
                Carbon::parse('2026-08-29 07:43:00', 'Europe/Amsterdam')
            )
        );
    }

    public function test_active_or_finished_rides_are_not_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 22:10:00', 'Europe/Amsterdam'));
        $past = Carbon::parse('2026-08-28 07:43:00', 'Europe/Amsterdam');

        $this->assertSame('picked_up', ContractPortalRideStatus::applyExpiry('picked_up', $past));
        $this->assertSame('completed', ContractPortalRideStatus::applyExpiry('completed', $past));
        $this->assertSame('en_route', ContractPortalRideStatus::applyExpiry('en_route', $past));
    }

    public function test_absent_legs_are_not_counted(): void
    {
        $this->assertSame(0, ContractPortalRideStatus::countableRideCount([
            'day_status' => 'scheduled',
            'status_key' => 'absent',
            'legs' => [
                ['status_key' => 'absent'],
                ['status_key' => 'expired'],
            ],
        ]));
        $this->assertSame(1, ContractPortalRideStatus::countableRideCount([
            'day_status' => 'scheduled',
            'status_key' => 'planned',
            'legs' => [
                ['status_key' => 'planned'],
                ['status_key' => 'absent'],
            ],
        ]));
        $this->assertSame(0, ContractPortalRideStatus::countableRideCount([
            'day_status' => 'absent',
            'legs' => [
                ['status_key' => 'planned'],
            ],
        ]));
        $this->assertTrue(ContractPortalRideStatus::allLegsAbsent([
            ['status_key' => 'absent'],
            ['status_key' => 'absent'],
        ]));
        $this->assertFalse(ContractPortalRideStatus::allLegsAbsent([
            ['status_key' => 'absent'],
            ['status_key' => 'planned'],
        ]));
    }
}
