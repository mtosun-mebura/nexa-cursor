<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TaxiDriverNotification;
use App\Modules\NexaTaxi\Support\RideAlertTone;
use Illuminate\Support\Str;

/**
 * Eenvoudige rit-chat in booking_payload (geen Twilio/Reverb vereist).
 * Berichten zijn tekst-only; privénummers horen hier niet in.
 */
class RideChatService
{
    public const PAYLOAD_KEY = 'ride_chat';

    public const META_KEY = 'ride_chat_meta';

    public const MAX_BODY = 2000;

    public const MAX_MESSAGES = 200;

    public function __construct(
        protected DriverExpoPushService $push,
        protected TaxiDriverNotificationService $driverNotifications,
    ) {}

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

        if ($from === 'customer') {
            $this->notifyDriverNewMessage($ride, $body);
        }

        return $list;
    }

    /** Chatgeschiedenis openen (ook na afronden). */
    public function viewAllowed(RideRequest $ride): bool
    {
        return (int) ($ride->driver_id ?? 0) > 0;
    }

    /** Nieuwe berichten sturen tijdens/na de rit (niet bij annulering). */
    public function communicationAllowed(RideRequest $ride): bool
    {
        if ((int) ($ride->driver_id ?? 0) <= 0) {
            return false;
        }

        $status = strtolower((string) ($ride->status ?? ''));

        return in_array($status, [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
            RideRequest::STATUS_COMPLETED,
        ], true);
    }

    public function unreadCountFor(RideRequest $ride, string $role): int
    {
        $peer = $role === 'driver' ? 'customer' : 'driver';
        $lastRead = $this->lastReadAt($ride, $role);
        $count = 0;
        foreach ($this->messages($ride) as $row) {
            if ($row['from'] !== $peer) {
                continue;
            }
            if ($lastRead === null || strcmp((string) $row['at'], $lastRead) > 0) {
                $count++;
            }
        }

        return $count;
    }

    public function markRead(RideRequest $ride, string $role): void
    {
        if (! in_array($role, ['driver', 'customer'], true)) {
            return;
        }
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $meta = is_array($payload[self::META_KEY] ?? null) ? $payload[self::META_KEY] : [];
        $meta[$role.'_last_read_at'] = now()->toIso8601String();
        $payload[self::META_KEY] = $meta;
        $ride->booking_payload = $payload;
        $ride->save();
    }

    protected function lastReadAt(RideRequest $ride, string $role): ?string
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $meta = is_array($payload[self::META_KEY] ?? null) ? $payload[self::META_KEY] : [];
        $raw = trim((string) ($meta[$role.'_last_read_at'] ?? ''));

        return $raw !== '' ? $raw : null;
    }

    protected function notifyDriverNewMessage(RideRequest $ride, string $body): void
    {
        $driverId = (int) ($ride->driver_id ?? 0);
        if ($driverId <= 0) {
            return;
        }

        $conn = $ride->getConnectionName() ?: null;
        $preview = mb_substr(trim($body), 0, 120);
        $customer = trim((string) ($ride->customer_name ?? ''));
        $title = $customer !== '' ? 'Chat: '.$customer : 'Nieuw chatbericht';
        $pickup = trim((string) ($ride->pickup_address ?? ''));
        if ($pickup !== '') {
            $comma = strpos($pickup, ',');
            if ($comma !== false && $comma > 0) {
                $pickup = trim(substr($pickup, 0, $comma));
            }
        }
        $pushBody = $preview !== ''
            ? $preview
            : ($pickup !== '' ? 'Bericht over rit · '.$pickup : 'Je hebt een nieuw chatbericht.');

        try {
            if ($conn) {
                $this->driverNotifications->notify($conn, $driverId, [
                    'type' => TaxiDriverNotification::TYPE_RIDE_CHAT,
                    'title' => $title,
                    'body' => $pushBody,
                    'company_id' => $ride->company_id ? (int) $ride->company_id : null,
                    'related_ride_id' => (int) $ride->id,
                    'push' => false,
                ]);
            }
        } catch (\Throwable) {
            /* inbox mag push niet blokkeren */
        }

        $tone = RideAlertTone::DEFAULT;
        try {
            $user = User::query()->find($driverId);
            $tone = RideAlertTone::fromUser($user);
        } catch (\Throwable) {
            $tone = RideAlertTone::DEFAULT;
        }

        $this->push->sendRideChat(
            $driverId,
            (int) $ride->id,
            $title,
            $pushBody,
            $tone
        );
    }
}
