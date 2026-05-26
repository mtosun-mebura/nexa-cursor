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
        protected TaxiRidePaymentService $ridePayments
    ) {}
    public function acceptOffer(string $conn, User $driver, int $offerId): array
    {
        return DB::connection($conn)->transaction(function () use ($conn, $driver, $offerId) {
            $offer = RideDispatchOffer::on($conn)->whereKey($offerId)->lockForUpdate()->first();
            if (! $offer || (int) $offer->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'offer' => ['Aanbod niet gevonden.'],
                ]);
            }

            if ($offer->status !== RideDispatchOffer::STATUS_PENDING || $offer->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'offer' => ['Dit aanbod is verlopen of niet meer geldig.'],
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

            if (in_array($ride->status, [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_COMPLETED, RideRequest::STATUS_CANCELLED], true)) {
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
                'status' => RideRequest::STATUS_ASSIGNED,
                'company_id' => $ride->company_id ?: $offer->company_id,
            ]);

            $freshRide = $ride->fresh();
            $freshOffer = $offer->fresh();

            app()->terminating(function () use ($conn, $freshRide, $driver) {
                if ($freshRide) {
                    app(TaxiCustomerRideAcceptedNotificationService::class)
                        ->notifyAfterRideAssigned($conn, $freshRide, $driver);
                }
            });

            return [
                'ride' => $freshRide,
                'offer' => $freshOffer,
            ];
        });
    }

    public function declineOffer(string $conn, User $driver, int $offerId): RideDispatchOffer
    {
        $offer = RideDispatchOffer::on($conn)
            ->whereKey($offerId)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        if ($offer->status !== RideDispatchOffer::STATUS_PENDING) {
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

            if (! in_array($ride->status, [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_ACCEPTED], true)) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze rit kan niet worden afgerond.'],
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
