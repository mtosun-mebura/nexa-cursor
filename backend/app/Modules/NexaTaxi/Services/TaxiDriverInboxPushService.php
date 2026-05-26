<?php

namespace App\Modules\NexaTaxi\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Lightweight push-signaal voor chauffeur-inbox (SSE clients luisteren op cache-key).
 */
class TaxiDriverInboxPushService
{
    private const TTL_SECONDS = 120;

    public function notifyDriver(int $driverId, ?int $rideRequestId = null): void
    {
        if ($driverId <= 0) {
            return;
        }

        Cache::put($this->cacheKey($driverId), [
            'v' => microtime(true),
            'ride_request_id' => $rideRequestId,
            'at' => now()->toIso8601String(),
        ], self::TTL_SECONDS);
    }

    /**
     * @param  list<int>  $driverIds
     */
    public function notifyDrivers(array $driverIds, ?int $rideRequestId = null): void
    {
        foreach (array_unique(array_filter(array_map('intval', $driverIds))) as $driverId) {
            if ($driverId > 0) {
                $this->notifyDriver($driverId, $rideRequestId);
            }
        }
    }

    public function pullSignal(int $driverId): ?array
    {
        if ($driverId <= 0) {
            return null;
        }

        $payload = Cache::get($this->cacheKey($driverId));

        return is_array($payload) ? $payload : null;
    }

    private function cacheKey(int $driverId): string
    {
        return 'taxi_driver_inbox_push:'.$driverId;
    }
}
