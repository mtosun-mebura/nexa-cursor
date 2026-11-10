<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\TaxiDriverNotification;
use App\Modules\NexaTaxi\Support\TaxiDriverComplaintSchema;

class TaxiDriverNotificationService
{
    public function __construct(
        protected DriverExpoPushService $push
    ) {}

    /**
     * @param  array{
     *   type: string,
     *   title: string,
     *   body: string,
     *   company_id?: int|null,
     *   related_complaint_id?: int|null,
     *   related_ride_id?: int|null,
     *   related_rating_id?: int|null,
     *   created_by?: int|null,
     *   push?: bool
     * }  $payload
     */
    public function notify(string $conn, int $driverId, array $payload): TaxiDriverNotification
    {
        TaxiDriverComplaintSchema::ensure($conn);

        $row = new TaxiDriverNotification([
            'driver_id' => $driverId,
            'company_id' => $payload['company_id'] ?? null,
            'type' => $payload['type'],
            'title' => $payload['title'],
            'body' => $payload['body'],
            'related_complaint_id' => $payload['related_complaint_id'] ?? null,
            'related_ride_id' => $payload['related_ride_id'] ?? null,
            'related_rating_id' => $payload['related_rating_id'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ]);
        $row->setConnection($conn);
        $row->save();

        if (($payload['push'] ?? true) === true) {
            $this->push->sendSimple(
                $driverId,
                (string) $payload['title'],
                (string) $payload['body'],
                [
                    'type' => 'driver_inbox',
                    'notification_id' => (int) $row->id,
                    'notification_type' => (string) $payload['type'],
                ]
            );
        }

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOpenForDriver(string $conn, int $driverId, int $limit = 50): array
    {
        TaxiDriverComplaintSchema::ensure($conn);
        if ($driverId <= 0) {
            return [];
        }

        return TaxiDriverNotification::on($conn)
            ->where('driver_id', $driverId)
            ->whereNull('closed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (TaxiDriverNotification $row) => $this->toPayload($row))
            ->values()
            ->all();
    }

    public function unreadCount(string $conn, int $driverId): int
    {
        TaxiDriverComplaintSchema::ensure($conn);
        if ($driverId <= 0) {
            return 0;
        }

        return (int) TaxiDriverNotification::on($conn)
            ->where('driver_id', $driverId)
            ->whereNull('closed_at')
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(string $conn, int $driverId, int $notificationId): ?TaxiDriverNotification
    {
        $row = $this->findOwned($conn, $driverId, $notificationId);
        if (! $row) {
            return null;
        }
        if ($row->read_at === null) {
            $row->read_at = now();
            $row->save();
        }

        return $row;
    }

    public function close(string $conn, int $driverId, int $notificationId): ?TaxiDriverNotification
    {
        $row = $this->findOwned($conn, $driverId, $notificationId);
        if (! $row) {
            return null;
        }
        if ($row->read_at === null) {
            $row->read_at = now();
        }
        $row->closed_at = now();
        $row->save();

        return $row;
    }

    public function reopen(string $conn, int $driverId, int $notificationId): ?TaxiDriverNotification
    {
        $row = $this->findOwned($conn, $driverId, $notificationId, includeClosed: true);
        if (! $row) {
            return null;
        }
        $row->closed_at = null;
        $row->save();

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(TaxiDriverNotification $row): array
    {
        return [
            'id' => (int) $row->id,
            'type' => (string) $row->type,
            'title' => (string) $row->title,
            'body' => (string) $row->body,
            'related_complaint_id' => $row->related_complaint_id ? (int) $row->related_complaint_id : null,
            'related_ride_id' => $row->related_ride_id ? (int) $row->related_ride_id : null,
            'read_at' => $row->read_at?->toIso8601String(),
            'closed_at' => $row->closed_at?->toIso8601String(),
            'created_at' => $row->created_at?->toIso8601String(),
            'is_read' => $row->read_at !== null,
            'is_closed' => $row->closed_at !== null,
        ];
    }

    protected function findOwned(
        string $conn,
        int $driverId,
        int $notificationId,
        bool $includeClosed = false
    ): ?TaxiDriverNotification {
        TaxiDriverComplaintSchema::ensure($conn);
        $query = TaxiDriverNotification::on($conn)
            ->where('driver_id', $driverId)
            ->whereKey($notificationId);
        if (! $includeClosed) {
            $query->whereNull('closed_at');
        }

        return $query->first();
    }
}
