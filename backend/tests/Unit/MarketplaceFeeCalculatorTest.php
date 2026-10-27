<?php

namespace Tests\Unit;

use App\Models\NexaSuiteMarketplaceSetting;
use App\Services\Payout\MarketplaceFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceFeeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private MarketplaceFeeCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 10]);
        $this->calc = new MarketplaceFeeCalculator;
    }

    #[Test]
    public function large_ride_uses_the_percentage(): void
    {
        $r = $this->calc->calculate(60.00, 'ideal');

        $this->assertSame(6.0, $r['fee']);
        $this->assertSame(10, $r['percent']);
        $this->assertFalse($r['minimum_applied']);
    }

    #[Test]
    public function small_ride_hits_the_minimum(): void
    {
        // iDEAL 0,32 + payout 0,25 = 0,57; 10% van 4 = 0,40.
        $r = $this->calc->calculate(4.00, 'ideal');

        $this->assertSame(0.57, $r['minimum_fee']);
        $this->assertSame(0.57, $r['fee']);
        $this->assertTrue($r['minimum_applied']);
        $this->assertSame(14, $r['percent']);
    }

    #[Test]
    public function card_minimum_is_higher_than_ideal(): void
    {
        // Kaart: 0,25 + 1,8% van 5 (0,09) + 0,25 = 0,59; iDEAL: 0,57.
        $this->assertSame(0.59, $this->calc->calculate(5.00, 'creditcard')['fee']);
        $this->assertSame(0.57, $this->calc->calculate(5.00, 'ideal')['fee']);
        // Onbekende of ontbrekende methode valt terug op default (= kaarttarief).
        $this->assertSame(0.59, $this->calc->calculate(5.00, null)['fee']);
        $this->assertSame(0.59, $this->calc->calculate(5.00, 'giftcard')['fee']);
    }

    #[Test]
    public function fee_is_capped_at_the_ride_total_on_tiny_rides(): void
    {
        $r = $this->calc->calculate(0.40, 'ideal');

        $this->assertSame(0.40, $r['fee']);
        $this->assertTrue($r['capped']);
        $this->assertSame(0.0, round($r['gross'] - $r['fee'], 2));
        $this->assertSame(0.0, $this->calc->calculate(0.0, 'ideal')['fee']);
    }

    #[Test]
    public function minimum_is_rounded_up_to_whole_cents(): void
    {
        // 0,25 + 1,8% van 7,77 (0,13986) + 0,25 = 0,63986 -> 0,64.
        $r = $this->calc->calculate(7.77, 'creditcard');

        $this->assertSame(0.64, $r['minimum_fee']);
        $this->assertSame(0.78, $r['fee']); // 10% = 0,78 wint.
    }

    #[Test]
    public function stored_settings_override_defaults_and_can_disable_the_minimum(): void
    {
        NexaSuiteMarketplaceSetting::current()->update(['fee_minimum_settings' => [
            'payout_fixed' => 0.50,
            'margin_fixed' => 0.10,
            'margin_percent' => 1.0,
        ]]);
        // iDEAL 0,32 + 0,50 + 0,10 + 1% van 5 (0,05) = 0,97.
        $this->assertSame(0.97, $this->calc->calculate(5.00, 'ideal')['fee']);

        NexaSuiteMarketplaceSetting::current()->update(['fee_minimum_settings' => ['enabled' => false]]);
        $this->assertSame(0.50, $this->calc->calculate(5.00, 'ideal')['fee']);
        // Mollie-kosten blijven zichtbaar voor het admin-overzicht, ook zonder minimum.
        $this->assertSame(0.57, $this->calc->calculate(5.00, 'ideal')['mollie_cost']);
    }
}
