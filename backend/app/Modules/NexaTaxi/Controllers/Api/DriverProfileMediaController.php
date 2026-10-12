<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiDriverProfileMediaService;
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

    public function uploadVehiclePhoto(Request $request, TaxiDriverProfileMediaService $media): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        try {
            $payload = $media->storeVehiclePhoto($request->user(), $request->file('photo'));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Upload mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Auto-foto opgeslagen.',
            'media' => $payload,
        ]);
    }
}
