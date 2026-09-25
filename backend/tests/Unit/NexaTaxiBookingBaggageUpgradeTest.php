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

        $this->assertSame(2.0, $context['baggage_units']);
        $this->assertFalse($context['baggage_van_upgrade']);
        $this->assertSame('1-4', $context['person_range']);
    }

    public function test_excess_baggage_upgrades_to_bus_range(): void
    {
        $config = $this->service()->getDefaultSectionConfig();
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['large' => 5],
            'special_baggage' => [],
        ], 1);

        $this->assertSame(5.0, $context['baggage_units']);
        $this->assertTrue($context['baggage_van_upgrade']);
        $this->assertSame('5-8', $context['person_range']);
    }

    public function test_three_passengers_with_three_large_and_one_small_stay_on_car_range(): void
    {
        $config = $this->service()->getDefaultSectionConfig();
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['large' => 3, 'small' => 1],
            'special_baggage' => [],
        ], 3);

        $this->assertSame(4.0, $context['baggage_units']);
        $this->assertFalse($context['baggage_van_upgrade']);
        $this->assertSame('1-4', $context['person_range']);
    }

    public function test_hand_luggage_does_not_count_toward_trunk_limit(): void
    {
        $config = $this->service()->getDefaultSectionConfig();
        $context = $this->service()->resolveBaggagePersonRangeContext($config, [
            'baggage' => ['large' => 4, 'hand' => 6],
            'special_baggage' => [],
        ], 1);

        $this->assertSame(4.0, $context['baggage_units']);
        $this->assertFalse($context['baggage_van_upgrade']);
        $this->assertSame('1-4', $context['person_range']);
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

        $this->assertFalse($context['baggage_van_upgrade']);
        $this->assertSame('5-8', $context['person_range']);
    }

    public function test_normalize_offer_display_mode_accepts_person_range_aliases(): void
    {
        $this->assertSame('person_range', $this->service()->normalizeOfferDisplayMode('person_range'));
        $this->assertSame('person_range', $this->service()->normalizeOfferDisplayMode('Per aantal personen'));
        $this->assertSame('vehicle', $this->service()->normalizeOfferDisplayMode('Per auto'));
    }

    public function test_build_quotes_person_range_mode_returns_single_tariff_card(): void
    {
        $config = $this->service()->mergeSectionConfig([
            'logic' => [
                'offer_display_mode' => 'person_range',
            ],
            'offers' => [
                [
                    'id' => 'offer_1',
                    'title' => 'BMW 5 serie',
                    'features' => ['Altijd een prive-taxi'],
                ],
                [
                    'id' => 'offer_2',
                    'title' => 'Mercedes E klasse',
                    'features' => ['Altijd een prive-taxi'],
                ],
            ],
        ]);

        $quotes = $this->service()->buildQuotes($config, [
            'distance_meters' => 12000,
            'duration_seconds' => 900,
            'passengers' => 1,
            'return_trip' => false,
            'baggage' => [],
            'special_baggage' => [],
        ]);

        $this->assertSame('person_range', $quotes['offer_display_mode']);
        $this->assertCount(1, $quotes['offers']);
        $this->assertStringStartsWith('person_range_', (string) $quotes['offers'][0]['id']);
        $this->assertStringContainsString('vehicle-placeholder.png', (string) $quotes['offers'][0]['image_url']);
    }

    public function test_person_range_quote_uses_van_placeholder_for_bus_range(): void
    {
        $config = $this->service()->mergeSectionConfig([
            'logic' => [
                'offer_display_mode' => 'person_range',
            ],
        ]);

        $quotes = $this->service()->buildQuotes($config, [
            'distance_meters' => 12000,
            'duration_seconds' => 900,
            'passengers' => 6,
            'return_trip' => false,
            'baggage' => [],
            'special_baggage' => [],
        ]);

        $this->assertSame('5-8', $quotes['person_range']);
        $this->assertStringContainsString('vehicle-placeholder-van.png', (string) $quotes['offers'][0]['image_url']);
    }
}
