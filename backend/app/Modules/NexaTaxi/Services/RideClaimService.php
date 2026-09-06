<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\WhatsAppBookingMessageComposer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RideClaimService
{
    public function __construct(
        protected TaxiRidePaymentService $ridePayments,
        protected RideDispatchService $dispatch,
        protected ContractRideStopService $contractStops,
    ) {}

    public function acceptOffer(string $conn, User $driver, int $offerId, ?string $pickupAt = null): array
    {
        $result = DB::connection($conn)->transaction(function () use ($conn, $driver, $offerId, $pickupAt) {
            $offer = RideDispatchOffer::on($conn)->whereKey($offerId)->lockForUpdate()->first();
            if (! $offer || (int) $offer->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'offer' => ['Aanbod niet gevonden.'],
                ]);
            }

            if (! in_array($offer->status, [
                RideDispatchOffer::STATUS_PENDING,
                RideDispatchOffer::STATUS_DECLINED,
                RideDispatchOffer::STATUS_EXPIRED,
            ], true)) {
                throw ValidationException::withMessages([
                    'offer' => ['Dit aanbod is verlopen of niet meer geldig.'],
                ]);
            }

            // Accepteren mag tijdens een lopende rit (komt als geplande/geaccepteerde rit).
            // Starten van een tweede toegewezen rit blijft geblokkeerd in startRide().

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
            $requiresNewPickup = in_array($offer->status, [
                RideDispatchOffer::STATUS_DECLINED,
                RideDispatchOffer::STATUS_EXPIRED,
            ], true) && app(TaxiDispatchSettingsService::class)->offerPickupIsPast($ride);

            if ($requiresNewPickup && ($pickupAt === null || trim($pickupAt) === '')) {
                throw ValidationException::withMessages([
                    'pickup_at' => ['Kies een nieuw ophaalmoment in de toekomst.'],
                ]);
            }

            // Nieuw ophaalmoment gaat via rit_ophaal_voorstel (klant moet bevestigen),
            // niet direct als pickup_at — conflictcheck gebruikt wel dit voorstelmoment.
            $proposePickupAt = null;
            if ($pickupAt !== null && trim($pickupAt) !== '') {
                $instant = Carbon::parse($pickupAt);
                if ($instant->lte($now)) {
                    throw ValidationException::withMessages([
                        'pickup_at' => ['Kies een ophaalmoment in de toekomst.'],
                    ]);
                }
                $proposePickupAt = trim($pickupAt);
            }

            $effectivePickup = $proposePickupAt
                ? Carbon::parse($proposePickupAt)
                : ($ride->pickup_at ? $ride->pickup_at->copy() : $now->copy());

            $this->assertNoScheduleConflict(
                $conn,
                (int) $driver->id,
                $effectivePickup,
                $ride->duration_seconds !== null ? (int) $ride->duration_seconds : null,
                (int) $ride->id
            );

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

            $rideUpdates = [
                'driver_id' => $driver->id,
                'status' => RideRequest::STATUS_ACCEPTED,
                'company_id' => $ride->company_id ?: $offer->company_id,
            ];

            $ride->update($rideUpdates);

            $freshRide = $ride->fresh();
            $freshOffer = $offer->fresh();

            return [
                'ride' => $freshRide,
                'offer' => $freshOffer,
                'propose_pickup_at' => $proposePickupAt,
                'force_customer_notify' => $requiresNewPickup && $proposePickupAt === null,
            ];
        });

        $pickupProposed = false;
        if (! empty($result['propose_pickup_at']) && ! empty($result['ride'])) {
            TaxiDispatchSchema::ensurePickupProposalColumns($conn);
            $result['ride'] = app(TaxiPickupProposalService::class)->proposeNewPickup(
                $conn,
                $driver,
                (int) $result['ride']->id,
                (string) $result['propose_pickup_at']
            );
            $pickupProposed = true;
        } elseif (! empty($result['ride'])) {
            app(TaxiCustomerRideAcceptedNotificationService::class)
                ->notifyAfterRideAssigned(
                    $conn,
                    $result['ride'],
                    $driver,
                    ['force' => ! empty($result['force_customer_notify'])]
                );
        }

        $result['pickup_proposed'] = $pickupProposed;
        $result['pickup_changed'] = $pickupProposed;

        return $result;
    }

    public function startRide(string $conn, User $driver, int $rideId): RideRequest
    {
        $ride = DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId) {
            if ($this->driverHasBlockingAssignedRide($conn, (int) $driver->id, $rideId)) {
                throw ValidationException::withMessages([
                    'ride' => ['Je hebt al een lopende rit. Rond die eerst af.'],
                ]);
            }

            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            $vehicleId = DriverAvailability::vehicleIdForDriver($conn, (int) $driver->id);
            if (! $ride->isVisibleToDriver((int) $driver->id, $vehicleId)) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($ride->status !== RideRequest::STATUS_ACCEPTED) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze rit kan niet worden gestart.'],
                ]);
            }

            if (! $ride->isContractRide()) {
                TaxiDispatchSchema::ensurePickupProposalColumns($conn);
                $ride->refresh();
                $dispatchSettings = app(TaxiDispatchSettingsService::class);
                $isOverdue = $dispatchSettings->scheduledRideIsOverdue(
                    $ride,
                    (int) ($ride->company_id ?? 0) > 0 ? (int) $ride->company_id : null
                );
                if ($isOverdue && $ride->pickup_proposal_status !== RideRequest::PICKUP_PROPOSAL_ACCEPTED) {
                    throw ValidationException::withMessages([
                        'ride' => ['Het ophaalmoment is verlopen. Stel eerst een nieuw tijdstip voor aan de klant en wacht op acceptatie.'],
                    ]);
                }
            }

            if ($ride->isContractRide()) {
                $this->assertContractRideCanStartToday($conn, $ride);
            }

            $updates = ['status' => RideRequest::STATUS_ASSIGNED];
            if (! $ride->driver_id) {
                $updates['driver_id'] = $driver->id;
            }
            if ($ride->isReturnTrip() && $ride->hasOutboundCompleted() && ! $ride->hasReturnLegStarted()) {
                $updates['return_started_at'] = now();
            }

            $ride->update($updates);

            return $ride->fresh();
        });

        $this->notifyCustomerStatus($conn, $ride, WhatsAppBookingMessageComposer::EVENT_STARTED, $driver);

        return $ride;
    }

    private function assertContractRideCanStartToday(string $conn, RideRequest $ride): void
    {
        $occurrenceDate = TransportOccurrence::on($conn)
            ->where('ride_request_id', $ride->id)
            ->value('scheduled_date');

        $scheduledDate = $occurrenceDate
            ? Carbon::parse($occurrenceDate)->toDateString()
            : ($ride->pickup_at
                ? $ride->pickup_at->copy()->timezone(ContractTransportTimezone::TIMEZONE)->toDateString()
                : null);

        $today = now(ContractTransportTimezone::TIMEZONE)->toDateString();

        if (! $scheduledDate || $scheduledDate !== $today) {
            throw ValidationException::withMessages([
                'ride' => ['Contractritten kun je alleen starten op de dag van de rit.'],
            ]);
        }
    }

    public function releaseAcceptedRide(string $conn, User $driver, int $rideId): RideRequest
    {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);
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

            if ($ride->isContractRide()) {
                throw ValidationException::withMessages([
                    'ride' => ['Contractritten kunnen niet worden vrijgegeven. Neem contact op met de planner.'],
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

            $ride->update(array_merge([
                'driver_id' => null,
                'status' => RideRequest::STATUS_PENDING_DISPATCH,
            ], $this->clearedPickupProposalAttributes()));

            return $ride->fresh();
        });

        if ($companyId > 0) {
            $this->dispatch->startDispatch($conn, $released, $companyId, [(int) $driver->id]);
        }

        $this->notifyCustomerStatus(
            $conn,
            $released,
            WhatsAppBookingMessageComposer::EVENT_REDISPATCHED,
            null,
            ['extra_lines' => ['We zoeken een nieuwe chauffeur voor uw rit.']]
        );

        return $released->fresh() ?? $released;
    }

    public function releaseReturnLeg(string $conn, User $driver, int $rideId): RideRequest
    {
        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns($conn);

        $companyId = 0;

        $released = DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId, &$companyId) {
            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || ! $ride->canReleaseReturnLeg((int) $driver->id)) {
                throw ValidationException::withMessages([
                    'ride' => ['De retourrit kan nu niet worden vrijgegeven.'],
                ]);
            }

            if ($ride->isContractRide()) {
                throw ValidationException::withMessages([
                    'ride' => ['Contractritten kunnen niet worden vrijgegeven.'],
                ]);
            }

            $companyId = (int) ($ride->company_id ?? 0);
            $now = now();

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('driver_id', $driver->id)
                ->whereIn('status', [
                    RideDispatchOffer::STATUS_ACCEPTED,
                    RideDispatchOffer::STATUS_PENDING,
                ])
                ->update([
                    'status' => RideDispatchOffer::STATUS_DECLINED,
                    'responded_at' => $now,
                ]);

            $rideUpdates = [
                'driver_id' => null,
                'status' => RideRequest::STATUS_PENDING_DISPATCH,
            ];

            if (! $ride->outbound_driver_id) {
                $rideUpdates['outbound_driver_id'] = $driver->id;
            }

            $ride->update($rideUpdates);

            return $ride->fresh();
        });

        if ($companyId > 0) {
            $this->dispatch->startDispatch($conn, $released, $companyId, [(int) $driver->id]);
        }

        $this->notifyCustomerStatus(
            $conn,
            $released,
            WhatsAppBookingMessageComposer::EVENT_REDISPATCHED,
            null,
            ['extra_lines' => ['We zoeken een nieuwe chauffeur voor de retourrit.']]
        );

        return $released->fresh() ?? $released;
    }

    public function startReturnLeg(string $conn, User $driver, int $rideId): RideRequest
    {
        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns($conn);

        return DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId) {
            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if (! $ride->isReturnTrip() || ! $ride->hasOutboundCompleted()) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze rit heeft geen openstaande retour.'],
                ]);
            }

            if ($ride->hasReturnLegStarted()) {
                throw ValidationException::withMessages([
                    'ride' => ['De retourrit is al gestart.'],
                ]);
            }

            if ($ride->status !== RideRequest::STATUS_ASSIGNED) {
                throw ValidationException::withMessages([
                    'ride' => ['Start eerst de rit voordat je de retour begint.'],
                ]);
            }

            $ride->update(['return_started_at' => now()]);

            return $ride->fresh();
        });
    }

    public function declineOffer(string $conn, User $driver, int $offerId, ?string $declineReason = null): RideDispatchOffer
    {
        TaxiDispatchSchema::ensureOfferDeclineReasonColumn($conn);

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

        $reason = trim((string) $declineReason);
        if (mb_strlen($reason) > 500) {
            $reason = mb_substr($reason, 0, 500);
        }

        $offer->update([
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'responded_at' => now(),
            'decline_reason' => $reason !== '' ? $reason : null,
        ]);

        $freshOffer = $offer->fresh() ?? $offer;
        $ride = RideRequest::on($conn)->find($freshOffer->ride_request_id);
        if ($ride) {
            app(TaxiCustomerRideAcceptedNotificationService::class)
                ->notifyAfterOfferDeclined($conn, $ride, $driver, $reason !== '' ? $reason : null);
        }

        return $freshOffer;
    }

    public function completeRide(
        string $conn,
        User $driver,
        int $rideId,
        bool $allowOverdueContractComplete = false,
    ): RideRequest {
        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns($conn);

        $completedFully = false;

        $ride = DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId, $allowOverdueContractComplete, &$completedFully) {
            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($ride->status === RideRequest::STATUS_ACCEPTED) {
                if (! $allowOverdueContractComplete || ! $ride->isContractRide()) {
                    throw ValidationException::withMessages([
                        'ride' => ['Start de rit eerst voordat je deze afrondt.'],
                    ]);
                }

                $ride->update(['status' => RideRequest::STATUS_ASSIGNED]);
                $ride = $ride->fresh();
            } elseif ($ride->status !== RideRequest::STATUS_ASSIGNED) {
                throw ValidationException::withMessages([
                    'ride' => ['Start de rit eerst voordat je deze afrondt.'],
                ]);
            }

            if ($ride->isReturnTrip() && $ride->hasOutboundCompleted() && ! $ride->hasReturnLegStarted()) {
                throw ValidationException::withMessages([
                    'ride' => ['Start de retourrit of geef deze vrij voordat je afrondt.'],
                ]);
            }

            if (! $this->ridePayments->canCompleteRide($ride)) {
                throw ValidationException::withMessages([
                    'ride' => ['Rond eerst de betaling af voordat je de rit afrondt.'],
                ]);
            }

            if ($ride->isReturnTrip() && ! $ride->hasOutboundCompleted()) {
                $updates = [
                    'outbound_completed_at' => now(),
                    'outbound_driver_id' => $ride->outbound_driver_id ?: $driver->id,
                ];
                if ($ride->requiresPerLegDriverPayment()) {
                    $updates['payment_status'] = RideRequest::PAYMENT_STATUS_NOT_REQUIRED;
                    $updates['final_price'] = null;
                }
                $ride->update($updates);

                return $ride->fresh();
            }

            if ($allowOverdueContractComplete && $ride->isContractRide()) {
                $this->contractStops->resolvePendingStopsForForcedComplete($conn, $ride);
            } else {
                $this->contractStops->assertGroupRideCanComplete($ride);
            }

            $ride->update(['status' => RideRequest::STATUS_COMPLETED]);

            $this->contractStops->completeDestinationStops($conn, $ride);

            $completedFully = true;

            return $ride->fresh();
        });

        if ($completedFully) {
            $this->notifyCustomerStatus($conn, $ride, WhatsAppBookingMessageComposer::EVENT_COMPLETED, $driver);
        }

        return $ride;
    }

    private function notifyCustomerStatus(
        string $conn,
        ?RideRequest $ride,
        string $event,
        ?User $driver = null,
        array $extraContext = []
    ): void {
        if (! $ride) {
            return;
        }

        $context = $extraContext;
        if ($driver) {
            $driverName = trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''));
            if ($driverName !== '') {
                $context['driver_name'] = $driverName;
            }
            $driverPhone = trim((string) ($driver->phone ?? ''));
            if ($driverPhone !== '') {
                $context['driver_phone'] = $driverPhone;
            }
        }

        try {
            app(TaxiCustomerRideStatusNotificationService::class)->notify($conn, $ride, $event, $context);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Klant weigerde het nieuwe ophaalmoment: verberg de rit voor deze chauffeur
     * en zet hem terug in dispatch voor anderen.
     */
    public function archiveCustomerDeclinedPickupProposal(string $conn, User $driver, int $offerId): RideDispatchOffer
    {
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);
        $companyId = 0;

        $archived = DB::connection($conn)->transaction(function () use ($conn, $driver, $offerId, &$companyId) {
            $offer = RideDispatchOffer::on($conn)->whereKey($offerId)->lockForUpdate()->first();
            if (! $offer || (int) $offer->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'offer' => ['Aanbod niet gevonden.'],
                ]);
            }

            $ride = RideRequest::on($conn)->whereKey($offer->ride_request_id)->lockForUpdate()->first();
            if (
                ! $ride
                || (int) $ride->driver_id !== (int) $driver->id
                || $ride->status !== RideRequest::STATUS_ACCEPTED
                || $offer->status !== RideDispatchOffer::STATUS_ACCEPTED
                || $ride->pickup_proposal_status !== RideRequest::PICKUP_PROPOSAL_DECLINED
            ) {
                throw ValidationException::withMessages([
                    'offer' => ['Alleen door de klant afgewezen ophaalvoorstellen kunnen hier worden gearchiveerd.'],
                ]);
            }

            $companyId = (int) ($ride->company_id ?: $offer->company_id);
            $now = now();

            $offer->update([
                'status' => RideDispatchOffer::STATUS_DECLINED,
                'responded_at' => $now,
                'archived_at' => $now,
            ]);

            $ride->update(array_merge([
                'driver_id' => null,
                'status' => RideRequest::STATUS_PENDING_DISPATCH,
            ], $this->clearedPickupProposalAttributes()));

            return $offer->fresh(['rideRequest']) ?? $offer;
        });

        $released = $archived->rideRequest;
        if ($companyId > 0 && $released) {
            $this->dispatch->startDispatch($conn, $released, $companyId, [(int) $driver->id]);
        }

        return $archived;
    }

    /**
     * Chauffeur zet een rit weg die nog wacht op WhatsApp van de klant.
     * Het voorstel blijft open: bij een late reactie komt de rit terug als nieuwe aanvraag.
     */
    public function archivePendingPickupProposal(string $conn, User $driver, int $offerId): RideDispatchOffer
    {
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        return DB::connection($conn)->transaction(function () use ($conn, $driver, $offerId) {
            $offer = RideDispatchOffer::on($conn)->whereKey($offerId)->lockForUpdate()->first();
            if (! $offer || (int) $offer->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'offer' => ['Aanbod niet gevonden.'],
                ]);
            }

            $ride = RideRequest::on($conn)->whereKey($offer->ride_request_id)->lockForUpdate()->first();
            if (
                ! $ride
                || (int) $ride->driver_id !== (int) $driver->id
                || $ride->status !== RideRequest::STATUS_ACCEPTED
                || $offer->status !== RideDispatchOffer::STATUS_ACCEPTED
                || $ride->pickup_proposal_status !== RideRequest::PICKUP_PROPOSAL_PENDING
            ) {
                throw ValidationException::withMessages([
                    'offer' => ['Alleen ritten die wachten op de klant kunnen hier worden gearchiveerd.'],
                ]);
            }

            $now = now();
            $offer->update([
                'status' => RideDispatchOffer::STATUS_EXPIRED,
                'responded_at' => $now,
                'archived_at' => $now,
            ]);

            $ride->update([
                'driver_id' => null,
                'status' => RideRequest::STATUS_PENDING_DISPATCH,
            ]);

            return $offer->fresh(['rideRequest']) ?? $offer;
        });
    }

    /**
     * Late WhatsApp-reactie nadat de chauffeur het wachtende voorstel heeft gearchiveerd:
     * rit terug als nieuwe aanvraag (accepteren) of naar Afgewezen/Verlopen (weigeren).
     */
    public function reopenAfterArchivedPickupProposal(string $conn, RideRequest $ride, bool $customerAccepted): RideRequest
    {
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);
        $companyId = 0;
        $previousDriverId = 0;

        $fresh = DB::connection($conn)->transaction(function () use ($conn, $ride, $customerAccepted, &$companyId, &$previousDriverId) {
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first() ?? $ride;
            if ($locked->driver_id) {
                return $locked;
            }

            $previous = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $locked->id)
                ->whereNotNull('archived_at')
                ->orderByDesc('archived_at')
                ->lockForUpdate()
                ->first();

            $previousDriverId = (int) ($previous?->driver_id ?? 0);
            $companyId = (int) ($locked->company_id ?? $previous?->company_id ?? 0);
            $now = now();
            $ttl = (int) config('taxi-dispatch.offer_ttl_seconds', 300);

            $locked->update(array_merge([
                'status' => RideRequest::STATUS_PENDING_DISPATCH,
            ], $this->clearedPickupProposalAttributes()));

            if ($previous && $previousDriverId > 0) {
                if ($customerAccepted) {
                    $previous->update([
                        'status' => RideDispatchOffer::STATUS_PENDING,
                        'archived_at' => null,
                        'responded_at' => null,
                        'offered_at' => $now,
                        'expires_at' => $now->copy()->addSeconds(max(15, $ttl)),
                    ]);
                    $locked->update(['status' => RideRequest::STATUS_OFFERED]);
                } else {
                    $previous->update([
                        'status' => RideDispatchOffer::STATUS_DECLINED,
                        'archived_at' => null,
                        'responded_at' => $now,
                    ]);
                }
            }

            return $locked->fresh() ?? $locked;
        });

        if ($companyId > 0) {
            $exclude = (! $customerAccepted && $previousDriverId > 0) ? [$previousDriverId] : [];
            $this->dispatch->startDispatch($conn, $fresh, $companyId, $exclude);
        }

        if ($previousDriverId > 0) {
            app(TaxiDriverInboxPushService::class)->notifyDriver($previousDriverId, (int) $fresh->id);
            Cache::put(
                'taxi_driver_pickup_proposal_alert:'.$previousDriverId,
                [
                    'ride_id' => (int) $fresh->id,
                    'decision' => $customerAccepted ? 'reopened_offer' : 'declined',
                    'message' => $customerAccepted
                        ? 'Klant heeft het nieuwe ophaalmoment geaccepteerd. De rit staat weer bij Aanvragen.'
                        : 'Klant heeft het nieuwe ophaalmoment geweigerd. De rit staat bij Afgewezen.',
                    'proposal_status' => null,
                    'remark' => null,
                ],
                now()->addMinutes(30)
            );
        }

        return $fresh->fresh() ?? $fresh;
    }

    /**
     * @return array<string, null>
     */
    private function clearedPickupProposalAttributes(): array
    {
        return [
            'pickup_proposal_at' => null,
            'pickup_proposal_status' => null,
            'pickup_proposal_customer_remark' => null,
            'pickup_proposal_sent_at' => null,
            'pickup_proposal_responded_at' => null,
            'pickup_proposal_whatsapp_wamid' => null,
        ];
    }

    private function driverHasBlockingAssignedRide(string $conn, int $driverId, ?int $exceptRideId = null): bool
    {
        $query = RideRequest::on($conn)
            ->where('driver_id', $driverId)
            ->where('status', RideRequest::STATUS_ASSIGNED);

        if ($exceptRideId !== null) {
            $query->whereKeyNot($exceptRideId);
        }

        return $query->get()->contains(
            fn (RideRequest $ride) => $ride->blocksDriverFromOtherRides()
        );
    }

    /**
     * Blokkeer accepteren als het ophaalmoment overlapt met een al geplande/lopende rit.
     */
    private function assertNoScheduleConflict(
        string $conn,
        int $driverId,
        Carbon $newStart,
        ?int $newDurationSeconds,
        ?int $exceptRideId = null,
    ): void {
        $defaultDuration = 45 * 60;
        $bufferSeconds = 10 * 60;
        $newDuration = ($newDurationSeconds !== null && $newDurationSeconds > 0)
            ? $newDurationSeconds
            : $defaultDuration;
        $newEnd = $newStart->copy()->addSeconds($newDuration + $bufferSeconds);

        $query = RideRequest::on($conn)
            ->where('driver_id', $driverId)
            ->whereIn('status', [
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_ASSIGNED,
            ])
            ->whereNotNull('pickup_at');

        if ($exceptRideId !== null) {
            $query->whereKeyNot($exceptRideId);
        }

        foreach ($query->get() as $existing) {
            /** @var RideRequest $existing */
            $existingStart = $existing->pickup_at?->copy();
            if (! $existingStart) {
                continue;
            }
            $existingDuration = $existing->duration_seconds !== null && (int) $existing->duration_seconds > 0
                ? (int) $existing->duration_seconds
                : $defaultDuration;
            $existingEnd = $existingStart->copy()->addSeconds($existingDuration + $bufferSeconds);

            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                $label = $existingStart->timezone(config('app.timezone'))->format('d-m-Y H:i');
                throw ValidationException::withMessages([
                    'offer' => [
                        'Je hebt al een rit gepland rond dit tijdstip ('.$label.'). '.
                        'Accepteer geen overlapping — bekijk eerst je geplande ritten.',
                    ],
                ]);
            }
        }
    }
}
