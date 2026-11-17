<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Support\Str;

/**
 * Eenvoudige rit-chat in booking_payload (geen Twilio/Reverb vereist).
 * Berichten zijn tekst-only; privénummers horen hier niet in.
 */
class RideChatService
{
    public const PAYLOAD_KEY = 'ride_chat';

    public const MAX_BODY = 2000;

    public const MAX_MESSAGES = 200;

    /**
     * @return list<array{id: string, from: string, body: string, at: string}>
     */
    public function messages(RideRequest $ride): array
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $raw = $payload[self::PAYLOAD_KEY] ?? [];
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            $from = trim((string) ($row['from'] ?? ''));
            $body = trim((string) ($row['body'] ?? ''));
            $at = trim((string) ($row['at'] ?? ''));
            if ($id === '' || $body === '' || ! in_array($from, ['customer', 'driver', 'system'], true)) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'from' => $from,
                'body' => mb_substr($body, 0, self::MAX_BODY),
                'at' => $at !== '' ? $at : now()->toIso8601String(),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, from: string, body: string, at: string}>
     */
    public function append(RideRequest $ride, string $from, string $body, ?string $clientMessageId = null): array
    {
        if (! in_array($from, ['customer', 'driver', 'system'], true)) {
            return $this->messages($ride);
        }

        $body = trim(strip_tags($body));
        if ($body === '') {
            return $this->messages($ride);
        }
        $body = mb_substr($body, 0, self::MAX_BODY);

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $list = $this->messages($ride);

        $id = $clientMessageId && Str::isUuid($clientMessageId)
            ? $clientMessageId
            : (string) Str::uuid();

        foreach ($list as $existing) {
            if ($existing['id'] === $id) {
                return $list;
            }
        }

        $list[] = [
            'id' => $id,
            'from' => $from,
            'body' => $body,
            'at' => now()->toIso8601String(),
        ];

        if (count($list) > self::MAX_MESSAGES) {
            $list = array_slice($list, -self::MAX_MESSAGES);
        }

        $payload[self::PAYLOAD_KEY] = $list;
        $ride->booking_payload = $payload;
        $ride->save();

        return $list;
    }

    public function communicationAllowed(RideRequest $ride): bool
    {
        if ((int) ($ride->driver_id ?? 0) <= 0) {
            return false;
        }

        $status = strtolower((string) ($ride->status ?? ''));

        return in_array($status, [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
        ], true);
    }
}
