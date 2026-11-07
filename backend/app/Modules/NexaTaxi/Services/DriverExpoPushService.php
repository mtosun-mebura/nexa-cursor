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

    /**
     * Zichtbare tekst bij “Rit geannuleerd”: welke rit, geen vage “niet beschikbaar”.
     *
     * @param  array{pickup_address?: string|null, pickup_at?: string|null, quoted_price?: float|int|string|null}  $meta
     */
    public function closedOfferBody(array $meta = []): string
    {
        $pickup = trim((string) ($meta['pickup_address'] ?? ''));
        if ($pickup !== '') {
            $comma = strpos($pickup, ',');
            if ($comma !== false && $comma > 0) {
                $pickup = trim(substr($pickup, 0, $comma));
            }
        }
        $when = '';
        $rawWhen = trim((string) ($meta['pickup_at'] ?? ''));
        if ($rawWhen !== '') {
            try {
                $when = \Carbon\Carbon::parse($rawWhen)->format('d-m H:i');
            } catch (\Throwable) {
                $when = '';
            }
        }
        $amount = $meta['quoted_price'] ?? null;
        $price = is_numeric($amount) ? '€ '.number_format((float) $amount, 2, ',', '') : null;
        $parts = array_values(array_filter(
            [$pickup !== '' ? $pickup : null, $when !== '' ? $when : null, $price],
            static fn ($part) => $part !== null && $part !== ''
        ));

        return $parts !== []
            ? implode(' · ', $parts)
            : 'De rit is geannuleerd.';
    }

    public function sendNewOffer(
        int $driverId,
        int $rideRequestId,
        ?int $offerId,
        string $title,
        string $body,
        ?string $pickupAddress = null,
        ?string $placeName = null,
        ?string $dropoffAddress = null,
        ?string $dropoffPlaceName = null,
        ?string $pickupAt = null,
        ?float $quotedPrice = null,
        ?string $expiresAt = null
    ): void {
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
            // Rit-snapshot in data: app toont de kaart bij tikken / na wake.
            $data = array_filter([
                'type' => 'driver_offer',
                'screen' => 'requests',
                'offer_id' => $offerId,
                'ride_request_id' => $rideRequestId,
                'pickup_address' => trim((string) $pickupAddress),
                'pickup_place_name' => trim((string) $placeName),
                'dropoff_address' => trim((string) $dropoffAddress),
                'dropoff_place_name' => trim((string) $dropoffPlaceName),
                'pickup_at' => $pickupAt,
                'quoted_price' => $quotedPrice,
                'expires_at' => $expiresAt,
            ], static fn ($value) => $value !== null && $value !== '');

            $platform = strtolower(trim((string) ($row->platform ?? '')));
            if ($platform === 'android') {
                // Android: FCM-banner + Bel via notification channel.
                $messages[] = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body !== '' ? $body : 'Er staat een nieuwe rit voor je klaar.',
                    'sound' => 'chime.wav',
                    '_contentAvailable' => true,
                    'priority' => 'high',
                    'channelId' => 'driver-offers-alert-v10-chime',
                    'interruptionLevel' => 'time-sensitive',
                    'data' => $data,
                ];
            } else {
                // iOS: zichtbare APNs-banner + geluid. Custom chime.wav uit de app-bundle
                // (valt terug op systeemtoon als het bestand ontbreekt). `_contentAvailable`
                // wekt de app zodat Bel via expo-audio nog speelt bij stille schakelaar.
                // Geen data-only + lokale chime: die blijft op slot vaak stil.
                $messages[] = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body !== '' ? $body : 'Er staat een nieuwe rit voor je klaar.',
                    'sound' => 'chime.wav',
                    '_contentAvailable' => true,
                    'priority' => 'high',
                    'channelId' => 'driver-offers-alert-v10-chime',
                    'interruptionLevel' => 'time-sensitive',
                    'data' => $data,
                ];
            }
        }

        if ($messages === []) {
            return;
        }

        $this->postMessages($driverId, $rideRequestId, $messages, $sentRows);
    }

    /**
     * Aanbod is niet meer pending (geannuleerd). Push noemt de rit (adres/tijd),
     * zodat de chauffeur weet welke kaart grijs/geannuleerd wordt.
     *
     * @param  iterable<int, array{id?: int, driver_id?: int, ride_request_id?: int, pickup_address?: string|null, pickup_at?: string|null, quoted_price?: float|int|string|null}|object>  $offers
     */
    public function sendOffersClosed(iterable $offers): void
    {
        $rows = [];
        foreach ($offers as $offer) {
            $id = (int) (is_array($offer) ? ($offer['id'] ?? 0) : ($offer->id ?? 0));
            $driverId = (int) (is_array($offer) ? ($offer['driver_id'] ?? 0) : ($offer->driver_id ?? 0));
            $rideId = (int) (is_array($offer) ? ($offer['ride_request_id'] ?? 0) : ($offer->ride_request_id ?? 0));
            if ($id <= 0 || $driverId <= 0) {
                continue;
            }
            $rows[] = [
                'id' => $id,
                'driver_id' => $driverId,
                'ride_request_id' => $rideId,
                'pickup_address' => trim((string) (is_array($offer)
                    ? ($offer['pickup_address'] ?? '')
                    : ($offer->pickup_address ?? ''))),
                'pickup_at' => trim((string) (is_array($offer)
                    ? ($offer['pickup_at'] ?? '')
                    : ($offer->pickup_at ?? ''))),
                'quoted_price' => is_array($offer)
                    ? ($offer['quoted_price'] ?? null)
                    : ($offer->quoted_price ?? null),
            ];
        }
        if ($rows === []) {
            return;
        }

        $tokens = DriverDeviceToken::query()
            ->whereIn('user_id', array_values(array_unique(array_column($rows, 'driver_id'))))
            ->get()
            ->groupBy('user_id');

        $messages = [];
        $sentRows = [];
        $dedupeKeys = [];
        foreach ($rows as $row) {
            $body = $this->closedOfferBody($row);
            $data = array_filter([
                'type' => 'driver_offer_closed',
                'screen' => 'requests',
                'offer_id' => $row['id'],
                'ride_request_id' => $row['ride_request_id'] > 0 ? $row['ride_request_id'] : null,
                'pickup_address' => $row['pickup_address'] !== '' ? $row['pickup_address'] : null,
                'pickup_at' => $row['pickup_at'] !== '' ? $row['pickup_at'] : null,
                'quoted_price' => is_numeric($row['quoted_price']) ? (float) $row['quoted_price'] : null,
            ], static fn ($value) => $value !== null && $value !== '');
            foreach ($tokens->get($row['driver_id'], []) as $device) {
                $token = trim((string) $device->token);
                if (! preg_match('/^Expo(nent)?PushToken\[.+\]$/', $token)) {
                    continue;
                }
                $dedupe = 'taxi_expo_offer_closed:'.$row['id'].':'.sha1($token);
                if (! Cache::add($dedupe, 1, now()->addSeconds(30))) {
                    continue;
                }
                $dedupeKeys[] = $dedupe;
                $sentRows[] = $device;
                $platform = strtolower(trim((string) ($device->platform ?? '')));
                if ($platform === 'android') {
                    // Android: Bel via notification channel.
                    $messages[] = [
                        'to' => $token,
                        'title' => 'Rit geannuleerd',
                        'body' => $body,
                        'sound' => 'chime.wav',
                        '_contentAvailable' => true,
                        'priority' => 'high',
                        'channelId' => 'driver-offers-alert-v10-chime',
                        'interruptionLevel' => 'time-sensitive',
                        'data' => $data,
                    ];
                } else {
                    // iOS: zichtbare banner + Bel (APNs); content-available voor audio-backup.
                    $messages[] = [
                        'to' => $token,
                        'title' => 'Rit geannuleerd',
                        'body' => $body,
                        'sound' => 'chime.wav',
                        '_contentAvailable' => true,
                        'priority' => 'high',
                        'channelId' => 'driver-offers-alert-v10-chime',
                        'interruptionLevel' => 'time-sensitive',
                        'data' => $data,
                    ];
                }
            }
        }

        if ($messages === []) {
            return;
        }

        // rideRequestId null: een mislukte sluit-push mag de dedupe van de nieuwe-rit-push niet wissen.
        $result = $this->postMessages(0, null, $messages, $sentRows);
        if ($result['error'] !== null) {
            foreach ($dedupeKeys as $key) {
                Cache::forget($key);
            }
        }
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
                'sound' => 'chime.wav',
                '_contentAvailable' => true,
                'priority' => 'high',
                'channelId' => 'driver-offers-alert-v10-chime',
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
