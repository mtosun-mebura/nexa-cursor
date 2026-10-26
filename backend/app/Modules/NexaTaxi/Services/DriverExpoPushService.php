<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\DriverDeviceToken;
use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Remote push via de Expo Push API. Eén aanbod = één bericht per apparaat.
 * Fouten worden gelogd en breken het aanmaken van het aanbod niet.
 */
class DriverExpoPushService
{
    public const SEND_URL = 'https://exp.host/--/api/v2/push/send';

    public function offerBody(RideRequest $ride): string
    {
        $when = $ride->pickup_at?->format('d-m H:i');
        $amount = $ride->quoted_price ?? $ride->final_price ?? null;
        $price = is_numeric($amount) ? '€ '.number_format((float) $amount, 2, ',', '') : null;
        $parts = array_values(array_filter(
            [
                trim((string) ($ride->pickup_address ?? '')),
                $when,
                $price,
            ],
            static fn ($part) => $part !== null && $part !== ''
        ));

        return $parts !== [] ? implode(' · ', $parts) : 'Er staat een nieuwe rit voor je klaar.';
    }

    public function sendNewOffer(int $driverId, int $rideRequestId, ?int $offerId, string $title, string $body): void
    {
        if ($driverId <= 0 || $rideRequestId <= 0 || $title === '') {
            return;
        }

        $rows = DriverDeviceToken::query()->where('user_id', $driverId)->get();
        $messages = [];
        $sentRows = [];
        foreach ($rows as $row) {
            $token = trim((string) $row->token);
            if (! preg_match('/^Expo(nent)?PushToken\[.+\]$/', $token)) {
                continue;
            }
            $dedupe = 'taxi_expo_offer:'.$rideRequestId.':'.sha1($token);
            if (! Cache::add($dedupe, 1, now()->addDay())) {
                continue;
            }
            $sentRows[] = $row;
            $messages[] = [
                'to' => $token,
                'title' => $title,
                'body' => $body !== '' ? $body : 'Er staat een nieuwe rit voor je klaar.',
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'driver-offers-system',
                'interruptionLevel' => 'time-sensitive',
                'data' => [
                    'type' => 'driver_offer',
                    'offer_id' => $offerId,
                    'ride_request_id' => $rideRequestId,
                ],
            ];
        }

        if ($messages === []) {
            return;
        }

        $this->postMessages($driverId, $rideRequestId, $messages, $sentRows);
    }

    /**
     * Testpush naar elk geldig apparaat van deze chauffeur. Geen tokens in het resultaat.
     *
     * @return array{devices: int, tickets: list<array{status: string, error: string|null}>, error: string|null}
     */
    public function sendTest(int $driverId): array
    {
        if ($driverId <= 0) {
            return ['devices' => 0, 'tickets' => [], 'error' => null];
        }

        $rows = DriverDeviceToken::query()->where('user_id', $driverId)->get();
        $messages = [];
        $sentRows = [];
        foreach ($rows as $row) {
            $token = trim((string) $row->token);
            if (! preg_match('/^Expo(nent)?PushToken\[.+\]$/', $token)) {
                continue;
            }
            $sentRows[] = $row;
            $messages[] = [
                'to' => $token,
                'title' => 'Testmelding Nexa Taxi',
                'body' => 'Dit is een test van de chauffeur-push.',
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'driver-offers-system',
                'interruptionLevel' => 'time-sensitive',
                'data' => ['type' => 'driver_offer_test'],
            ];
        }

        if ($messages === []) {
            return ['devices' => 0, 'tickets' => [], 'error' => null];
        }

        $result = $this->postMessages($driverId, null, $messages, $sentRows);

        return [
            'devices' => count($messages),
            'tickets' => $result['tickets'],
            'error' => $result['error'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<DriverDeviceToken>  $sentRows
     * @return array{tickets: list<array{status: string, error: string|null}>, error: string|null}
     */
    private function postMessages(int $driverId, ?int $rideRequestId, array $messages, array $sentRows): array
    {
        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->asJson()
                ->post(self::SEND_URL, $messages);
        } catch (\Throwable $e) {
            if ($rideRequestId) {
                $this->forgetDedupe($rideRequestId, $sentRows);
            }
            Log::warning('Expo push mislukt.', [
                'ride_request_id' => $rideRequestId,
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);

            return ['tickets' => [], 'error' => 'request_failed'];
        }

        if (! $response->successful()) {
            if ($rideRequestId) {
                $this->forgetDedupe($rideRequestId, $sentRows);
            }
            Log::warning('Expo push afgewezen.', [
                'ride_request_id' => $rideRequestId,
                'driver_id' => $driverId,
                'status' => $response->status(),
            ]);

            return ['tickets' => [], 'error' => 'http_'.$response->status()];
        }

        $payload = $response->json('data');
        $rawTickets = is_array($payload) && array_is_list($payload) ? $payload : (is_array($payload) ? [$payload] : []);
        $public = [];
        foreach ($rawTickets as $index => $ticket) {
            if (! is_array($ticket)) {
                continue;
            }
            $error = $ticket['details']['error'] ?? null;
            $errorCode = is_string($error) ? $error : null;
            if ($errorCode === 'DeviceNotRegistered' && isset($sentRows[$index])) {
                $sentRows[$index]->delete();
                Log::info('Expo push-token verwijderd (apparaat uitgeschreven).', [
                    'driver_id' => $driverId,
                ]);
            } elseif (($ticket['status'] ?? null) === 'error') {
                Log::warning('Expo push-ticket fout.', [
                    'ride_request_id' => $rideRequestId,
                    'driver_id' => $driverId,
                    'error' => $errorCode ?? 'unknown',
                ]);
            }
            $public[] = [
                'status' => (string) ($ticket['status'] ?? 'unknown'),
                'error' => $errorCode,
            ];
        }

        return ['tickets' => $public, 'error' => null];
    }

    /**
     * @param  list<DriverDeviceToken>  $rows
     */
    private function forgetDedupe(int $rideRequestId, array $rows): void
    {
        foreach ($rows as $row) {
            Cache::forget('taxi_expo_offer:'.$rideRequestId.':'.sha1((string) $row->token));
        }
    }
}
