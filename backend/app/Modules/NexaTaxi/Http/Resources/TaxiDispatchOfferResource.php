<?php

namespace App\Modules\NexaTaxi\Http\Resources;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Services\ContractOccurrenceGeneratorService;
use App\Modules\NexaTaxi\Services\ContractRideStopService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRideInvoiceService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;

class TaxiDispatchOfferResource
{
    public static function fromOffer(RideDispatchOffer $offer, ?RideRequest $ride = null, bool $isScheduledOverdue = false): array
    {
        $ride ??= $offer->relationLoaded('rideRequest') ? $offer->rideRequest : null;

        $secondsRemaining = $offer->expires_at
            ? max(0, (int) now()->diffInSeconds($offer->expires_at, false))
            : 0;

        $waitingSinceAt = null;
        $secondsWaiting = 0;
        $isWaiting = false;
        $isPickupOverdue = false;
        if ($ride && ! $ride->driver_id) {
            $conn = $offer->getConnectionName();
            $waitingSinceAt = $ride->created_at;
            if ($waitingSinceAt) {
                $secondsWaiting = max(0, (int) $waitingSinceAt->diffInSeconds(now(), false));
            }
            $companyId = (int) ($ride->company_id ?: $offer->company_id);
            $dispatchSettings = app(TaxiDispatchSettingsService::class);
            $offerTtlSeconds = $dispatchSettings->offerTtlSeconds($companyId);
            $isPickupOverdue = $dispatchSettings->offerPickupIsPast($ride);

            $hadNoResponse = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->whereIn('status', [
                    RideDispatchOffer::STATUS_EXPIRED,
                    RideDispatchOffer::STATUS_DECLINED,
                ])
                ->exists();

            // Blijft wachten zolang de rit openstaat en de acceptatietijd minstens één keer is verstreken
            // (ook na vernieuwd aanbod — anders verdwijnt "verlopen" door updateOrCreate).
            $isWaiting = $hadNoResponse
                || $secondsWaiting >= $offerTtlSeconds
                || $secondsRemaining <= 0
                || $isPickupOverdue;
        }

        $urgency = ($isWaiting || $isPickupOverdue)
            ? 'waiting'
            : ($secondsRemaining > 0 && $secondsRemaining <= 60 ? 'urgent' : 'normal');

        return [
            'id' => $offer->id,
            'status' => $offer->status,
            'expires_at' => $offer->expires_at?->toIso8601String(),
            'offered_at' => $offer->offered_at?->toIso8601String(),
            'archived_at' => $offer->archived_at?->toIso8601String(),
            'seconds_remaining' => $secondsRemaining,
            'seconds_waiting' => $secondsWaiting,
            'waiting_since_at' => $waitingSinceAt?->toIso8601String(),
            'is_waiting' => $isWaiting,
            'is_pickup_overdue' => $isPickupOverdue,
            'urgency' => $urgency,
            'ride' => $ride ? array_merge(
                self::rideSummary($ride, $isScheduledOverdue),
                ['is_pickup_overdue' => $isPickupOverdue || $isScheduledOverdue]
            ) : null,
            'actions' => [
                'accept' => url("/api/taxi/v1/driver/dispatch/offers/{$offer->id}/accept"),
                'decline' => url("/api/taxi/v1/driver/dispatch/offers/{$offer->id}/decline"),
            ],
        ];
    }

