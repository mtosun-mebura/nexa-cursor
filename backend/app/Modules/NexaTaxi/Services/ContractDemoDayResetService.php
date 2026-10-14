<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideGpsPoint;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Zet de contractritten van vandaag terug, zodat een demo opnieuw getest kan worden.
 */
class ContractDemoDayResetService
{
    public function __construct(
        private ContractDestinationArrivalService $arrival,
    ) {}

    /**
     * @param  list<int>  $passengerIds
     */
    public function resetToday(string $conn, array $passengerIds, int $userId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $passengerIds))));
        $this->arrival->forgetProgress($conn, $userId);
        if ($ids === []) {
            return 0;
        }

        $wallStart = Carbon::now(ContractTransportTimezone::TIMEZONE)->startOfDay();
        $start = ContractTransportTimezone::naiveUtcForWallClockQuery($wallStart);
        $end = ContractTransportTimezone::naiveUtcForWallClockQuery($wallStart->copy()->endOfDay());

        $pickupStops = RideStop::on($conn)
            ->whereIn('transport_passenger_id', $ids)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->whereBetween('planned_at', [$start, $end])
            ->whereHas('ride', function ($query) {
                $query->whereNotIn('status', [RideRequest::STATUS_CANCELLED])
                    ->whereIn('ride_type', [
                        RideRequest::RIDE_TYPE_CONTRACT_GROUP,
                        RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
                    ]);
            })
            ->get(['id', 'ride_request_id']);

        $rideIds = $pickupStops
            ->pluck('ride_request_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($rideIds === []) {
            return 0;
        }

        $pickupIds = $pickupStops->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($pickupIds !== []) {
            RideStop::on($conn)
                ->whereIn('id', $pickupIds)
                ->where('status', '!=', RideStop::STATUS_PLANNED)
                ->update([
                    'status' => RideStop::STATUS_PLANNED,
                    'completed_at' => null,
                ]);
        }

        RideStop::on($conn)
            ->whereIn('ride_request_id', $rideIds)
            ->whereIn('stop_type', [RideStop::STOP_TYPE_DESTINATION, RideStop::STOP_TYPE_DROPOFF])
            ->where('status', '!=', RideStop::STATUS_PLANNED)
            ->update([
                'status' => RideStop::STATUS_PLANNED,
                'completed_at' => null,
            ]);

        RideRequest::on($conn)
            ->whereIn('id', $rideIds)
            ->whereIn('status', [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_COMPLETED])
            ->update(['status' => RideRequest::STATUS_ACCEPTED]);

        $clear = [];
        $schema = Schema::connection($conn);
        foreach ([
            'trip_started_at',
            'trip_completed_at',
            'track_polyline',
            'actual_distance_meters',
            'actual_duration_seconds',
        ] as $column) {
            if ($schema->hasColumn('ride_requests', $column)) {
                $clear[$column] = null;
            }
        }
        if ($clear !== []) {
            RideRequest::on($conn)->whereIn('id', $rideIds)->update($clear);
        }

        if ($schema->hasTable('ride_gps_points')) {
            RideGpsPoint::on($conn)->whereIn('ride_request_id', $rideIds)->delete();
        }

        return count($rideIds);
    }
}
