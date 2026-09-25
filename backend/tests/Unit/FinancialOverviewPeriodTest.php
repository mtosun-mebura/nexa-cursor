<?php

namespace Tests\Unit;

use App\Services\FinancialOverviews\FinancialOverviewPeriod;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinancialOverviewPeriodTest extends TestCase
{
    #[Test]
    public function quarter_sets_start_and_end_dates(): void
    {
        $period = FinancialOverviewPeriod::fromInput([
            'period_type' => FinancialOverviewPeriod::TYPE_QUARTER,
            'year' => 2026,
            'quarter' => 2,
        ]);

        $this->assertSame('2026-04-01', $period->startDate());
        $this->assertSame('2026-06-30', $period->endDate());
        $this->assertSame('2e kwartaal 2026', $period->label);
    }

    #[Test]
    public function half_year_and_year_cover_expected_ranges(): void
    {
        $h2 = FinancialOverviewPeriod::fromInput([
            'period_type' => FinancialOverviewPeriod::TYPE_HALF_YEAR,
            'year' => 2026,
            'half' => 2,
        ]);
        $this->assertSame('2026-07-01', $h2->startDate());
        $this->assertSame('2026-12-31', $h2->endDate());

        $year = FinancialOverviewPeriod::fromInput([
            'period_type' => FinancialOverviewPeriod::TYPE_YEAR,
            'year' => 2025,
        ]);
        $this->assertSame('2025-01-01', $year->startDate());
        $this->assertSame('2025-12-31', $year->endDate());
    }

    #[Test]
    public function month_uses_calendar_bounds(): void
    {
        $period = FinancialOverviewPeriod::fromInput([
            'period_type' => FinancialOverviewPeriod::TYPE_MONTH,
            'year' => 2026,
            'month' => 2,
        ]);

        $this->assertSame('2026-02-01', $period->startDate());
        $this->assertSame('2026-02-28', $period->endDate());
        $this->assertSame('februari 2026', $period->label);
    }

    #[Test]
    public function custom_period_parses_dutch_dates(): void
    {
        $period = FinancialOverviewPeriod::fromInput([
            'period_type' => FinancialOverviewPeriod::TYPE_CUSTOM,
            'start_date' => '01-03-2026',
            'end_date' => '15-03-2026',
        ]);

        $this->assertSame('2026-03-01', $period->startDate());
        $this->assertSame('2026-03-15', $period->endDate());
    }

    #[Test]
    public function custom_period_rejects_inverted_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FinancialOverviewPeriod::fromInput([
            'period_type' => FinancialOverviewPeriod::TYPE_CUSTOM,
            'start_date' => '15-03-2026',
            'end_date' => '01-03-2026',
        ]);
    }
}
