<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Support\TaxiDriverAccountStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxiDriverAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_when_is_active_false(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse(TaxiDriverAccountStatus::isActive($user));
    }

    public function test_active_when_is_active_true(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->assertTrue(TaxiDriverAccountStatus::isActive($user));
    }
}
