<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RideDispatchService
{
    public function __construct(
        protected TaxiDriverEligibilityService $drivers,
        protected TaxiDriverInboxPushService $push,
        protected TaxiDispatchSettingsService $dispatchSettings
    ) {}

    public function startDispatch(string $conn, RideRequest $ride, int $companyId, array $excludeDriverIds = []): void
    {
        if ($companyId <= 0) {
            return;
        }

        if (! TaxiDispatchSchema::tablesExist($conn)) {
            Log::warning('Taxi dispatch overgeslagen: migratie ontbreekt (driver_availability / ride_dispatch_offers).', [
                'connection' => $conn,
                'ride_request_id' => $ride->id,
            ]);

            return;
        }

        $ttl = $this->dispatchSettings->offerTtlSeconds($companyId);
        $batch = (int) config('taxi-dispatch.offer_batch_size', 8);
        $exclude = array_values(array_unique(array_map('intval', $excludeDriverIds)));
        $driverIds = array_values(array_filter(
            $this->drivers->onlineDriverIdsForCompany($conn, $companyId, $batch),
            fn (int $driverId) => ! in_array($driverId, $exclude, true)
        ));

        if ($driverIds === []) {
            return;
        }

        DB::connection($conn)->transaction(function () use ($conn, $ride, $companyId, $driverIds, $ttl) {
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first();
            if (! $locked || $locked->driver_id) {
                return;
            }

            $now = now();
            $expires = $now->copy()->addSeconds($ttl);
            $wave = 1;

            foreach ($driverIds as $driverId) {
                if ($this->driverDeclinedRide($conn, (int) $ride->id, $driverId)) {
                    continue;
                }

                RideDispatchOffer::on($conn)->updateOrCreate(
                    [
                        'ride_request_id' => $ride->id,
                        'driver_id' => $driverId,
                    ],
                    [
                        'company_id' => $companyId,
                        'status' => RideDispatchOffer::STATUS_PENDING,
                        'wave' => $wave,
                        'offered_at' => $now,
                        'expires_at' => $expires,
                        'responded_at' => null,
                    ]
                );
            }

            $locked->update([
                'status' => RideRequest::STATUS_OFFERED,
                'company_id' => $locked->company_id ?: $companyId,
            ]);
        });

        $this->push->notifyDrivers($driverIds, (int) $ride->id);
    }

    public function expireStaleOffers(string $conn, ?int $rideId = null): int
    {
        if (! TaxiDispatchSchema::tablesExist($conn)) {
            return 0;
        }

        $query = RideDispatchOffer::on($conn)
            ->where('status', RideDispatchOffer::STATUS_PENDING)
            ->where('expires_at', '<=', now());

        if ($rideId) {
            $query->where('ride_request_id', $rideId);
        }

        $rideIds = (clone $query)->pluck('ride_request_id')->unique()->map(fn ($id) => (int) $id)->all();

        $count = $query->update([
            'status' => RideDispatchOffer::STATUS_EXPIRED,
            'responded_at' => now(),
        ]);

        foreach ($rideIds as $waitingRideId) {
            $this->escalateWaitingRide($conn, $waitingRideId);
        }

        return $count;
    }

    /**
     * Rit heeft nog geen chauffeur: verleng aanbod + waarschuw alle online chauffeurs (gedebounced).
     */
    public function escalateWaitingRide(string $conn, int $rideRequestId): void
    {
        if ($rideRequestId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return;
        }

        $debounceKey = 'taxi_waiting_escalation:'.$conn.':'.$rideRequestId;
        if (Cache::has($debounceKey)) {
            return;
        }
        Cache::put($debounceKey, 1, 45);

        $ride = RideRequest::on($conn)->find($rideRequestId);
        if (! $ride || $ride->driver_id) {
            return;
        }

        if (! in_array($ride->status, [RideRequest::STATUS_PENDING_DISPATCH, RideRequest::STATUS_OFFERED], true)) {
            return;
        }

        $companyId = (int) $ride->company_id;
        if ($companyId <= 0) {
            return;
        }

        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);
        if ($ride->pickup_at && $ride->pickup_at->lt($pickupCutoff)) {
            return;
        }

        if ($this->allOffersDeclinedForRide($conn, $rideRequestId)) {
            return;
        }

        $ttl = $this->dispatchSettings->offerTtlSeconds($companyId);
        $batch = (int) config('taxi-dispatch.offer_batch_size', 8);
        $driverIds = $this->drivers->onlineDriverIdsForCompany($conn, $companyId, $batch);

        if ($driverIds === []) {
            return;
        }

        $now = now();
        $expires = $now->copy()->addSeconds($ttl);

        foreach ($driverIds as $driverId) {
            if ($this->driverDeclinedRide($conn, (int) $ride->id, $driverId)) {
                continue;
            }

            RideDispatchOffer::on($conn)->updateOrCreate(
                [
                    'ride_request_id' => $ride->id,
                    'driver_id' => $driverId,
                ],
                [
                    'company_id' => $companyId,
                    'status' => RideDispatchOffer::STATUS_PENDING,
                    'wave' => 1,
                    'offered_at' => $now,
                    'expires_at' => $expires,
                    'responded_at' => null,
                ]
            );

            $this->push->notifyDriver($driverId, (int) $ride->id);
        }

        if ($ride->status === RideRequest::STATUS_PENDING_DISPATCH) {
            $ride->update(['status' => RideRequest::STATUS_OFFERED]);
        }
    }

    /**
     * Zorg dat openstaande ritten een (vernieuwd) aanbod hebben voor deze chauffeur (inbox/polling).
     */
    public function syncPendingOffersForDriver(string $conn, int $companyId, int $driverId): void
    {
        if ($companyId <= 0 || $driverId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return;
        }

        $driver = \App\Models\User::query()->find($driverId);
        if (! $driver || ! $this->drivers->isChauffeurForCompany($driver, $companyId)) {
            return;
        }

        $ttl = $this->dispatchSettings->offerTtlSeconds($companyId);
        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);
        $now = now();
        $expires = $now->copy()->addSeconds($ttl);

        $rides = RideRequest::on($conn)
            ->where('company_id', $companyId)
            ->whereNull('driver_id')
            ->whereIn('status', [RideRequest::STATUS_PENDING_DISPATCH, RideRequest::STATUS_OFFERED])
            ->dispatchPickupWithinQueueWindow($pickupCutoff)
            ->orderBy('pickup_at')
            ->limit(20)
            ->get();

        foreach ($rides as $ride) {
            if ($this->driverDeclinedRide($conn, (int) $ride->id, $driverId)) {
                continue;
            }

            $hasActive = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('driver_id', $driverId)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->where('expires_at', '>', $now)
                ->exists();

            if ($hasActive) {
                continue;
            }

            RideDispatchOffer::on($conn)->updateOrCreate(
                [
                    'ride_request_id' => $ride->id,
                    'driver_id' => $driverId,
                ],
                [
                    'company_id' => $companyId,
                    'status' => RideDispatchOffer::STATUS_PENDING,
                    'wave' => 1,
                    'offered_at' => $now,
                    'expires_at' => $expires,
                    'responded_at' => null,
                ]
            );

            if ($ride->status === RideRequest::STATUS_PENDING_DISPATCH) {
                $ride->update(['status' => RideRequest::STATUS_OFFERED]);
            }

            $this->push->notifyDriver($driverId, (int) $ride->id);
        }
    }

    /**
     * Verlopen aanbiedingen voor ritten buiten het pickup-grace-venster.
     */
    public function expireOffersForPastPickups(string $conn, int $companyId): int
    {
        if ($companyId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return 0;
        }

        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);

        return RideDispatchOffer::on($conn)
            ->where('company_id', $companyId)
            ->where('status', RideDispatchOffer::STATUS_PENDING)
            ->whereHas('rideRequest', function ($q) use ($pickupCutoff) {
                $q->whereNull('driver_id')
                    ->whereNotNull('pickup_at')
                    ->where('pickup_at', '<', $pickupCutoff);
            })
            ->update([
                'status' => RideDispatchOffer::STATUS_EXPIRED,
                'responded_at' => now(),
            ]);
    }

    /**
     * Ritten zonder chauffeur waarbij elke chauffeur expliciet heeft afgewezen.
     *
     * @return array<int, array{ride_id: int, pickup_at: ?string, pickup_address: ?string, message: string}>
     */
    public function unclaimedRidesForCompany(string $conn, int $companyId): array
    {
        if ($companyId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return [];
        }

        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);

        $rides = RideRequest::on($conn)
            ->where('company_id', $companyId)
            ->whereNull('driver_id')
            ->whereIn('status', [RideRequest::STATUS_PENDING_DISPATCH, RideRequest::STATUS_OFFERED])
            ->dispatchPickupWithinQueueWindow($pickupCutoff)
            ->get();

        $alerts = [];
        foreach ($rides as $ride) {
            if (! $this->allOffersDeclinedForRide($conn, (int) $ride->id)) {
                continue;
            }

            $alerts[] = [
                'ride_id' => (int) $ride->id,
                'pickup_at' => $ride->pickup_at?->toIso8601String(),
                'pickup_address' => $ride->pickup_address,
                'message' => 'Rit #'.$ride->id.': geen chauffeur heeft deze rit opgepakt. Iemand moet deze oppakken.',
            ];
        }

        return $alerts;
    }

    protected function driverDeclinedRide(string $conn, int $rideRequestId, int $driverId): bool
    {
        return RideDispatchOffer::on($conn)
            ->where('ride_request_id', $rideRequestId)
            ->where('driver_id', $driverId)
            ->where('status', RideDispatchOffer::STATUS_DECLINED)
            ->exists();
    }

    protected function allOffersDeclinedForRide(string $conn, int $rideRequestId): bool
    {
        $offers = RideDispatchOffer::on($conn)
            ->where('ride_request_id', $rideRequestId)
            ->get();

        if ($offers->isEmpty()) {
            return false;
        }

        $hasActionable = $offers->contains(function (RideDispatchOffer $offer) {
            return $offer->status === RideDispatchOffer::STATUS_PENDING
                && $offer->expires_at
                && $offer->expires_at->isFuture();
        });

        if ($hasActionable) {
            return false;
        }

        return $offers->every(fn (RideDispatchOffer $offer) => $offer->status === RideDispatchOffer::STATUS_DECLINED);
    }
}
