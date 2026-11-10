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
                && ($message['sound'] ?? null) === 'chime.wav'
                && ($message['_contentAvailable'] ?? null) === true
                && ($message['priority'] ?? null) === 'high'
                && ($message['channelId'] ?? null) === 'driver-offers-alert-v10-chime';
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

    #[Test]
    public function ios_new_offer_push_uses_visible_bel_banner_and_content_available_wake(): void
    {
        $user = User::factory()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[ios-offer]',
            'platform' => 'ios',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-ios']],
            ]),
        ]);

        app(DriverExpoPushService::class)->sendNewOffer(
            $user->id,
            55,
            12,
            'Nieuwe rit',
            'Damrak 1',
            'Damrak 1'
        );

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return ($message['to'] ?? null) === 'ExponentPushToken[ios-offer]'
                && ($message['title'] ?? null) === 'Nieuwe rit'
                && ($message['sound'] ?? null) === 'chime.wav'
                && ($message['_contentAvailable'] ?? null) === true
                && ($message['priority'] ?? null) === 'high'
                && ($message['data']['type'] ?? null) === 'driver_offer'
                && (int) ($message['data']['offer_id'] ?? 0) === 12;
        });
    }

    #[Test]
    public function android_new_offer_push_keeps_visible_bel_banner(): void
    {
        $user = User::factory()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[android-offer]',
            'platform' => 'android',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-android']],
            ]),
        ]);

        app(DriverExpoPushService::class)->sendNewOffer(
            $user->id,
            56,
            13,
            'Nieuwe rit',
            'Damrak 1',
            'Damrak 1'
        );

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return ($message['to'] ?? null) === 'ExponentPushToken[android-offer]'
                && ($message['title'] ?? null) === 'Nieuwe rit'
                && ($message['sound'] ?? null) === 'chime.wav'
                && ($message['channelId'] ?? null) === 'driver-offers-alert-v10-chime'
                && ($message['data']['type'] ?? null) === 'driver_offer';
        });
    }

    #[Test]
    public function ios_closed_offer_push_uses_visible_bel_banner(): void
    {
        $user = User::factory()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[closed]',
            'platform' => 'ios',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-closed']],
            ]),
        ]);

        app(DriverExpoPushService::class)->sendOffersClosed([[
            'id' => 15,
            'driver_id' => $user->id,
            'ride_request_id' => 9,
            'pickup_address' => 'Stationsplein 1, Amsterdam',
            'pickup_at' => '2026-11-05T14:30:00+01:00',
            'quoted_price' => 24.5,
        ]]);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return ($message['data']['type'] ?? null) === 'driver_offer_closed'
                && (int) ($message['data']['offer_id'] ?? 0) === 15
                && (int) ($message['data']['ride_request_id'] ?? 0) === 9
                && ($message['data']['pickup_address'] ?? null) === 'Stationsplein 1, Amsterdam'
                && ($message['title'] ?? null) === 'Rit geannuleerd'
                && ($message['sound'] ?? null) === 'chime.wav'
                && ($message['_contentAvailable'] ?? null) === true;
        });
    }

    #[Test]
    public function android_closed_offer_push_uses_bel_channel_sound(): void
    {
        $user = User::factory()->create();
        DriverDeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[closed-android]',
            'platform' => 'android',
        ]);

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-closed-android']],
            ]),
        ]);

        app(DriverExpoPushService::class)->sendOffersClosed([[
            'id' => 16,
            'driver_id' => $user->id,
            'ride_request_id' => 10,
            'pickup_address' => 'Damrak 1',
            'pickup_at' => '2026-11-05T14:30:00+01:00',
            'quoted_price' => 18,
        ]]);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->data();
            $message = is_array($body) && array_is_list($body) ? ($body[0] ?? []) : $body;

            return ($message['data']['type'] ?? null) === 'driver_offer_closed'
                && ($message['title'] ?? null) === 'Rit geannuleerd'
                && ($message['sound'] ?? null) === 'chime.wav'
                && ($message['channelId'] ?? null) === 'driver-offers-alert-v10-chime';
        });
    }
}
