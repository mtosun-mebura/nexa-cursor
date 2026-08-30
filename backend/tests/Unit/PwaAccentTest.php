<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Support\PwaAccent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PwaAccentTest extends TestCase
{
    #[Test]
    public function it_normalizes_known_and_unknown_accents(): void
    {
        $this->assertSame('blue', PwaAccent::normalize('Blue'));
        $this->assertSame('orange', PwaAccent::normalize('unknown'));
        $this->assertSame('orange', PwaAccent::normalize(null));
        $this->assertSame('pink', PwaAccent::normalize('pink'));
    }

    #[Test]
    public function it_persists_accent_on_the_user(): void
    {
        $user = User::factory()->create(['pwa_accent' => 'orange']);

        $saved = PwaAccent::saveFor($user, 'green');

        $this->assertSame('green', $saved);
        $this->assertSame('green', $user->fresh()->pwa_accent);
        $this->assertSame('green', PwaAccent::fromUser($user->fresh()));
    }
}
