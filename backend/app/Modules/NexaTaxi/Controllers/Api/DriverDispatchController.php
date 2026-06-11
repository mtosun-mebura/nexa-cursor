<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideClaimService;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverDispatchController extends Controller
{
    public function inbox(
        Request $request,
        ModuleDatabaseService $moduleDb,
        RideDispatchService $dispatch
    ): JsonResponse {
        $user = $request->user();
        $conn = $moduleDb->getModuleConnectionName('taxi');

        $companyId = (int) $request->attributes->get('taxi_company_id');

        $dispatch->expireStaleOffers($conn);

        if (TaxiDispatchSchema::tablesExist($conn)) {
            $dispatch->expireOffersForPastPickups($conn, $companyId);
            $dispatch->syncPendingOffersForDriver($conn, $companyId, (int) $user->id);
            if (TaxiDispatchSchema::driverAvailabilityExists($conn)) {
                DriverAvailability::on($conn)->updateOrCreate(
                    ['driver_id' => $user->id],
                    ['company_id' => $companyId, 'last_seen_at' => now()]
                );
            }
        }

        $pickupCutoff = app(TaxiDispatchSettingsService::class)->pickupQueueCutoffAt($companyId);

        $offers = RideDispatchOffer::on($conn)
            ->with('rideRequest')
            ->inboxForDriver($user->id, $pickupCutoff)
            ->get()
            ->sortBy(function (RideDispatchOffer $offer) {
                $pickupAt = $offer->rideRequest?->pickup_at;

                return $pickupAt ? $pickupAt->timestamp : PHP_INT_MAX;
            })
            ->values();

        $declinedOffers = RideDispatchOffer::on($conn)
            ->with('rideRequest')
            ->declinedForDriver($user->id, $pickupCutoff)
            ->get()
            ->sortByDesc(function (RideDispatchOffer $offer) {
                return $offer->responded_at?->timestamp ?? 0;
            })
            ->values();

        $unclaimedRides = $dispatch->unclaimedRidesForCompany($conn, $companyId);

        $activeRide = RideRequest::on($conn)
            ->where('driver_id', $user->id)
            ->where('status', RideRequest::STATUS_ASSIGNED)
            ->orderBy('pickup_at')
            ->first();

        $dispatchSettings = app(TaxiDispatchSettingsService::class);

        $acceptedRides = RideRequest::on($conn)
            ->where('driver_id', $user->id)
            ->where('status', RideRequest::STATUS_ACCEPTED)
            ->orderBy('pickup_at')
            ->get();

        $scheduledRides = $acceptedRides
            ->filter(fn (RideRequest $ride) => ! $dispatchSettings->scheduledRideIsOverdue($ride, $companyId))
            ->values();

        $overdueScheduledRides = $acceptedRides
            ->filter(fn (RideRequest $ride) => $dispatchSettings->scheduledRideIsOverdue($ride, $companyId))
            ->values();

        return response()->json([
            'data' => [
                'offers' => $offers->map(
                    fn (RideDispatchOffer $o) => TaxiDispatchOfferResource::fromOffer($o, $o->rideRequest)
                )->values(),
                'declined_offers' => $declinedOffers->map(
                    fn (RideDispatchOffer $o) => TaxiDispatchOfferResource::fromOffer($o, $o->rideRequest)
                )->values(),
                'active_ride' => $activeRide
                    ? TaxiDispatchOfferResource::rideSummary($activeRide)
                    : null,
                'scheduled_rides' => $scheduledRides
                    ->map(fn (RideRequest $ride) => TaxiDispatchOfferResource::rideSummary($ride))
                    ->values(),
                'overdue_scheduled_rides' => $overdueScheduledRides
                    ->map(fn (RideRequest $ride) => TaxiDispatchOfferResource::rideSummary($ride, true))
                    ->values(),
            ],
            'meta' => array_merge(
                [
                    'server_time' => now()->toIso8601String(),
                    'poll_interval_ms' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 3000),
                    'offer_ttl_seconds' => $dispatchSettings->offerTtlSeconds($companyId),
                    'past_pickup_grace_hours' => $dispatchSettings->pastPickupGraceHours($companyId),
                    'unclaimed_rides' => $unclaimedRides,
                ],
                app(TaxiDispatchSettingsService::class)->paymentOptionsForTenant($companyId)
            ),
        ]);
    }

    public function accept(
        Request $request,
        int $offer,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $result = $claim->acceptOffer($conn, $request->user(), $offer);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan rit niet accepteren.',
                'errors' => $e->errors(),
            ], 409);
        }

        return response()->json([
            'message' => 'Rit geaccepteerd.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($result['ride']),
                'offer_id' => $result['offer']->id,
            ],
        ]);
    }

    public function start(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $started = $claim->startRide($conn, $request->user(), $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan rit niet starten.',
                'errors' => $e->errors(),
            ], 409);
        }

        return response()->json([
            'message' => 'Rit gestart.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($started),
            ],
        ]);
    }

    public function release(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $claim->releaseAcceptedRide($conn, $request->user(), $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan rit niet vrijgeven.',
                'errors' => $e->errors(),
            ], 409);
        }

        return response()->json([
            'message' => 'Rit vrijgegeven. Andere chauffeurs kunnen deze nu overnemen.',
        ]);
    }

    public function decline(
        Request $request,
        int $offer,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $claim->declineOffer($conn, $request->user(), $offer);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['message' => 'Rit afgewezen.']);
    }

    public function complete(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $completed = $claim->completeRide($conn, $request->user(), $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan rit niet afronden.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Rit afgerond. Je bent weer beschikbaar voor nieuwe ritten.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($completed),
            ],
        ]);
    }
}
