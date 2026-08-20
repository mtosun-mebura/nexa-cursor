<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Services\RideClaimService;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiPickupProposalService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);

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

        $dispatchSettings = app(TaxiDispatchSettingsService::class);
        $pickupCutoff = $dispatchSettings->pickupQueueCutoffAt($companyId);

        $offers = RideDispatchOffer::on($conn)
            ->with('rideRequest')
            ->inboxForDriver($user->id, $pickupCutoff)
            ->get()
            ->sortBy(function (RideDispatchOffer $offer) {
                $pickupAt = $offer->rideRequest?->pickup_at;

                return $pickupAt ? $pickupAt->timestamp : PHP_INT_MAX;
            })
            ->values();

        $overdueReleasedOffers = RideDispatchOffer::on($conn)
            ->with('rideRequest')
            ->overdueReleasedForDriver($user->id, $pickupCutoff)
            ->get()
            ->sortByDesc(function (RideDispatchOffer $offer) {
                return $offer->responded_at?->timestamp ?? $offer->offered_at?->timestamp ?? 0;
            })
            ->values();

        $overdueReleasedOfferIds = $overdueReleasedOffers->pluck('id');

        $archivedOffers = RideDispatchOffer::on($conn)
            ->with('rideRequest')
            ->archivedForDriver($user->id)
            ->orderByDesc('archived_at')
            ->limit(100)
            ->get();

        $declinedOffers = RideDispatchOffer::on($conn)
            ->with('rideRequest')
            ->declinedForDriver($user->id, $pickupCutoff)
            ->whereNotIn('id', $overdueReleasedOfferIds)
            ->get()
            ->sortByDesc(function (RideDispatchOffer $offer) {
                return $offer->responded_at?->timestamp ?? 0;
            })
            ->values();

        $unclaimedRides = $dispatch->unclaimedRidesForCompany($conn, $companyId);

        $assignedRides = RideRequest::on($conn)
            ->where('driver_id', $user->id)
            ->where('status', RideRequest::STATUS_ASSIGNED)
            ->orderBy('pickup_at')
            ->get();

        $activeRide = $assignedRides->first(
            fn (RideRequest $ride) => $ride->blocksDriverFromOtherRides()
        ) ?? $assignedRides->first();

        $parkedAssignedRides = $assignedRides
            ->filter(fn (RideRequest $ride) => $activeRide && (int) $ride->id !== (int) $activeRide->id)
            ->values();

        if ($activeRide && $activeRide->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
            $openPayment = RidePayment::on($conn)
                ->where('ride_request_id', $activeRide->id)
                ->whereIn('channel', [RidePayment::CHANNEL_DRIVER, RidePayment::CHANNEL_BOOKING])
                ->where('status', RidePayment::STATUS_OPEN)
                ->orderByDesc('id')
                ->first();

            if ($openPayment) {
                app(TaxiRidePaymentService::class)->syncRidePaymentFromMollie($conn, $openPayment);
                $activeRide = $activeRide->fresh();
            }
        }

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

        $absenceAlert = Cache::pull('taxi_driver_absence_alert:'.(int) $user->id);
        $pickupProposalAlert = Cache::pull('taxi_driver_pickup_proposal_alert:'.(int) $user->id);

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
                'parked_assigned_rides' => $parkedAssignedRides
                    ->map(fn (RideRequest $ride) => TaxiDispatchOfferResource::rideSummary($ride))
                    ->values(),
                'scheduled_rides' => $scheduledRides
                    ->map(fn (RideRequest $ride) => TaxiDispatchOfferResource::rideSummary($ride))
                    ->values(),
                'overdue_scheduled_rides' => $overdueScheduledRides
                    ->map(fn (RideRequest $ride) => TaxiDispatchOfferResource::rideSummary($ride, true))
                    ->values(),
                'overdue_released_offers' => $overdueReleasedOffers
                    ->map(function (RideDispatchOffer $offer) {
                        return TaxiDispatchOfferResource::fromOffer($offer, $offer->rideRequest, true);
                    })
                    ->values(),
                'archived_offers' => $archivedOffers
                    ->map(function (RideDispatchOffer $offer) {
                        return TaxiDispatchOfferResource::fromOffer($offer, $offer->rideRequest, true);
                    })
                    ->values(),
                'absence_alert' => is_array($absenceAlert) ? $absenceAlert : null,
                'pickup_proposal_alert' => is_array($pickupProposalAlert) ? $pickupProposalAlert : null,
            ],
            'meta' => array_merge(
                [
                    'server_time' => now()->toIso8601String(),
                    'poll_interval_ms' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 3000),
                    'offer_ttl_seconds' => $dispatchSettings->offerTtlSeconds($companyId),
                    'past_pickup_grace_minutes' => $dispatchSettings->pastPickupGraceMinutes($companyId),
                    'past_pickup_grace_hours' => $dispatchSettings->pastPickupGraceHours($companyId),
                    'unclaimed_rides' => $unclaimedRides,
                ],
                $dispatchSettings->paymentOptionsForTenant($companyId)
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
        $data = $request->validate([
            'pickup_at' => ['nullable', 'date'],
        ]);

        try {
            $result = $claim->acceptOffer(
                $conn,
                $request->user(),
                $offer,
                $data['pickup_at'] ?? null
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan rit niet accepteren.',
                'errors' => $e->errors(),
            ], 409);
        }

        $message = ! empty($result['pickup_proposed'])
            ? 'Rit geaccepteerd. Nieuw ophaalmoment voorgesteld aan de klant via WhatsApp.'
            : 'Rit geaccepteerd.';

        return response()->json([
            'message' => $message,
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($result['ride']),
                'offer_id' => $result['offer']->id,
                'pickup_proposed' => ! empty($result['pickup_proposed']),
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

    public function releaseReturn(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $claim->releaseReturnLeg($conn, $request->user(), $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan retourrit niet vrijgeven.',
                'errors' => $e->errors(),
            ], 409);
        }

        return response()->json([
            'message' => 'Retourrit vrijgegeven. Andere chauffeurs kunnen de terugweg overnemen.',
        ]);
    }

    public function startReturn(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $started = $claim->startReturnLeg($conn, $request->user(), $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan retourrit niet starten.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Retourrit gestart.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($started),
            ],
        ]);
    }

    public function decline(
        Request $request,
        int $offer,
        ModuleDatabaseService $moduleDb,
        RideClaimService $claim
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        $validated = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $claim->declineOffer(
                $conn,
                $request->user(),
                $offer,
                isset($validated['decline_reason']) ? (string) $validated['decline_reason'] : null
            );
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
        RideClaimService $claim,
        TaxiDispatchSettingsService $dispatchSettings,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = RideRequest::on($conn)->find($ride);
        $allowOverdueContractComplete = $rideModel
            && $rideModel->isContractRide()
            && $rideModel->status === RideRequest::STATUS_ACCEPTED
            && $dispatchSettings->scheduledRideIsOverdue(
                $rideModel,
                (int) ($rideModel->company_id ?? 0) > 0 ? (int) $rideModel->company_id : null
            );

        try {
            $completed = $claim->completeRide(
                $conn,
                $request->user(),
                $ride,
                $allowOverdueContractComplete,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan rit niet afronden.',
                'errors' => $e->errors(),
            ], 422);
        }

        $isOutboundOnlyComplete = $completed->isReturnTrip()
            && $completed->hasOutboundCompleted()
            && ! $completed->hasReturnLegStarted();

        $message = $isOutboundOnlyComplete
            ? 'Heenrit afgerond. Start de retour of geef deze vrij voor een andere chauffeur.'
            : 'Rit afgerond. Je bent weer beschikbaar voor nieuwe ritten.';

        return response()->json([
            'message' => $message,
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($completed),
                'outbound_completed' => $isOutboundOnlyComplete,
            ],
        ]);
    }

    public function proposePickup(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiPickupProposalService $proposals,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $data = $request->validate([
            'pickup_at' => ['required', 'date'],
        ]);

        try {
            $updated = $proposals->proposeNewPickup(
                $conn,
                $request->user(),
                $ride,
                (string) $data['pickup_at']
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Kan voorstel niet versturen.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Nieuw ophaalmoment voorgesteld aan de klant via WhatsApp.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($updated, true),
            ],
        ]);
    }

    public function archiveOffer(
        Request $request,
        int $offer,
        ModuleDatabaseService $moduleDb,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);
        $user = $request->user();

        $row = RideDispatchOffer::on($conn)
            ->whereKey($offer)
            ->where('driver_id', $user->id)
            ->whereIn('status', [RideDispatchOffer::STATUS_DECLINED, RideDispatchOffer::STATUS_EXPIRED])
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Verlopen rit niet gevonden.'], 404);
        }

        if ($row->archived_at) {
            return response()->json([
                'message' => 'Rit staat al in het archief.',
                'data' => [
                    'offer' => TaxiDispatchOfferResource::fromOffer($row, $row->rideRequest, true),
                ],
            ]);
        }

        $row->update(['archived_at' => now()]);
        $fresh = $row->fresh(['rideRequest']) ?? $row;

        return response()->json([
            'message' => 'Rit gearchiveerd.',
            'data' => [
                'offer' => TaxiDispatchOfferResource::fromOffer($fresh, $fresh->rideRequest, true),
            ],
        ]);
    }

    public function deleteArchivedOffer(
        Request $request,
        int $offer,
        ModuleDatabaseService $moduleDb,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);
        $user = $request->user();

        $row = RideDispatchOffer::on($conn)
            ->whereKey($offer)
            ->where('driver_id', $user->id)
            ->whereNotNull('archived_at')
            ->whereIn('status', [RideDispatchOffer::STATUS_DECLINED, RideDispatchOffer::STATUS_EXPIRED])
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Gearchiveerde rit niet gevonden.'], 404);
        }

        $row->delete();

        return response()->json([
            'message' => 'Rit verwijderd uit archief.',
        ]);
    }

    public function deleteArchivedOffers(
        Request $request,
        ModuleDatabaseService $moduleDb,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);
        $user = $request->user();

        $data = $request->validate([
            'offer_ids' => ['required', 'array', 'min:1', 'max:100'],
            'offer_ids.*' => ['integer', 'min:1'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['offer_ids'])));

        $deleted = RideDispatchOffer::on($conn)
            ->where('driver_id', $user->id)
            ->whereNotNull('archived_at')
            ->whereIn('status', [RideDispatchOffer::STATUS_DECLINED, RideDispatchOffer::STATUS_EXPIRED])
            ->whereIn('id', $ids)
            ->delete();

        if ($deleted < 1) {
            return response()->json(['message' => 'Geen gearchiveerde ritten gevonden om te verwijderen.'], 404);
        }

        return response()->json([
            'message' => $deleted === 1
                ? '1 rit verwijderd uit archief.'
                : $deleted.' ritten verwijderd uit archief.',
            'data' => [
                'deleted' => $deleted,
            ],
        ]);
    }
}
