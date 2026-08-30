<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\ModuleDatabaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DriverPlanningController extends Controller
{
    private const WEEKS_BACK = 4;

    private const WEEKS_AHEAD = 8;

    public function week(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $user = $request->user();
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $tz = ContractTransportTimezone::TIMEZONE;
        $today = Carbon::now($tz)->startOfDay();

        $fromInput = $request->query('from');
        try {
            $from = $fromInput
                ? Carbon::parse((string) $fromInput, $tz)->startOfWeek(Carbon::MONDAY)
                : $today->copy()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            $from = $today->copy()->startOfWeek(Carbon::MONDAY);
        }

        $earliest = $today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(self::WEEKS_BACK);
        $latest = $today->copy()->startOfWeek(Carbon::MONDAY)->addWeeks(self::WEEKS_AHEAD);
        if ($from->lt($earliest)) {
            $from = $earliest;
        }
        if ($from->gt($latest)) {
            $from = $latest;
        }

        $to = $from->copy()->endOfWeek(Carbon::SUNDAY);
        $days = $this->emptyDays($from, $to, $today);

        if (Schema::connection($conn)->hasTable('ride_requests')) {
            $rides = RideRequest::on($conn)
                ->where('driver_id', $user->id)
                ->whereIn('status', [
                    RideRequest::STATUS_ACCEPTED,
                    RideRequest::STATUS_ASSIGNED,
                    RideRequest::STATUS_COMPLETED,
                ])
                ->whereBetween('pickup_at', [
                    ContractTransportTimezone::naiveUtcForWallClockQuery($from->copy()->startOfDay()),
                    ContractTransportTimezone::naiveUtcForWallClockQuery($to->copy()->endOfDay()),
                ])
                ->orderBy('pickup_at')
                ->get();

            $grouped = $rides->groupBy(function (RideRequest $ride) {
                return ContractTransportTimezone::asAmsterdamWall($ride->pickup_at)?->toDateString() ?? '';
            });

            foreach ($days as $i => $day) {
                $dayRides = $grouped->get($day['date'], collect())
                    ->map(fn (RideRequest $ride) => TaxiDispatchOfferResource::planningRide($ride))
                    ->values()
                    ->all();
                $days[$i]['rides'] = $dayRides;
                $days[$i]['ride_count'] = count($dayRides);
            }
        }

        return response()->json([
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'today' => $today->toDateString(),
                'days' => $days,
            ],
        ]);
    }

    /**
     * @return list<array{date: string, is_today: bool, ride_count: int, rides: list<array<string, mixed>>}>
     */
    private function emptyDays(Carbon $from, Carbon $to, Carbon $today): array
    {
        $days = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $dateString = $d->toDateString();
            $days[] = [
                'date' => $dateString,
                'is_today' => $dateString === $today->toDateString(),
                'ride_count' => 0,
                'rides' => [],
            ];
        }

        return $days;
    }
}
