<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverAvailabilityController extends Controller
{
    public function update(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $data = $request->validate([
            'is_online' => 'required|boolean',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $user = $request->user();
        $companyId = (int) $request->attributes->get('taxi_company_id');
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $now = now();

        $row = DriverAvailability::on($conn)->updateOrCreate(
            ['driver_id' => $user->id],
            [
                'company_id' => $companyId,
                'is_online' => (bool) $data['is_online'],
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'location_updated_at' => isset($data['lat'], $data['lng']) ? $now : null,
                'last_seen_at' => $now,
            ]
        );

        return response()->json([
            'data' => [
                'is_online' => $row->is_online,
                'lat' => $row->lat,
                'lng' => $row->lng,
                'last_seen_at' => $row->last_seen_at?->toIso8601String(),
            ],
        ]);
    }
}
