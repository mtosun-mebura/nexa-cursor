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

        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->asJson()
                ->post(self::SEND_URL, $messages);
        } catch (\Throwable $e) {
            $this->forgetDedupe($rideRequestId, $sentRows);
            Log::warning('Expo push mislukt.', [
                'ride_request_id' => $rideRequestId,
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            $this->forgetDedupe($rideRequestId, $sentRows);
            Log::warning('Expo push afgewezen.', [
                'ride_request_id' => $rideRequestId,
                'driver_id' => $driverId,
                'status' => $response->status(),
            ]);

            return;
        }

        $payload = $response->json('data');
        $tickets = is_array($payload) && array_is_list($payload) ? $payload : (is_array($payload) ? [$payload] : []);
        foreach ($tickets as $index => $ticket) {
            if (! is_array($ticket)) {
                continue;
            }
            $error = $ticket['details']['error'] ?? null;
            if ($error === 'DeviceNotRegistered' && isset($sentRows[$index])) {
                $sentRows[$index]->delete();
                Log::info('Expo push-token verwijderd (apparaat uitgeschreven).', [
                    'driver_id' => $driverId,
                ]);
            } elseif (($ticket['status'] ?? null) === 'error') {
                Log::warning('Expo push-ticket fout.', [
                    'ride_request_id' => $rideRequestId,
                    'driver_id' => $driverId,
                    'error' => is_string($error) ? $error : 'unknown',
                ]);
            }
        }
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
