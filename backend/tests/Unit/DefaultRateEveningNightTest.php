<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\DefaultRate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefaultRateEveningNightTest extends TestCase
{
    #[Test]
    public function evening_night_hour_wraps_over_midnight(): void
    {
        $this->assertTrue(DefaultRate::isEveningNightHour(22, 22, 6));
        $this->assertTrue(DefaultRate::isEveningNightHour(23, 22, 6));
        $this->assertTrue(DefaultRate::isEveningNightHour(0, 22, 6));
        $this->assertTrue(DefaultRate::isEveningNightHour(5, 22, 6));
        $this->assertFalse(DefaultRate::isEveningNightHour(6, 22, 6));
        $this->assertFalse(DefaultRate::isEveningNightHour(12, 22, 6));
        $this->assertFalse(DefaultRate::isEveningNightHour(21, 22, 6));
    }

    #[Test]
    public function evening_night_hour_supports_same_day_window(): void
    {
        $this->assertTrue(DefaultRate::isEveningNightHour(8, 8, 18));
        $this->assertTrue(DefaultRate::isEveningNightHour(17, 8, 18));
        $this->assertFalse(DefaultRate::isEveningNightHour(18, 8, 18));
        $this->assertFalse(DefaultRate::isEveningNightHour(7, 8, 18));
    }

    #[Test]
    public function equal_from_and_until_never_applies(): void
    {
        $this->assertFalse(DefaultRate::isEveningNightHour(22, 22, 22));
    }

    #[Test]
    public function evening_night_settings_fall_back_to_defaults(): void
    {
        $settings = DefaultRate::eveningNightSettings(null);

        $this->assertSame(1.2, $settings['multiplier']);
        $this->assertSame(22, $settings['from_hour']);
        $this->assertSame(6, $settings['until_hour']);
    }

    #[Test]
    public function evening_night_settings_read_from_rate(): void
    {
        $rate = new DefaultRate([
            'evening_night_multiplier' => 1.5,
            'evening_night_from_hour' => 21,
            'evening_night_until_hour' => 5,
        ]);

        $settings = DefaultRate::eveningNightSettings($rate);

        $this->assertSame(1.5, $settings['multiplier']);
        $this->assertSame(21, $settings['from_hour']);
        $this->assertSame(5, $settings['until_hour']);
    }
}
