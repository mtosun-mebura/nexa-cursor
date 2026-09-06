<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Support\RideAlertTone;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RideAlertToneTest extends TestCase
{
    #[Test]
    public function it_normalizes_known_and_unknown_tones(): void
    {
        $this->assertSame('chime', RideAlertTone::normalize('Chime'));
        $this->assertSame('classic', RideAlertTone::normalize('unknown'));
        $this->assertSame('classic', RideAlertTone::normalize(null));
        $this->assertSame('siren', RideAlertTone::normalize('siren'));
    }

    #[Test]
    public function it_exposes_five_tone_options(): void
    {
        $this->assertCount(5, RideAlertTone::KEYS);
        $this->assertCount(5, RideAlertTone::options());
        $this->assertSame(RideAlertTone::KEYS, array_keys(RideAlertTone::options()));
    }

    #[Test]
    public function it_persists_tone_on_the_user(): void
    {
        $user = User::factory()->create(['ride_alert_tone' => 'classic']);

        $saved = RideAlertTone::saveFor($user, 'alert');

        $this->assertSame('alert', $saved);
        $this->assertSame('alert', $user->fresh()->ride_alert_tone);
        $this->assertSame('alert', RideAlertTone::fromUser($user->fresh()));
    }
}