    public static function rideSummary(RideRequest $ride, bool $isScheduledOverdue = false): array
    {
        $payments = app(TaxiRidePaymentService::class);
        $conn = $ride->getConnectionName();
        $isContract = $ride->isContractRide();

        $stopsMeta = null;
        $schedule = null;
        if ($ride->ride_type === RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
            $progress = app(ContractRideStopService::class)->groupRideProgress($conn, (int) $ride->id);
            $schedule = app(ContractOccurrenceGeneratorService::class)->schedulePayloadForRide($conn, $ride);
            $stopsMeta = [
                'total' => $progress['stops_total'],
                'pickups_total' => $progress['pickups_total'],
                'pickups_done' => $progress['pickups_done'],
                'all_pickups_done' => $progress['all_pickups_done'],
            ];
        }

        $scheduledDate = null;
        if ($isContract) {
            $occurrenceDate = TransportOccurrence::on($conn)
                ->where('ride_request_id', $ride->id)
                ->value('scheduled_date');
            if ($occurrenceDate) {
                $scheduledDate = Carbon::parse($occurrenceDate)->toDateString();
            } elseif ($ride->pickup_at) {
                $scheduledDate = $ride->pickup_at->copy()->timezone(ContractTransportTimezone::TIMEZONE)->toDateString();
            }
        }

        $onReturnLeg = $ride->isReturnTrip() && $ride->hasOutboundCompleted();
        $returnLegCoords = [
            'pickup_lat' => $onReturnLeg
                ? ($ride->dropoff_lat !== null ? (float) $ride->dropoff_lat : null)
                : ($ride->pickup_lat !== null ? (float) $ride->pickup_lat : null),
            'pickup_lng' => $onReturnLeg
                ? ($ride->dropoff_lng !== null ? (float) $ride->dropoff_lng : null)
                : ($ride->pickup_lng !== null ? (float) $ride->pickup_lng : null),
            'dropoff_lat' => $onReturnLeg
                ? ($ride->pickup_lat !== null ? (float) $ride->pickup_lat : null)
                : ($ride->dropoff_lat !== null ? (float) $ride->dropoff_lat : null),
            'dropoff_lng' => $onReturnLeg
                ? ($ride->pickup_lng !== null ? (float) $ride->pickup_lng : null)
                : ($ride->dropoff_lng !== null ? (float) $ride->dropoff_lng : null),
        ];

        return [
            'id' => $ride->id,
            'status' => $ride->status,
            'ride_type' => $ride->ride_type,
            'source' => $ride->source,
            'is_contract' => $isContract,
            'contract_label' => $isContract ? 'Contract' : null,
            'return_trip' => $ride->isReturnTrip(),
            'return_at' => $ride->resolveReturnAt()?->toIso8601String(),
            'return_leg' => $ride->currentReturnLeg(),
            'outbound_completed_at' => $ride->outbound_completed_at?->toIso8601String(),
            'return_started_at' => $ride->return_started_at?->toIso8601String(),
            'outbound_completed' => $ride->hasOutboundCompleted(),
            'return_outbound_done_label' => $ride->hasOutboundCompleted() ? 'Heenrit al uitgevoerd' : null,
            'can_release_return' => $ride->canReleaseReturnLeg(),
            'original_pickup_address' => $ride->pickup_address,
            'original_dropoff_address' => $ride->dropoff_address,
            'transport_contract_id' => $ride->transport_contract_id ? (int) $ride->transport_contract_id : null,
            'is_scheduled_overdue' => $isScheduledOverdue,
            'is_pickup_overdue' => $isScheduledOverdue || app(TaxiDispatchSettingsService::class)->offerPickupIsPast($ride),
            'requires_pickup_adjustment' => $isScheduledOverdue,
            'pickup_proposal' => [
                'status' => $ride->pickup_proposal_status,
                'proposed_at' => ContractTransportTimezone::toDriverIso8601($ride->pickup_proposal_at),
                'customer_remark' => $ride->pickup_proposal_customer_remark,
                'sent_at' => $ride->pickup_proposal_sent_at?->toIso8601String(),
                'responded_at' => $ride->pickup_proposal_responded_at?->toIso8601String(),
            ],
            'scheduled_date' => $scheduledDate,
            'created_at' => $ride->created_at?->toIso8601String(),
            'waiting_since_at' => $ride->created_at?->toIso8601String(),
            'pickup_address' => $ride->driverLegPickupAddress(),
            'dropoff_address' => $ride->driverLegDropoffAddress(),
            'pickup_lat' => $returnLegCoords['pickup_lat'],
            'pickup_lng' => $returnLegCoords['pickup_lng'],
            'dropoff_lat' => $returnLegCoords['dropoff_lat'],
            'dropoff_lng' => $returnLegCoords['dropoff_lng'],
            'pickup_at' => $schedule['departure_at'] ?? ContractTransportTimezone::toDriverIso8601($ride->effectivePickupAt()),
            'quoted_price' => $ride->quoted_price !== null ? (float) $ride->quoted_price : null,
            'return_trip_leg_amounts' => $ride->returnTripLegAmountsPayload(),
            'passengers' => (int) $ride->passengers,
            'customer_name' => $ride->customer_name,
            'customer_phone' => $ride->customer_phone,
            'distance_km' => $ride->distance_meters ? round($ride->distance_meters / 1000, 1) : null,
            'duration_seconds' => $ride->duration_seconds !== null ? (int) $ride->duration_seconds : null,
            'duration_minutes' => $ride->duration_minutes,
            'stops' => $stopsMeta,
            'schedule' => $schedule,
            'payment' => $payments->paymentSummaryForRide($ride),
            'invoice' => $isContract ? null : app(TaxiRideInvoiceService::class)->driverInvoicePayload($ride),
            'actions' => [
                'start' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/start"),
                'release' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/release"),
                'release_return' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/release-return"),
                'start_return' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/start-return"),
                'complete' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/complete"),
                'stops' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/stops"),
            ],
        ];
    }

    /**
     * Compacte ritkaart voor de chauffeur-planning (geen betaling/factuur).
     *
     * @return array<string, mixed>
     */
    public static function planningRide(RideRequest $ride): array
    {
        $pickupAt = $ride->pickup_at;
        $status = (string) $ride->status;

        return [
            'id' => $ride->id,
            'status' => $status,
            'status_label' => RideRequest::statusLabels()[$status] ?? $status,
            'is_contract' => $ride->isContractRide(),
            'pickup_address' => (string) $ride->pickup_address,
            'dropoff_address' => (string) $ride->dropoff_address,
            'pickup_lat' => $ride->pickup_lat !== null ? (float) $ride->pickup_lat : null,
            'pickup_lng' => $ride->pickup_lng !== null ? (float) $ride->pickup_lng : null,
            'dropoff_lat' => $ride->dropoff_lat !== null ? (float) $ride->dropoff_lat : null,
            'dropoff_lng' => $ride->dropoff_lng !== null ? (float) $ride->dropoff_lng : null,
            'pickup_at' => ContractTransportTimezone::toDriverIso8601($pickupAt),
            'planning_date' => ContractTransportTimezone::asAmsterdamWall($pickupAt)?->toDateString(),
            'customer_name' => $ride->customer_name,
            'passengers' => (int) $ride->passengers,
            'quoted_price' => $ride->quoted_price !== null ? (float) $ride->quoted_price : null,
        ];
    }
}
