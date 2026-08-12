<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Support\ContractPortalLegLabel;
use Carbon\Carbon;
use Tests\TestCase;

class ContractPortalLegLabelTest extends TestCase
{
    public function test_morning_planned_at_is_heen(): void
    {
        [$key, $label] = ContractPortalLegLabel::forPlannedAt(
            Carbon::parse('2026-08-12 08:30:00', 'Europe/Amsterdam'),
            'Europe/Amsterdam'
        );

        $this->assertSame('heen', $key);
        $this->assertSame('Heen', $label);
    }

    public function test_afternoon_planned_at_is_retour(): void
    {
        [$key, $label] = ContractPortalLegLabel::forPlannedAt(
            Carbon::parse('2026-08-12 14:15:00', 'Europe/Amsterdam'),
            'Europe/Amsterdam'
        );

        $this->assertSame('retour', $key);
        $this->assertSame('Retour', $label);
    }

    public function test_noon_boundary_is_retour(): void
    {
        [$key] = ContractPortalLegLabel::forPlannedAt(
            Carbon::parse('2026-08-12 12:00:00', 'Europe/Amsterdam'),
            'Europe/Amsterdam'
        );

        $this->assertSame('retour', $key);
    }

    public function test_null_planned_at_defaults_to_heen(): void
    {
        [$key, $label] = ContractPortalLegLabel::forPlannedAt(null);

        $this->assertSame('heen', $key);
        $this->assertSame('Heen', $label);
    }
}
