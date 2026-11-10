<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Services\TaxiDriverProfileMediaService;
use App\Modules\NexaTaxi\Services\TaxiRideRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverProfileMediaController extends Controller
{
    public function show(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        return response()->json([
            'success' => true,
            'media' => $media->publicPayload($request->user()),
        ]);
    }

    public function ratings(Request $request, TaxiRideRatingService $ratings, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $driverId = (int) ($request->user()?->id ?? 0);

        return response()->json([
            'success' => true,
            'reviews' => $ratings->listForDriver($media->connection(), $driverId),
        ]);
    }

    public function uploadProfilePhoto(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        try {
            $payload = $media->storeProfilePhoto($request->user(), $request->file('photo'));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Upload mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profielfoto opgeslagen.',
            'media' => $payload,
        ]);
    }

    public function deleteProfilePhoto(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profielfoto verwijderd.',
            'media' => $media->clearProfilePhoto($request->user()),
        ]);
    }

    public function uploadCompanyLogo(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        try {
            $payload = $media->storeCompanyLogo($request->user(), $request->file('photo'));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Upload mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bedrijfslogo opgeslagen.',
            'media' => $payload,
        ]);
    }

    public function deleteCompanyLogo(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Bedrijfslogo verwijderd.',
            'media' => $media->clearCompanyLogo($request->user()),
        ]);
    }

    public function uploadVehiclePhoto(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $vehicleId = (int) $request->input('vehicle_id', 0);
        if ($vehicleId <= 0) {
            $vehicleId = (int) (DriverAvailability::vehicleIdForDriver(
                $media->connection(),
                (int) $request->user()->id
            ) ?? 0);
        }

        try {
            $vehicle = $media->storeVehiclePhoto(
                $request->user(),
                $request->file('photo'),
                (int) $request->attributes->get('taxi_company_id'),
                $vehicleId
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Upload mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Auto-foto opgeslagen.',
            'media' => $media->publicPayload($request->user()),
            'vehicle' => $vehicle,
        ]);
    }

    public function deleteVehiclePhoto(Request $request, int $vehicleId, TaxiDriverProfileMediaService $media): JsonResponse
    {
        try {
            $vehicle = $media->clearVehiclePhoto(
                $request->user(),
                (int) $request->attributes->get('taxi_company_id'),
                $vehicleId
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Verwijderen mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Auto-foto verwijderd.',
            'media' => $media->publicPayload($request->user()),
            'vehicle' => $vehicle,
        ]);
    }
}
