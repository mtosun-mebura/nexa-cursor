<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\DriverSchedule;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiDriverProfileMediaService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DriverVehicleController extends Controller
{
    public function index(Request $request, ModuleDatabaseService $moduleDb, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $companyId = (int) $request->attributes->get('taxi_company_id');

        $vehicles = Vehicle::on($conn)
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $vehicles->map(fn (Vehicle $vehicle) => $this->payload($vehicle, $media))->values(),
        ]);
    }

    public function store(Request $request, ModuleDatabaseService $moduleDb, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $validated = $this->validateVehicle($request);

        $vehicle = Vehicle::on($conn)->create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'license_plate' => $validated['license_plate'],
            'person_range' => $validated['person_range'],
            'seats' => $this->seatsForRange($validated['person_range']),
            'active' => true,
            'price_per_km' => 0,
            'price_per_min' => 0,
            'min_fare' => 0,
        ]);

        return response()->json([
            'message' => 'Voertuig toegevoegd.',
            'data' => $this->payload($vehicle, $media),
        ], 201);
    }

    public function update(Request $request, int $vehicle, ModuleDatabaseService $moduleDb, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $row = $this->findVehicle($conn, $companyId, $vehicle);
        $validated = $this->validateVehicle($request);

        $row->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'license_plate' => $validated['license_plate'],
            'person_range' => $validated['person_range'],
            'seats' => $this->seatsForRange($validated['person_range']),
        ]);

        return response()->json([
            'message' => 'Voertuig bijgewerkt.',
            'data' => $this->payload($row->fresh() ?? $row, $media),
        ]);
    }

    public function destroy(Request $request, int $vehicle, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $row = $this->findVehicle($conn, $companyId, $vehicle);

        if (RideRequest::on($conn)->where('vehicle_id', $row->id)->exists()) {
            return response()->json([
                'message' => 'Dit voertuig heeft ritten en kan niet worden verwijderd.',
            ], 422);
        }

        if (Schema::connection($conn)->hasTable('driver_schedules')
            && DriverSchedule::on($conn)->where('vehicle_id', $row->id)->exists()) {
            return response()->json([
                'message' => 'Dit voertuig staat in een planning en kan niet worden verwijderd.',
            ], 422);
        }

        if (Schema::connection($conn)->hasTable('driver_availability')
            && Schema::connection($conn)->hasColumn('driver_availability', 'vehicle_id')) {
            DriverAvailability::on($conn)->where('vehicle_id', $row->id)->update(['vehicle_id' => null]);
        }

        $row->delete();

        return response()->json(['message' => 'Voertuig verwijderd.']);
    }

    /**
     * @return array{name: string, type: string, license_plate: string, person_range: string}
     */
    private function validateVehicle(Request $request): array
    {
        $request->merge([
            'license_plate' => strtoupper(trim((string) $request->input('license_plate', ''))),
            'name' => trim((string) $request->input('name', '')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Vehicle::typeLabels()))],
            'license_plate' => ['required', 'string', 'max:20'],
            'person_range' => ['required', Rule::in(array_keys(Vehicle::personRangeLabels()))],
        ], [
            'name.required' => 'Vul een naam in.',
            'type.required' => 'Kies een type.',
            'license_plate.required' => 'Vul een kenteken in.',
            'person_range.required' => 'Kies een personenbereik.',
        ]);

        return [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'license_plate' => $validated['license_plate'],
            'person_range' => $validated['person_range'],
        ];
    }

    private function findVehicle(string $conn, int $companyId, int $vehicleId): Vehicle
    {
        $vehicle = Vehicle::on($conn)
            ->where('company_id', $companyId)
            ->whereKey($vehicleId)
            ->first();

        if (! $vehicle) {
            abort(404, 'Voertuig niet gevonden.');
        }

        return $vehicle;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Vehicle $vehicle, TaxiDriverProfileMediaService $media): array
    {
        $type = (string) ($vehicle->type ?? Vehicle::TYPE_CAR);
        $range = (string) ($vehicle->person_range ?? Vehicle::PERSON_RANGE_1_4);
        $path = is_string($vehicle->driver_photo_path ?? null) ? $vehicle->driver_photo_path : null;

        return [
            'id' => (int) $vehicle->id,
            'name' => (string) ($vehicle->name ?? ''),
            'license_plate' => trim((string) ($vehicle->license_plate ?? '')) ?: null,
            'type' => $type,
            'type_label' => Vehicle::typeLabels()[$type] ?? $type,
            'person_range' => $range,
            'person_range_label' => Vehicle::personRangeLabels()[$range] ?? $range,
            'label' => $vehicle->fleetLabel(),
            'photo_url' => $media->publicUrlFor($path),
        ];
    }

    private function seatsForRange(string $range): int
    {
        [, $max] = \App\Modules\NexaTaxi\Models\DefaultRate::parseRangeBounds($range);

        return max(1, $max);
    }
}
