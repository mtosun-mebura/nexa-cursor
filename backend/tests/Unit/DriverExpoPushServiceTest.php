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

    #[Test]
    public function push_test_command_sends_without_printing_the_token(): void
    {
        $user = User::factory()->create(['email' => 'push-test@example.com']);
        $token = 'ExponentPushToken[secret-device]';
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => $token,
            'platform' => 'android',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-test']],
            ]),
        ]);

        $this->artisan('taxi:push-test', ['email' => 'push-test@example.com'])
            ->expectsOutputToContain('Apparaten: 1')
            ->expectsOutputToContain('Ticket: ok')
            ->doesntExpectOutputToContain($token)
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return ($message['title'] ?? null) === 'Testmelding Nexa Taxi'
                && ($message['sound'] ?? null) === 'default'
                && ($message['priority'] ?? null) === 'high'
                && ($message['channelId'] ?? null) === 'driver-offers-system';
        });
    }

    #[Test]
    public function push_test_command_does_not_call_expo_for_an_unknown_driver(): void
    {
        Http::fake();

        $this->artisan('taxi:push-test', ['email' => 'nobody@example.com'])
            ->expectsOutputToContain('Geen gebruiker met dat e-mailadres.')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
