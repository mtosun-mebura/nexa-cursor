<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Automatisch opgehaald markeren als de chauffeur op bestemming is
 * en (na grace) weer vertrekt. Overslaan (niet meegenomen) blijft handmatig.
 */
class ContractPortalAutoBoardService
{
    /** Minuten na afronden bestemming voordat openstaande pickups auto-opgehaald worden. */
    public const GRACE_MINUTES = 5;

    /**
     * @return int Aantal stops dat op picked_up is gezet
     */
    public function applyForRide(string $conn, RideRequest $ride, bool $force = false): int
    {
        if (! $ride->isContractRide()) {
            return 0;
        }

        // Force: chauffeur rondt af / vertrekt → direct openstaande pickups als opgehaald.
        if ($force) {
            return $this->boardOpenPickups($conn, (int) $ride->id);
        }

        $count = 0;
        // 1) Bestemming klaar + grace → alle openstaande pickups
        if ($this->destinationGraceElapsed($conn, (int) $ride->id)) {
            $count += $this->boardOpenPickups($conn, (int) $ride->id);
        }
        // 2) Chauffeur ter plaatse (arrived) langer dan grace → die pickup
        $count += $this->boardStaleArrivedPickups($conn, (int) $ride->id);

        return $count;
    }

    /**
     * Pas auto-board toe op alle contractritten met bestemming klaar (grace verstreken).
     *
     * @param  Collection<int, int>|list<int>  $rideIds
     * @return int Totaal geboarde stops
     */
    public function applyForRideIds(string $conn, Collection|array $rideIds): int
    {
        $ids = Collection::make($rideIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        $rides = RideRequest::on($conn)
            ->whereIn('id', $ids->all())
            ->whereIn('status', [
                RideRequest::STATUS_ASSIGNED,
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_COMPLETED,
            ])
            ->get();

        $total = 0;
        foreach ($rides as $ride) {
            $total += $this->applyForRide($conn, $ride, false);
        }

        return $total;
    }

    public function destinationGraceElapsed(string $conn, int $rideId): bool
    {
        $completedAt = $this->earliestDestinationCompletedAt($conn, $rideId);
        if (! $completedAt) {
            return false;
        }

        $cutoff = Carbon::now(ContractTransportTimezone::TIMEZONE)->subMinutes(self::GRACE_MINUTES);

        return $completedAt->lte($cutoff);
    }

    public function hasCompletedDestination(string $conn, int $rideId): bool
    {
        return $this->earliestDestinationCompletedAt($conn, $rideId) !== null;
    }

    private function earliestDestinationCompletedAt(string $conn, int $rideId): ?Carbon
    {
        $destinations = RideStop::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('stop_type', RideStop::STOP_TYPE_DESTINATION)
            ->where('status', RideStop::STATUS_COMPLETED)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->get(['completed_at']);

        if ($destinations->isEmpty()) {
            return null;
        }

        $raw = $destinations->first()?->completed_at;
        if (! $raw) {
            return null;
        }

        return Carbon::parse($raw)->timezone(ContractTransportTimezone::TIMEZONE);
    }

    private function boardOpenPickups(string $conn, int $rideId): int
    {
        return RideStop::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->whereIn('status', [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED])
            ->update([
                'status' => RideStop::STATUS_PICKED_UP,
                'completed_at' => now(),
            ]);
    }

    /**
     * Chauffeur stond langer dan grace bij ophaalpunt → alsnog opgehaald (tenzij skipped).
     */
    private function boardStaleArrivedPickups(string $conn, int $rideId): int
    {
        $cutoff = Carbon::now(ContractTransportTimezone::TIMEZONE)->subMinutes(self::GRACE_MINUTES);

        $stops = RideStop::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->where('status', RideStop::STATUS_ARRIVED)
            ->whereNotNull('updated_at')
            ->get();

        $ids = [];
        foreach ($stops as $stop) {
            $at = Carbon::parse($stop->updated_at)->timezone(ContractTransportTimezone::TIMEZONE);
            if ($at->lte($cutoff)) {
                $ids[] = (int) $stop->id;
            }
        }

        if ($ids === []) {
            return 0;
        }

        return RideStop::on($conn)
            ->whereIn('id', $ids)
            ->where('status', RideStop::STATUS_ARRIVED)
            ->update([
                'status' => RideStop::STATUS_PICKED_UP,
                'completed_at' => now(),
            ]);
    }
}
