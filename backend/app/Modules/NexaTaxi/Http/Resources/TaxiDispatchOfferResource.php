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
    /** @var array<int, true>|null */
    private static ?array $noResponseRideIds = null;

    /** @var array<int, int> */
    private static array $offerTtlByCompany = [];

    /**
     * @param  list<int>  $rideIds
     */
    public static function preloadWaitingState(string $conn, array $rideIds): void
    {
        self::$noResponseRideIds = [];
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $rideIds),
            fn (int $id) => $id > 0
        )));
        if ($ids === []) {
            return;
        }

        $found = RideDispatchOffer::on($conn)
            ->whereIn('ride_request_id', $ids)
            ->whereIn('status', [
                RideDispatchOffer::STATUS_EXPIRED,
                RideDispatchOffer::STATUS_DECLINED,
            ])
            ->pluck('ride_request_id');

        foreach ($found as $id) {
            self::$noResponseRideIds[(int) $id] = true;
        }
    }

    public static function clearWaitingStatePreload(): void
    {
        self::$noResponseRideIds = null;
        self::$offerTtlByCompany = [];
    }

    public static function fromOffer(
        RideDispatchOffer $offer,
        ?RideRequest $ride = null,
        bool $isScheduledOverdue = false,
        bool $includeBilling = false
    ): array {
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
            $offerTtlSeconds = self::$offerTtlByCompany[$companyId]
                ?? (self::$offerTtlByCompany[$companyId] = $dispatchSettings->offerTtlSeconds($companyId));
            $isPickupOverdue = $dispatchSettings->offerPickupIsPast($ride);

            $hadNoResponse = self::$noResponseRideIds !== null
                ? isset(self::$noResponseRideIds[(int) $ride->id])
                : RideDispatchOffer::on($conn)
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
                self::rideSummary($ride, $isScheduledOverdue, $includeBilling, (int) ($offer->company_id ?? 0) ?: null),
                ['is_pickup_overdue' => $isPickupOverdue || $isScheduledOverdue]
            ) : null,
            'actions' => [
                'accept' => url("/api/taxi/v1/driver/dispatch/offers/{$offer->id}/accept"),
                'decline' => url("/api/taxi/v1/driver/dispatch/offers/{$offer->id}/decline"),
            ],
        ];
    }

    public static function rideSummary(
        RideRequest $ride,
        bool $isScheduledOverdue = false,
        bool $includeBilling = true,
        ?int $offerCompanyId = null,
    ): array {
        $payments = app(TaxiRidePaymentService::class);
        $conn = $ride->getConnectionName();
        $isContract = $ride->isContractRide();
        $feeBreakdown = self::feeBreakdownForDriver($ride, $offerCompanyId);
        $isNetwork = $ride->isNetworkFulfilled()
            || ($feeBreakdown !== null && ! empty($feeBreakdown['is_network']));

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

        $vehicleId = $ride->vehicle_id ? (int) $ride->vehicle_id : null;
        $vehicleLabel = null;
        $vehiclePlate = null;
        $vehicleName = null;
        $vehicleModel = null;
        if ($vehicleId && $ride->relationLoaded('vehicle') && $ride->vehicle) {
            $vehicleModel = $ride->vehicle;
        } elseif ($vehicleId) {
            try {
                $vehicleModel = \App\Modules\NexaTaxi\Models\Vehicle::on($conn)->find($vehicleId);
            } catch (\Throwable) {
                $vehicleModel = null;
            }
        }
        if ($vehicleModel) {
            $vehiclePlate = trim((string) ($vehicleModel->license_plate ?? '')) ?: null;
            $vehicleName = trim((string) ($vehicleModel->name ?? '')) ?: null;
            $vehicleLabel = $vehiclePlate ?: ($vehicleName ?: $vehicleModel->fleetLabel());
        }

        $canHandOverToNetwork = self::canHandOverToNetwork($ride);

        return [
            'id' => $ride->id,
            'status' => $ride->status,
            'ride_type' => $ride->ride_type,
            'source' => $ride->source,
            'is_contract' => $isContract,
            'is_nexa_suite' => $ride->isNexaSuiteBooking(),
            'nexa_suite_label' => $ride->isNexaSuiteBooking() ? $ride->nexaSuiteLabel() : null,
            'is_network_ride' => $isNetwork,
            'network_label' => $isNetwork ? 'NEXA Network' : null,
            'owner_company_name' => self::ownerCompanyNameForRide($ride, $feeBreakdown),
            'fee_breakdown' => $feeBreakdown,
            'vehicle_id' => $vehicleId,
            'vehicle_label' => $vehicleLabel,
            'vehicle_plate' => $vehiclePlate,
            'vehicle_name' => $vehicleName,
            'can_hand_over_to_network' => $canHandOverToNetwork,
            'payment_status' => $ride->payment_status,
            'payment_method' => $ride->payment_method,
            'payment_paid' => $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID,
            'settlement_status' => $ride->settlement_status,
            'settlement_status_label' => $ride->settlement_status
                ? ($ride->settlement_status_label)
                : null,
            'settlement_hold_until' => $ride->settlement_hold_until?->toIso8601String(),
            'settlement_payable' => $ride->isSettlementPayable(),
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
            'customer_note' => $ride->customer_note ? (string) $ride->customer_note : null,
            'baggage' => self::baggagePayload($ride),
            'distance_km' => $ride->distance_meters ? round($ride->distance_meters / 1000, 1) : null,
            'duration_seconds' => $ride->duration_seconds !== null ? (int) $ride->duration_seconds : null,
            'duration_minutes' => $ride->duration_minutes,
            'stops' => $stopsMeta,
            'schedule' => $schedule,
            'payment' => $includeBilling ? $payments->paymentSummaryForRide($ride) : null,
            'invoice' => ($includeBilling && ! $isContract)
                ? app(TaxiRideInvoiceService::class)->driverInvoicePayload($ride)
                : null,
            'actions' => [
                'start' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/start"),
                'release' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/release"),
                'hand_over_network' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/hand-over-network"),
                'release_return' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/release-return"),
                'start_return' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/start-return"),
                'complete' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/complete"),
                'stops' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/stops"),
            ],
        ];
    }

    public static function canHandOverToNetwork(RideRequest $ride): bool
    {
        if ($ride->isContractRide() || $ride->isNetworkFulfilled()) {
            return false;
        }

        $ownerId = (int) ($ride->company_id ?? 0);
        if ($ownerId <= 0) {
            return false;
        }

        if (! in_array($ride->status, [
            RideRequest::STATUS_PENDING_DISPATCH,
            RideRequest::STATUS_OFFERED,
            RideRequest::STATUS_ACCEPTED,
        ], true)) {
            return false;
        }

        $settings = app(TaxiDispatchSettingsService::class);
        if (! $settings->networkEnabled($ownerId)
            || $settings->networkMode($ownerId) === TaxiDispatchSettingsService::NETWORK_MODE_OFF) {
            return false;
        }

        return $settings->networkPartnerCompanyIds($ownerId) !== [];
    }

    /**
     * Fee-splitsing voor chauffeur bij marketplace/network (zelfde % als marketplace fee).
     *
     * @return array{
     *   customer_pays: float,
     *   owner_name: string,
     *   executor_name: string,
     *   nexa_fee: float,
     *   nexa_fee_percent: int,
     *   is_marketplace: bool,
     *   is_network: bool
     * }|null
     */
    public static function feeBreakdownForDriver(RideRequest $ride, ?int $offerCompanyId = null): ?array
    {
        $isMarketplace = $ride->isNexaSuiteBooking();
        $ownerId = (int) ($ride->company_id ?? 0);
        $executorId = (int) ($ride->fulfilling_company_id ?? 0);
        $offerCompanyId = $offerCompanyId !== null ? (int) $offerCompanyId : 0;

        $isNetwork = $ride->isNetworkFulfilled();
        if (! $isNetwork && $ownerId > 0 && $offerCompanyId > 0 && $offerCompanyId !== $ownerId) {
            $isNetwork = true;
            $executorId = $offerCompanyId;
        }

        if (! $isMarketplace && ! $isNetwork) {
            return null;
        }

        if ($executorId <= 0) {
            $executorId = $offerCompanyId > 0 ? $offerCompanyId : $ownerId;
        }

        $gross = (float) ($ride->final_price ?? $ride->quoted_price ?? 0);
        $percent = \App\Support\NexaMarketplaceFeeCopy::percent();
        $fee = round(max(0, $gross) * ($percent / 100), 2);

        $ownerName = self::companyDisplayName($ownerId);
        if ($ownerName === '—' && $isMarketplace) {
            $ownerName = 'NEXA Suite';
        }

        $executorName = self::companyDisplayName($executorId);
        if ($executorName === '—' && $ownerId > 0 && $executorId === $ownerId) {
            $executorName = $ownerName;
        }

        return [
            'customer_pays' => round(max(0, $gross), 2),
            'owner_name' => $ownerName,
            'executor_name' => $executorName,
            'nexa_fee' => $fee,
            'nexa_fee_percent' => $percent,
            'is_marketplace' => $isMarketplace,
            'is_network' => $isNetwork,
        ];
    }

    private static function companyDisplayName(int $companyId): string
    {
        if ($companyId <= 0) {
            return '—';
        }

        $name = trim((string) (\App\Models\Company::query()->whereKey($companyId)->value('name') ?? ''));

        return $name !== '' ? $name : '—';
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
        $feeBreakdown = self::feeBreakdownForDriver($ride);
        $isNetwork = $ride->isNetworkFulfilled()
            || ($feeBreakdown !== null && ! empty($feeBreakdown['is_network']));

        return [
            'id' => $ride->id,
            'status' => $status,
            'status_label' => RideRequest::statusLabels()[$status] ?? $status,
            'is_contract' => $ride->isContractRide(),
            'is_nexa_suite' => $ride->isNexaSuiteBooking(),
            'nexa_suite_label' => $ride->isNexaSuiteBooking() ? $ride->nexaSuiteLabel() : null,
            'is_network_ride' => $isNetwork,
            'network_label' => $isNetwork ? 'NEXA Network' : null,
            'owner_company_name' => self::ownerCompanyNameForRide($ride, $feeBreakdown),
            'fee_breakdown' => $feeBreakdown,
            'payment_status' => $ride->payment_status,
            'payment_method' => $ride->payment_method,
            'payment_paid' => $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID,
            'settlement_status' => $ride->settlement_status,
            'settlement_status_label' => $ride->settlement_status
                ? ($ride->settlement_status_label)
                : null,
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

    /**
     * @return array{items: list<array{key: string, label: string, qty: int}>, summary: string|null}
     */
    public static function baggagePayload(RideRequest $ride): array
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $step = is_array($payload['step_data'] ?? null) ? $payload['step_data'] : [];
        $labels = [
            'large' => 'Grote ruimbagage',
            'small' => 'Kleine ruimbagage',
            'hand' => 'Handbagage',
            'wheelchair' => 'Opvouwbare rolstoel',
            'pets' => 'Huisdieren',
            'winter' => 'Wintersport',
            'golf' => 'Golftas',
        ];

        $items = [];
        foreach (['baggage', 'special_baggage'] as $bagKey) {
            $bag = is_array($step[$bagKey] ?? null) ? $step[$bagKey] : [];
            foreach ($bag as $key => $qty) {
                $count = (int) $qty;
                if ($count <= 0) {
                    continue;
                }
                $keyStr = (string) $key;
                $items[] = [
                    'key' => $keyStr,
                    'label' => $labels[$keyStr] ?? $keyStr,
                    'qty' => $count,
                ];
            }
        }

        $summary = $items === []
            ? null
            : implode(', ', array_map(
                static fn (array $row): string => $row['label'].' × '.$row['qty'],
                $items
            ));

        return [
            'items' => $items,
            'summary' => $summary,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $feeBreakdown
     */
    private static function ownerCompanyNameForRide(RideRequest $ride, ?array $feeBreakdown): ?string
    {
        $fromFee = trim((string) ($feeBreakdown['owner_name'] ?? ''));
        if ($fromFee !== '' && $fromFee !== '—') {
            return $fromFee;
        }

        $ownerId = (int) ($ride->company_id ?? 0);
        if ($ownerId <= 0) {
            return null;
        }

        $name = self::companyDisplayName($ownerId);

        return $name !== '—' ? $name : null;
    }
}
