<?php

namespace Tests\Unit;

use App\Services\NexaTaxiBookingPricingService;
use Tests\TestCase;

class NexaTaxiBookingBaggageUpgradeTest extends TestCase
{
    private function service(): NexaTaxiBookingPricingService
    {
        return app(NexaTaxiBookingPricingService::class);
    }

    public function test_baggage_within_car_limit_keeps_passenger_range(): void
    {
        $config = $this->service()->getDefaultSectionConfig();
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['small' => 2, 'hand' => 1],
            'special_baggage' => [],
        ], 2);

        $this->assertSame(3.0, $context['baggage_units']);
        $this->assertFalse($context['baggage_van_upgrade']);
        $this->assertSame('1-4', $context['person_range']);
    }

    public function test_excess_baggage_upgrades_to_bus_range(): void
    {
        $config = $this->service()->getDefaultSectionConfig();
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['large' => 3],
            'special_baggage' => [],
        ], 2);

        $this->assertSame(6.0, $context['baggage_units']);
        $this->assertTrue($context['baggage_van_upgrade']);
        $this->assertSame('5-8', $context['person_range']);
    }

    public function test_upgrade_respects_disabled_setting(): void
    {
        $config = $this->service()->mergeSectionConfig([
            'logic' => ['baggage_van_upgrade_enabled' => false],
        ]);
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['large' => 3],
            'special_baggage' => [],
        ], 2);

        $this->assertFalse($context['baggage_van_upgrade']);
        $this->assertSame('1-4', $context['person_range']);
    }

    public function test_passenger_range_still_wins_when_higher_than_baggage_upgrade(): void
    {
        $config = $this->service()->getDefaultSectionConfig();
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['large' => 3],
            'special_baggage' => [],
        ], 6);

        $this->assertTrue($context['baggage_van_upgrade']);
        $this->assertSame('5-8', $context['person_range']);
    }
}
