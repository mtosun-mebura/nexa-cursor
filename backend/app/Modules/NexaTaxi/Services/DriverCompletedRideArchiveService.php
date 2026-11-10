<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Support\Facades\Schema;

/**
 * Chauffeur-archief voor afgeronde ritten (persistent in booking_payload).
 * Alleen lokale AsyncStorage bleek na refresh weer onder Ritten te vallen.
 */
class DriverCompletedRideArchiveService
{
    public const PAYLOAD_KEY = 'driver_archive';

    public function supportsPayload(string $conn): bool
    {
        try {
            return Schema::connection($conn)->hasColumn('ride_requests', 'booking_payload');
        } catch (\Throwable) {
            return false;
        }
    }

    public function isArchived(RideRequest $ride, ?int $driverId = null): bool
    {
        $archive = $this->archiveMeta($ride);
        if ($archive === null || empty($archive['archived_at'])) {
            return false;
        }
        if ($driverId !== null && isset($archive['driver_id']) && (int) $archive['driver_id'] !== $driverId) {
            return false;
        }

        return true;
    }

    public function isDeleted(RideRequest $ride, ?int $driverId = null): bool
    {
        if (! $this->isArchived($ride, $driverId)) {
            return false;
        }

        $archive = $this->archiveMeta($ride);

        return $archive !== null && ! empty($archive['deleted_at']);
    }

    public function archive(RideRequest $ride, int $driverId): RideRequest
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $existing = is_array($payload[self::PAYLOAD_KEY] ?? null) ? $payload[self::PAYLOAD_KEY] : [];
        $payload[self::PAYLOAD_KEY] = array_filter([
            'archived_at' => $existing['archived_at'] ?? now()->toIso8601String(),
            'driver_id' => $driverId,
            'deleted_at' => null,
        ], fn ($value) => $value !== null && $value !== '');
        $ride->booking_payload = $payload;
        $ride->save();

        return $ride->fresh() ?? $ride;
    }

    public function unarchive(RideRequest $ride, int $driverId): RideRequest
    {
        if (! $this->isArchived($ride, $driverId)) {
            return $ride;
        }

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        unset($payload[self::PAYLOAD_KEY]);
        $ride->booking_payload = $payload;
        $ride->save();

        return $ride->fresh() ?? $ride;
    }

    public function markDeleted(RideRequest $ride, int $driverId): RideRequest
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $existing = is_array($payload[self::PAYLOAD_KEY] ?? null) ? $payload[self::PAYLOAD_KEY] : [];
        $payload[self::PAYLOAD_KEY] = [
            'archived_at' => $existing['archived_at'] ?? now()->toIso8601String(),
            'driver_id' => $driverId,
            'deleted_at' => now()->toIso8601String(),
        ];
        $ride->booking_payload = $payload;
        $ride->save();

        return $ride->fresh() ?? $ride;
    }

    /**
     * @return array{archived_at?: string, driver_id?: int, deleted_at?: string}|null
     */
    protected function archiveMeta(RideRequest $ride): ?array
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $archive = $payload[self::PAYLOAD_KEY] ?? null;

        return is_array($archive) ? $archive : null;
    }
}
