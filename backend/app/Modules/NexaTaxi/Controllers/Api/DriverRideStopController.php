<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\ContractRideStopService;
use App\Modules\NexaTaxi\Services\RideClaimService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverRideStopController extends Controller
{
    public function index(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        ContractRideStopService $stops,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $items = $stops->listStopsForRide($conn, $request->user(), $ride);

        return response()->json([
            'data' => [
                'stops' => $items->map(fn ($stop) => $stops->stopPayload($stop))->values(),
                'progress' => $stops->groupRideProgress($conn, $ride),
            ],
        ]);
    }

    public function arrive(
        Request $request,
        int $ride,
        int $stop,
        ModuleDatabaseService $moduleDb,
        ContractRideStopService $stops,
    ): JsonResponse {
        return $this->mutateStop($request, $ride, $stop, $moduleDb, $stops, 'markArrived', 'Aangekomen gemeld.');
    }

    public function pickup(
        Request $request,
        int $ride,
        int $stop,
        ModuleDatabaseService $moduleDb,
        ContractRideStopService $stops,
        RideClaimService $rides,
    ): JsonResponse {
        return $this->mutateStop($request, $ride, $stop, $moduleDb, $stops, 'markPickup', 'Stop afgehandeld.', function (
            string $conn,
            $driver,
            int $rideId,
            $updated,
        ) use ($rides) {
            if ($updated->stop_type !== ContractRideStopService::STOP_TYPE_DESTINATION) {
                return ['ride_completed' => false];
            }

            $rides->completeRide($conn, $driver, $rideId);

            return ['ride_completed' => true];
        });
    }

    public function skip(
        Request $request,
        int $ride,
        int $stop,
        ModuleDatabaseService $moduleDb,
        ContractRideStopService $stops,
    ): JsonResponse {
        return $this->mutateStop($request, $ride, $stop, $moduleDb, $stops, 'markSkip', 'Passagier gemarkeerd als afwezig.');
    }

    private function mutateStop(
        Request $request,
        int $rideId,
        int $stopId,
        ModuleDatabaseService $moduleDb,
        ContractRideStopService $stops,
        string $method,
        string $message,
        ?callable $after = null,
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $updated = $stops->{$method}($conn, $request->user(), $rideId, $stopId);
            $extra = $after
                ? $after($conn, $request->user(), $rideId, $updated)
                : [];
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Actie mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => $message,
            'data' => array_merge([
                'stop' => $stops->stopPayload($updated),
                'progress' => $stops->groupRideProgress($conn, $rideId),
            ], $extra),
        ]);
    }
}
