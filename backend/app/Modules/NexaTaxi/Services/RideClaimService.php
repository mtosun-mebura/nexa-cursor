<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Illuminate\Support\Carbon;
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
            ], true)) {
                throw ValidationException::withMessages([
                    'offer' => ['Dit aanbod is verlopen of niet meer geldig.'],
                ]);
            }

            if ($this->driverHasBlockingAssignedRide($conn, (int) $driver->id)) {
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

            $rideUpdates = [
                'driver_id' => $driver->id,
                'status' => RideRequest::STATUS_ACCEPTED,
                'company_id' => $ride->company_id ?: $offer->company_id,
            ];

            if ($pickupAt !== null && trim($pickupAt) !== '') {
                $newPickupAt = Carbon::parse($pickupAt);
                if ($newPickupAt->lte(now())) {
                    throw ValidationException::withMessages([
                        'pickup_at' => ['Kies een ophaalmoment in de toekomst.'],
                    ]);
                }
                $rideUpdates['pickup_at'] = $newPickupAt;
            }

            $ride->update($rideUpdates);

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
            if ($this->driverHasBlockingAssignedRide($conn, (int) $driver->id, $rideId)) {
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

            if ($ride->isContractRide()) {
                $this->assertContractRideCanStartToday($conn, $ride);
            }

            $updates = ['status' => RideRequest::STATUS_ASSIGNED];
            if ($ride->isReturnTrip() && $ride->hasOutboundCompleted() && ! $ride->hasReturnLegStarted()) {
                $updates['return_started_at'] = now();
            }

            $ride->update($updates);

            return $ride->fresh();
        });
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

    public function completeRide(
        string $conn,
        User $driver,
        int $rideId,
        bool $allowOverdueContractComplete = false,
    ): RideRequest {
        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns($conn);

        return DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId, $allowOverdueContractComplete) {
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

            return $ride->fresh();
        });
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
}
