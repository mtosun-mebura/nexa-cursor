<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\DriverScheduleService;
use App\Modules\NexaTaxi\Services\RideTrackService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DriverAvailabilityController extends Controller
{
    public function __construct(
        protected DriverScheduleService $schedules
    ) {}

    public function update(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $data = $request->validate([
            'is_online' => 'required|boolean',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'vehicle_id' => 'nullable|integer',
            'accuracy' => 'nullable|numeric|min:0|max:5000',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:80',
        ]);

        return $this->persist($request, $moduleDb, $data, requireOnline: true);
    }

    public function updateLocation(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'vehicle_id' => 'nullable|integer',
            'accuracy' => 'nullable|numeric|min:0|max:5000',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:80',
        ]);

        return $this->persist($request, $moduleDb, $data, requireOnline: false);
    }

    public function vehicles(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $driverId = (int) $request->user()->id;

        return response()->json($this->schedules->vehiclesPayloadForDriver($conn, $companyId, $driverId));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Request $request, ModuleDatabaseService $moduleDb, array $data, bool $requireOnline): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $conn = $moduleDb->getModuleConnectionName('taxi');
        TaxiDispatchSchema::ensureVehicleIdColumn($conn);
        $this->schedules->ensureReady($conn);
        $now = now();

        $payload = [
            'company_id' => $companyId,
            'last_seen_at' => $now,
        ];

        if ($requireOnline || array_key_exists('is_online', $data)) {
            $payload['is_online'] = (bool) ($data['is_online'] ?? false);
        }

        $hasCoords = isset($data['lat'], $data['lng'])
            && $data['lat'] !== null
            && $data['lng'] !== null;
        $existing = DriverAvailability::on($conn)->find($user->id);
        $accuracy = isset($data['accuracy']) && $data['accuracy'] !== null ? (float) $data['accuracy'] : null;
        if ($hasCoords && $accuracy !== null && $accuracy > 180.0 && $existing && $existing->lat !== null && $existing->lng !== null) {
            $hasCoords = false;
        }
        if ($hasCoords) {
            $payload['lat'] = $data['lat'];
            $payload['lng'] = $data['lng'];
            $payload['location_updated_at'] = $now;
        }

        if (isset($data['heading']) && $data['heading'] !== null) {
            $heading = fmod((float) $data['heading'] + 360.0, 360.0);
            Cache::put('taxi-gps-heading:'.$companyId.':'.(int) $user->id, $heading, now()->addMinutes(2));
        }

        $hasVehicleColumn = Schema::connection($conn)->hasColumn('driver_availability', 'vehicle_id');
        if ($hasVehicleColumn) {
            $lockedVehicleId = $this->schedules->resolveLockedVehicleId($conn, $companyId, (int) $user->id);
            $requestedVehicleId = array_key_exists('vehicle_id', $data) && $data['vehicle_id'] !== null
                ? (int) $data['vehicle_id']
                : null;

            if ($lockedVehicleId) {
                if ($requestedVehicleId && $requestedVehicleId !== $lockedVehicleId) {
                    return response()->json([
                        'message' => 'Je bent ingepland op een ander voertuig. Je kunt nu geen andere auto kiezen.',
                        'code' => 'vehicle_locked',
                    ], 422);
                }
                $payload['vehicle_id'] = $lockedVehicleId;
            } elseif (array_key_exists('vehicle_id', $data)) {
                $vehicleId = $requestedVehicleId;
                if ($vehicleId) {
                    $exists = Vehicle::on($conn)
                        ->where('company_id', $companyId)
                        ->whereKey($vehicleId)
                        ->exists();
                    if (! $exists) {
                        $payload['vehicle_id'] = null;
                    } elseif ($this->schedules->isVehicleOccupiedByOther($conn, $companyId, (int) $user->id, $vehicleId)) {
                        return response()->json([
                            'message' => 'Dit voertuig is al in gebruik door een andere chauffeur.',
                            'code' => 'vehicle_occupied',
                        ], 422);
                    } else {
                        $payload['vehicle_id'] = $vehicleId;
                    }
                } else {
                    $payload['vehicle_id'] = null;
                }
            }
        }

        $row = DriverAvailability::on($conn)->updateOrCreate(
            ['driver_id' => $user->id],
            $payload
        );

        if ($hasCoords) {
            try {
                app(RideTrackService::class)->appendPoint(
                    $conn,
                    $companyId,
                    (int) $user->id,
                    (float) $data['lat'],
                    (float) $data['lng']
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'data' => [
                'is_online' => (bool) $row->is_online,
                'vehicle_id' => $hasVehicleColumn ? ($row->vehicle_id ? (int) $row->vehicle_id : null) : null,
                'lat' => $row->lat,
                'lng' => $row->lng,
                'location_updated_at' => $row->location_updated_at?->toIso8601String(),
                'last_seen_at' => $row->last_seen_at?->toIso8601String(),
            ],
        ]);
    }
}
