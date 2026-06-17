<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContractRideStopService
{
    public const STOP_TYPE_PICKUP = 'pickup';

    public const STOP_TYPE_DESTINATION = 'destination';

    public function __construct(
        private readonly ContractOccurrenceGeneratorService $occurrenceGenerator,
    ) {}

    public function listStopsForRide(string $conn, User $driver, int $rideId): Collection
    {
        $ride = $this->resolveDriverRide($conn, $driver, $rideId);

        return RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->orderBy('sequence')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function stopPayload(RideStop $stop): array
    {
        $conn = $stop->getConnectionName();
        $plannedAt = $this->occurrenceGenerator->plannedAtForRideStop($conn, $stop);

        return [
            'id' => $stop->id,
            'sequence' => (int) $stop->sequence,
            'stop_type' => $stop->stop_type,
            'passenger_name' => $stop->passenger_name,
            'address' => $stop->address,
            'lat' => $stop->lat !== null ? (float) $stop->lat : null,
            'lng' => $stop->lng !== null ? (float) $stop->lng : null,
            'planned_at' => ContractTransportTimezone::toDriverIso8601($plannedAt),
            'status' => $stop->status,
            'completed_at' => $stop->completed_at?->toIso8601String(),
            'actions' => [
                'arrive' => url("/api/taxi/v1/driver/dispatch/rides/{$stop->ride_request_id}/stops/{$stop->id}/arrive"),
                'pickup' => url("/api/taxi/v1/driver/dispatch/rides/{$stop->ride_request_id}/stops/{$stop->id}/pickup"),
                'skip' => url("/api/taxi/v1/driver/dispatch/rides/{$stop->ride_request_id}/stops/{$stop->id}/skip"),
            ],
        ];
    }

    public function markArrived(string $conn, User $driver, int $rideId, int $stopId): RideStop
    {
        $stop = $this->resolveMutableStop($conn, $driver, $rideId, $stopId);

        if (! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
            throw ValidationException::withMessages([
                'stop' => ['Deze stop kan niet meer als aangekomen worden gemeld.'],
            ]);
        }

        $stop->update([
            'status' => RideStop::STATUS_ARRIVED,
        ]);

        return $stop->fresh();
    }

    public function markPickup(string $conn, User $driver, int $rideId, int $stopId): RideStop
    {
        $stop = $this->resolveMutableStop($conn, $driver, $rideId, $stopId);

        if ($stop->stop_type === self::STOP_TYPE_DESTINATION) {
            $stop->update([
                'status' => RideStop::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return $stop->fresh();
        }

        if (! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
            throw ValidationException::withMessages([
                'stop' => ['Deze stop kan niet meer als opgehaald worden gemeld.'],
            ]);
        }

        $stop->update([
            'status' => RideStop::STATUS_PICKED_UP,
            'completed_at' => now(),
        ]);

        return $stop->fresh();
    }

    public function markSkip(string $conn, User $driver, int $rideId, int $stopId): RideStop
    {
        $stop = $this->resolveMutableStop($conn, $driver, $rideId, $stopId);

        if ($stop->stop_type !== self::STOP_TYPE_PICKUP) {
            throw ValidationException::withMessages([
                'stop' => ['Alleen ophaalstops kunnen worden overgeslagen.'],
            ]);
        }

        if (! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
            throw ValidationException::withMessages([
                'stop' => ['Deze stop kan niet meer worden overgeslagen.'],
            ]);
        }

        $stop->update([
            'status' => RideStop::STATUS_SKIPPED,
            'completed_at' => now(),
        ]);

        return $stop->fresh();
    }

    public function assertGroupRideCanComplete(RideRequest $ride): void
    {
        if ($ride->ride_type !== RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
            return;
        }

        $conn = $ride->getConnectionName();
        $stops = RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->orderBy('sequence')
            ->get();

        if ($stops->isEmpty()) {
            return;
        }

        $pendingPickups = $stops
            ->where('stop_type', self::STOP_TYPE_PICKUP)
            ->whereNotIn('status', [RideStop::STATUS_PICKED_UP, RideStop::STATUS_SKIPPED]);

        if ($pendingPickups->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ride' => ['Verwerk eerst alle ophaalstops (opgehaald of afwezig) voordat je de rit afrondt.'],
            ]);
        }
    }

    public function completeDestinationStops(string $conn, RideRequest $ride): void
    {
        RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->where('stop_type', self::STOP_TYPE_DESTINATION)
            ->whereNotIn('status', [RideStop::STATUS_COMPLETED])
            ->update([
                'status' => RideStop::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
    }

    public function resolvePendingStopsForForcedComplete(string $conn, RideRequest $ride): void
    {
        if ($ride->ride_type !== RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
            return;
        }

        RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->where('stop_type', self::STOP_TYPE_PICKUP)
            ->whereIn('status', [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED])
            ->update([
                'status' => RideStop::STATUS_SKIPPED,
                'completed_at' => now(),
            ]);

        $this->completeDestinationStops($conn, $ride);
    }

    public function groupRideProgress(string $conn, int $rideId): array
    {
        $stops = RideStop::on($conn)
            ->where('ride_request_id', $rideId)
            ->orderBy('sequence')
            ->get();

        $pickups = $stops->where('stop_type', self::STOP_TYPE_PICKUP);
        $donePickups = $pickups->whereIn('status', [RideStop::STATUS_PICKED_UP, RideStop::STATUS_SKIPPED]);

        return [
            'stops_total' => $stops->count(),
            'pickups_total' => $pickups->count(),
            'pickups_done' => $donePickups->count(),
            'all_pickups_done' => $pickups->count() === 0 || $donePickups->count() === $pickups->count(),
        ];
    }

    private function resolveDriverRide(string $conn, User $driver, int $rideId): RideRequest
    {
        $ride = RideRequest::on($conn)->find($rideId);
        if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        if (! in_array($ride->status, [RideRequest::STATUS_ACCEPTED, RideRequest::STATUS_ASSIGNED], true)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit is niet actief voor stopbeheer.'],
            ]);
        }

        return $ride;
    }

    private function resolveMutableStop(string $conn, User $driver, int $rideId, int $stopId): RideStop
    {
        $ride = $this->resolveDriverRide($conn, $driver, $rideId);

        if ($ride->status !== RideRequest::STATUS_ASSIGNED) {
            throw ValidationException::withMessages([
                'ride' => ['Start de rit eerst voordat je stops afhandelt.'],
            ]);
        }

        $stop = RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->whereKey($stopId)
            ->first();

        if (! $stop) {
            throw ValidationException::withMessages([
                'stop' => ['Stop niet gevonden.'],
            ]);
        }

        return $stop;
    }
}
