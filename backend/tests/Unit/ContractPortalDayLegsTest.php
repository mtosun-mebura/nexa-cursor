<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Support\ContractPortalDayLegs;
use Tests\TestCase;

class ContractPortalDayLegsTest extends TestCase
{
    public function test_duplicate_morning_legs_collapse_to_one_heen(): void
    {
        $legs = ContractPortalDayLegs::uniqueBySlot([
            [
                'leg_key' => 'heen',
                'leg_label' => 'Heen',
                'status_key' => 'expired',
                'ride_stop_id' => 87,
            ],
            [
                'leg_key' => 'heen',
                'leg_label' => 'Heen',
                'status_key' => 'completed',
                'ride_stop_id' => 53,
            ],
            [
                'leg_key' => 'heen',
                'leg_label' => 'Heen',
                'status_key' => 'planned',
                'ride_stop_id' => 70,
            ],
        ]);

        $this->assertCount(1, $legs);
        $this->assertSame('heen', $legs[0]['leg_key']);
        $this->assertSame('completed', $legs[0]['status_key']);
        $this->assertSame(53, $legs[0]['ride_stop_id']);
    }

    public function test_heen_and_retour_stay_separate(): void
    {
        $legs = ContractPortalDayLegs::uniqueBySlot([
            [
                'leg_key' => 'heen',
                'leg_label' => 'Heen',
                'status_key' => 'completed',
            ],
            [
                'leg_key' => 'retour',
                'leg_label' => 'Retour',
                'status_key' => 'planned',
            ],
        ]);

        $this->assertCount(2, $legs);
        $this->assertSame(['heen', 'retour'], array_column($legs, 'leg_key'));
    }
}
