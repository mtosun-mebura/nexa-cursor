<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Start/afronden van contractritten via het contractportaal (contractant).
 */
class ContractPortalRideLifecycleService
{
    public function __construct(
        private TaxiContractPortalAccessService $access,
        private ContractRideStopService $contractStops,
        private RideTrackService $rideTrack,
    ) {}

    public function startFromStop(string $conn, User $user, array $context, int $rideStopId): RideRequest
    {
        $this->assertContractant($context);

        $stop = $this->resolvePickupStop($conn, $context, $rideStopId);
        $ride = $stop->ride;
        if (! $ride || ! $ride->isContractRide()) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        if ($ride->status === RideRequest::STATUS_ASSIGNED) {
            return $ride;
        }

        $startable = [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_OFFERED,
            RideRequest::STATUS_PENDING_DISPATCH,
        ];

        if (! in_array($ride->status, $startable, true)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit kan niet worden gestart.'],
            ]);
        }

        $ride = DB::connection($conn)->transaction(function () use ($conn, $user, $ride, $startable) {
            /** @var RideRequest|null $locked */
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first();
            if (! $locked) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($locked->status === RideRequest::STATUS_ASSIGNED) {
                return $locked;
            }

            if (! in_array($locked->status, $startable, true)) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze rit kan niet worden gestart.'],
                ]);
            }

            $locked->update([
                'driver_id' => $user->id,
                'status' => RideRequest::STATUS_ASSIGNED,
            ]);

            return $locked->fresh() ?? $locked;
        });

        try {
            $this->rideTrack->markTripStarted($conn, $ride);
            $ride = $ride->fresh() ?? $ride;
        } catch (\Throwable $e) {
            report($e);
        }

        return $ride;
    }

    /**
     * Markeer passagier als opgehaald (in de bus), zonder de hele rit af te ronden.
     */
    public function boardFromStop(string $conn, User $user, array $context, int $rideStopId): RideStop
    {
        $this->assertContractant($context);

        $stop = $this->resolvePickupStop($conn, $context, $rideStopId);
        $ride = $stop->ride;
        if (! $ride || ! $ride->isContractRide()) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        if (! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
            throw ValidationException::withMessages([
                'stop' => ['Deze passagier is al afgehandeld.'],
            ]);
        }

        // Rit moet onderweg zijn of mogen starten — boarden na start of vanaf accepted.
        if ($ride->status === RideRequest::STATUS_COMPLETED || $ride->status === RideRequest::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit is al afgerond.'],
            ]);
        }

        if (in_array($ride->status, [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_OFFERED,
            RideRequest::STATUS_PENDING_DISPATCH,
        ], true)) {
            $this->startFromStop($conn, $user, $context, $rideStopId);
            $stop = $stop->fresh() ?? $stop;
        }

        $stop->update([
            'status' => RideStop::STATUS_PICKED_UP,
            'completed_at' => now(),
        ]);

        return $stop->fresh() ?? $stop;
    }

    /**
     * Markeer passagier als niet meegenomen (overslaan), zonder de hele rit af te ronden.
     */
    public function skipFromStop(string $conn, User $user, array $context, int $rideStopId): RideStop
    {
        $this->assertContractant($context);

        $stop = $this->resolvePickupStop($conn, $context, $rideStopId);
        $ride = $stop->ride;
        if (! $ride || ! $ride->isContractRide()) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        if (! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
            throw ValidationException::withMessages([
                'stop' => ['Deze passagier is al afgehandeld.'],
            ]);
        }

        $stop->update([
            'status' => RideStop::STATUS_SKIPPED,
            'completed_at' => now(),
        ]);

        return $stop->fresh() ?? $stop;
    }

    public function completeFromStop(string $conn, User $user, array $context, int $rideStopId): RideRequest
    {
        $this->assertContractant($context);

        $stop = $this->resolvePickupStop($conn, $context, $rideStopId);
        $ride = $stop->ride;
        if (! $ride || ! $ride->isContractRide()) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        if ($ride->status === RideRequest::STATUS_COMPLETED) {
            return $ride;
        }

        $completable = [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
            RideRequest::STATUS_OFFERED,
            RideRequest::STATUS_PENDING_DISPATCH,
        ];

        if (! in_array($ride->status, $completable, true)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit kan niet worden afgerond.'],
            ]);
        }

        $passengerId = (int) ($stop->transport_passenger_id ?? 0);

        return DB::connection($conn)->transaction(function () use ($conn, $user, $ride, $stop, $passengerId) {
            /** @var RideRequest|null $locked */
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first();
            if (! $locked) {
                throw ValidationException::withMessages([
                    'ride' => ['Rit niet gevonden.'],
                ]);
            }

            if ($locked->status === RideRequest::STATUS_COMPLETED) {
                return $locked;
            }

            if ($locked->status === RideRequest::STATUS_ASSIGNED) {
                if ((int) ($locked->driver_id ?? 0) !== (int) $user->id) {
                    $locked->update(['driver_id' => $user->id]);
                    $locked = $locked->fresh() ?? $locked;
                }
            } elseif (in_array($locked->status, [
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_OFFERED,
                RideRequest::STATUS_PENDING_DISPATCH,
            ], true)) {
                $locked->update([
                    'driver_id' => $user->id,
                    'status' => RideRequest::STATUS_ASSIGNED,
                ]);
                $locked = $locked->fresh() ?? $locked;
            } else {
                throw ValidationException::withMessages([
                    'ride' => ['Start de rit eerst voordat je deze afrondt.'],
                ]);
            }

            // Grace verstreken na bestemming? Dan alsnog auto-opgehaald (skipped blijft).
            app(ContractPortalAutoBoardService::class)->applyForRide($conn, $locked, false);

            $freshStop = RideStop::on($conn)->whereKey($stop->id)->lockForUpdate()->first();
            if ($freshStop && in_array($freshStop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
                throw ValidationException::withMessages([
                    'stop' => ['Bevestig eerst of deze passagier is opgehaald of niet meegenomen.'],
                ]);
            }

            if ($freshStop && $freshStop->status === RideStop::STATUS_SKIPPED) {
                // Niet meegenomen: geen bestemming afronden voor deze passagier.
                $progress = $this->contractStops->groupRideProgress($conn, (int) $locked->id);
                if ($locked->ride_type === RideRequest::RIDE_TYPE_CONTRACT_GROUP
                    && ! ($progress['all_pickups_done'] ?? false)) {
                    return $locked->fresh() ?? $locked;
                }
                if ($progress['all_pickups_done'] ?? true) {
                    $this->contractStops->completeDestinationStops($conn, $locked);
                    $locked->update(['status' => RideRequest::STATUS_COMPLETED]);
                    $locked = $locked->fresh() ?? $locked;
                    try {
                        $this->rideTrack->finalizeRide($conn, $locked, []);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                return $locked->fresh() ?? $locked;
            }

            RideStop::on($conn)
                ->where('ride_request_id', $locked->id)
                ->where('stop_type', RideStop::STOP_TYPE_DESTINATION)
                ->where(function ($q) use ($passengerId) {
                    if ($passengerId > 0) {
                        $q->where('transport_passenger_id', $passengerId)
                            ->orWhereNull('transport_passenger_id');
                    }
                })
                ->whereNotIn('status', [RideStop::STATUS_COMPLETED])
                ->update([
                    'status' => RideStop::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

            if ($locked->ride_type === RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
                $progress = $this->contractStops->groupRideProgress($conn, (int) $locked->id);
                if (! ($progress['all_pickups_done'] ?? false)) {
                    return $locked->fresh() ?? $locked;
                }
            }

            $this->contractStops->completeDestinationStops($conn, $locked);
            $locked->update(['status' => RideRequest::STATUS_COMPLETED]);
            $locked = $locked->fresh() ?? $locked;

            try {
                $this->rideTrack->finalizeRide($conn, $locked, []);
            } catch (\Throwable $e) {
                report($e);
            }

            return $locked->fresh() ?? $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function assertContractant(array $context): void
    {
        if (! $this->access->isContractant($context)) {
            throw ValidationException::withMessages([
                'ride' => ['Alleen de contractant kan ritten starten of afronden.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolvePickupStop(string $conn, array $context, int $rideStopId): RideStop
    {
        $stop = RideStop::on($conn)
            ->with('ride')
            ->whereKey($rideStopId)
            ->first();

        if (! $stop || $stop->stop_type !== RideStop::STOP_TYPE_PICKUP) {
            throw ValidationException::withMessages([
                'stop' => ['Stop niet gevonden.'],
            ]);
        }

        $passengerId = (int) ($stop->transport_passenger_id ?? 0);
        if ($passengerId <= 0 || ! $this->access->canAccessPassenger($conn, $context, $passengerId)) {
            throw ValidationException::withMessages([
                'stop' => ['Geen toegang tot deze rit.'],
            ]);
        }

        return $stop;
    }
}
