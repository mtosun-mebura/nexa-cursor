<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Support\ContractPortalLegLabel;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Documenteert de multi-leg today-contract: ochtend=heen, middag=retour.
 * Statuskeys blijven gelijk aan RideStop/RideRequest enums.
 */
class ContractPortalMultiLegContractTest extends TestCase
{
    public function test_two_pickup_times_map_to_heen_and_retour(): void
    {
        $morning = ContractPortalLegLabel::forPlannedAt(Carbon::parse('2026-08-12 07:45:00', 'Europe/Amsterdam'));
        $afternoon = ContractPortalLegLabel::forPlannedAt(Carbon::parse('2026-08-12 15:30:00', 'Europe/Amsterdam'));

        $this->assertSame(['heen', 'Heen'], $morning);
        $this->assertSame(['retour', 'Retour'], $afternoon);
    }

    #[DataProvider('stopStatusProvider')]
    public function test_ride_stop_status_constants_used_by_portal(string $constant, string $expected): void
    {
        $this->assertSame($expected, constant(RideStop::class.'::'.$constant));
    }

    public static function stopStatusProvider(): array
    {
        return [
            ['STATUS_PLANNED', 'planned'],
            ['STATUS_ARRIVED', 'arrived'],
            ['STATUS_PICKED_UP', 'picked_up'],
            ['STATUS_COMPLETED', 'completed'],
            ['STATUS_SKIPPED', 'skipped'],
            ['STOP_TYPE_PICKUP', 'pickup'],
            ['STOP_TYPE_DESTINATION', 'destination'],
        ];
    }

    public function test_ride_request_completed_constant(): void
    {
        $this->assertSame('completed', RideRequest::STATUS_COMPLETED);
        $this->assertSame('assigned', RideRequest::STATUS_ASSIGNED);
    }
}
