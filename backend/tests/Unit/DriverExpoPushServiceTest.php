<?php

namespace Tests\Unit;

use App\Models\DriverDeviceToken;
use App\Models\User;
use App\Modules\NexaTaxi\Services\DriverExpoPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DriverExpoPushServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function device_not_registered_deletes_the_token(): void
    {
        $user = User::factory()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[gone]',
            'platform' => 'ios',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [[
                    'status' => 'error',
                    'message' => 'The recipient is not registered.',
                    'details' => ['error' => 'DeviceNotRegistered'],
                ]],
            ]),
        ]);

        app(DriverExpoPushService::class)->sendNewOffer($user->id, 41, 7, 'Nieuwe rit', 'Damrak 1');

        $this->assertDatabaseMissing('driver_device_tokens', [
            'token' => 'ExponentPushToken[gone]',
        ]);
    }

    #[Test]
    public function a_failed_push_does_not_throw_and_can_be_retried(): void
    {
        $user = User::factory()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[retry]',
            'platform' => 'android',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::sequence()
                ->push(['error' => 'down'], 500)
                ->push(['data' => [['status' => 'ok', 'id' => 'ticket-retry']]], 200),
        ]);

        $service = app(DriverExpoPushService::class);
        $service->sendNewOffer($user->id, 42, 8, 'Nieuwe rit', 'Dam');
        $service->sendNewOffer($user->id, 42, 8, 'Nieuwe rit', 'Dam');

        Http::assertSentCount(2);
        $this->assertDatabaseHas('driver_device_tokens', [
            'token' => 'ExponentPushToken[retry]',
        ]);
    }
}
