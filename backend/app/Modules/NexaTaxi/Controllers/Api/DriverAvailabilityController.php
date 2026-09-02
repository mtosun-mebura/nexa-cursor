<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DriverAvailabilityController extends Controller
{
    public function update(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $data = $request->validate([
            'is_online' => 'required|boolean',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'vehicle_id' => 'nullable|integer',
        ]);

        return $this->persist($request, $moduleDb, $data, requireOnline: true);
    }

    public function updateLocation(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'vehicle_id' => 'nullable|integer',
        ]);

        return $this->persist($request, $moduleDb, $data, requireOnline: false);
    }

    public function vehicles(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $conn = $moduleDb->getModuleConnectionName('taxi');

        $vehicles = Vehicle::on($conn)
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'license_plate', 'type']);

        return response()->json([
            'data' => $vehicles->map(fn (Vehicle $vehicle) => [
                'id' => (int) $vehicle->id,
                'name' => (string) ($vehicle->name ?? ''),
                'license_plate' => trim((string) ($vehicle->license_plate ?? '')) ?: null,
                'type' => (string) ($vehicle->type ?? ''),
            ])->values(),
        ]);
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
        if ($hasCoords) {
            $payload['lat'] = $data['lat'];
            $payload['lng'] = $data['lng'];
            $payload['location_updated_at'] = $now;
        }

        $hasVehicleColumn = Schema::connection($conn)->hasColumn('driver_availability', 'vehicle_id');
        if ($hasVehicleColumn && array_key_exists('vehicle_id', $data)) {
            $vehicleId = $data['vehicle_id'] !== null ? (int) $data['vehicle_id'] : null;
            if ($vehicleId) {
                $exists = Vehicle::on($conn)
                    ->where('company_id', $companyId)
                    ->whereKey($vehicleId)
                    ->exists();
                $payload['vehicle_id'] = $exists ? $vehicleId : null;
            } else {
                $payload['vehicle_id'] = null;
            }
        }

        $row = DriverAvailability::on($conn)->updateOrCreate(
            ['driver_id' => $user->id],
            $payload
        );

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
