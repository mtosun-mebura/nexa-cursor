<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiAppDemoModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxiAppDemoModeController extends Controller
{
    public function show(TaxiAppDemoModeService $demo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $demo->status(),
        ]);
    }

    public function update(Request $request, TaxiAppDemoModeService $demo): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Alleen een superadmin kan de demo-modus wijzigen.',
            ], 403);
        }

        if (! $demo->environmentAllowed()) {
            return response()->json([
                'success' => false,
                'message' => 'Demo-modus is alleen beschikbaar op lokaal en test.',
                'data' => $demo->status(),
            ], 422);
        }

        $data = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $enabled = $demo->setEnabled((bool) $data['enabled']);

        return response()->json([
            'success' => true,
            'data' => array_merge($demo->status(), ['enabled' => $enabled]),
        ]);
    }
}
