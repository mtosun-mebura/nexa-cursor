<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RideClaimService
{
    public function __construct(
        protected TaxiRidePaymentService $ridePayments,
        protected RideDispatchService $dispatch
    ) {}
    public function acceptOffer(string $conn, User $driver, int $offerId): array
    {
        $result = DB::connection($conn)->transaction(function () use ($conn, $driver, $offerId) {
            $offer = RideDispatchOffer::on($conn)->whereKey($offerId)->lockForUpdate()->first();
            if (! $offer || (int) $offer->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'offer' => ['Aanbod niet gevonden.'],
                ]);
            }

            if (! in_array($offer->status, [
                RideDispatchOffer::STATUS_PENDING,
                RideDispatchOffer::STATUS_DECLINED,
            ], true)) {
                throw ValidationException::withMessages([
                    'offer' => ['Dit aanbod is verlopen of niet meer geldig.'],
                ]);
            }

            $hasActiveRide = RideRequest::on($conn)
                ->where('driver_id', $driver->id)
                ->where('status', RideRequest::STATUS_ASSIGNED)
                ->exists();

            if ($hasActiveRide) {
                throw ValidationException::withMessages([
                    'offer' => ['Rond eerst je lopende rit af voordat je een nieuwe rit accepteert.'],
                ]);
            }

            $ride = RideRequest::on($conn)->whereKey($offer->ride_request_id)->lockForUpdate()->first();
            if (! $ride) {
                throw ValidationException::withMessages(['offer' => ['Rit niet gevonden.']]);
            }

            if ($ride->driver_id && (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'offer' => ['Deze rit is al door een andere chauffeur geaccepteerd.'],
                ]);
            }

            if (in_array($ride->status, [
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_ASSIGNED,
                RideRequest::STATUS_COMPLETED,
                RideRequest::STATUS_CANCELLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'offer' => ['Deze rit kan niet meer worden geaccepteerd.'],
                ]);
            }

            $now = now();

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('id', '!=', $offer->id)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->update([
                    'status' => RideDispatchOffer::STATUS_SUPERSEDED,
                    'responded_at' => $now,
                ]);

            $offer->update([
                'status' => RideDispatchOffer::STATUS_ACCEPTED,
                'responded_at' => $now,
            ]);

            $ride->update([
                'driver_id' => $driver->id,
                'status' => RideRequest::STATUS_ACCEPTED,
                'company_id' => $ride->company_id ?: $offer->company_id,
            ]);

            $freshRide = $ride->fresh();
            $freshOffer = $offer->fresh();

            return [
                'ride' => $freshRide,
                'offer' => $freshOffer,
            ];
        });

        if (! empty($result['ride'])) {
            app(TaxiCustomerRideAcceptedNotificationService::class)
                ->notifyAfterRideAssigned($conn, $result['ride'], $driver);
        }

        return $result;
    }

    public function startRide(string $conn, User $driver, int $rideId): RideRequest
    {
        return DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId) {
            $hasActiveRide = RideRequest::on($conn)
                ->where('driver_id', $driver->id)
                ->where('status', RideRequest::STATUS_ASSIGNED)
                ->whereKeyNot($rideId)
                ->exists();

            if ($hasActiveRide) {
                throw ValidationException::withMessages([
                    'ride' => ['Je hebt al een lopende rit. Rond die eerst af.'],
                ]);
            }

            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($ride->status !== RideRequest::STATUS_ACCEPTED) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze rit kan niet worden gestart.'],
                ]);
            }

            $ride->update(['status' => RideRequest::STATUS_ASSIGNED]);

            return $ride->fresh();
        });
    }

    public function releaseAcceptedRide(string $conn, User $driver, int $rideId): RideRequest
    {
        $companyId = 0;

        $released = DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId, &$companyId) {
            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($ride->status !== RideRequest::STATUS_ACCEPTED) {
                throw ValidationException::withMessages([
                    'ride' => ['Alleen geaccepteerde ritten die nog niet zijn gestart kunnen worden vrijgegeven.'],
                ]);
            }

            $companyId = (int) ($ride->company_id ?? 0);
            $now = now();

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('driver_id', $driver->id)
                ->where('status', RideDispatchOffer::STATUS_ACCEPTED)
                ->update([
                    'status' => RideDispatchOffer::STATUS_DECLINED,
                    'responded_at' => $now,
                ]);

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->update([
                    'status' => RideDispatchOffer::STATUS_EXPIRED,
                    'responded_at' => $now,
                ]);

            $ride->update([
                'driver_id' => null,
                'status' => RideRequest::STATUS_PENDING_DISPATCH,
            ]);

            return $ride->fresh();
        });

        if ($companyId > 0) {
            $this->dispatch->startDispatch($conn, $released, $companyId, [(int) $driver->id]);
        }

        return $released->fresh() ?? $released;
    }

    public function declineOffer(string $conn, User $driver, int $offerId): RideDispatchOffer
    {
        $offer = RideDispatchOffer::on($conn)
            ->whereKey($offerId)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        if (! in_array($offer->status, [
            RideDispatchOffer::STATUS_PENDING,
            RideDispatchOffer::STATUS_EXPIRED,
        ], true)) {
            throw ValidationException::withMessages([
                'offer' => ['Dit aanbod kan niet meer worden afgewezen.'],
            ]);
        }

        $offer->update([
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        return $offer;
    }

    public function completeRide(string $conn, User $driver, int $rideId): RideRequest
    {
        return DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId) {
            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($ride->status !== RideRequest::STATUS_ASSIGNED) {
                throw ValidationException::withMessages([
                    'ride' => ['Start de rit eerst voordat je deze afrondt.'],
                ]);
            }

            if (! $this->ridePayments->canCompleteRide($ride)) {
                throw ValidationException::withMessages([
                    'ride' => ['Rond eerst de betaling af voordat je de rit afrondt.'],
                ]);
            }

            $ride->update(['status' => RideRequest::STATUS_COMPLETED]);

            return $ride->fresh();
        });
    }
}
