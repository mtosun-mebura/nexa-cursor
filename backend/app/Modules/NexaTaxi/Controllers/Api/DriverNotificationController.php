<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiDriverBlockService;
use App\Modules\NexaTaxi\Services\TaxiDriverNotificationService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverNotificationController extends Controller
{
    public function index(
        Request $request,
        ModuleDatabaseService $moduleDb,
        TaxiDriverNotificationService $notifications,
        TaxiDriverBlockService $blocks
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $driverId = (int) $request->user()->id;

        if ($blocks->isBlocked($conn, $driverId)) {
            return response()->json([
                'message' => $blocks->loginDeniedMessage(),
                'error' => 'driver_blocked',
                'blocked' => $blocks->blockedPayload($conn, $driverId),
            ], 403);
        }

        $items = $notifications->listOpenForDriver($conn, $driverId);

        return response()->json([
            'notifications' => $items,
            'unread_count' => $notifications->unreadCount($conn, $driverId),
        ]);
    }

    public function markRead(
        Request $request,
        int $notification,
        ModuleDatabaseService $moduleDb,
        TaxiDriverNotificationService $notifications
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $row = $notifications->markRead($conn, (int) $request->user()->id, $notification);
        if (! $row) {
            return response()->json(['message' => 'Melding niet gevonden.'], 404);
        }

        return response()->json([
            'success' => true,
            'notification' => $notifications->toPayload($row),
            'unread_count' => $notifications->unreadCount($conn, (int) $request->user()->id),
        ]);
    }

    public function close(
        Request $request,
        int $notification,
        ModuleDatabaseService $moduleDb,
        TaxiDriverNotificationService $notifications
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $row = $notifications->close($conn, (int) $request->user()->id, $notification);
        if (! $row) {
            return response()->json(['message' => 'Melding niet gevonden.'], 404);
        }

        return response()->json([
            'success' => true,
            'notification' => $notifications->toPayload($row),
            'unread_count' => $notifications->unreadCount($conn, (int) $request->user()->id),
        ]);
    }

    public function reopen(
        Request $request,
        int $notification,
        ModuleDatabaseService $moduleDb,
        TaxiDriverNotificationService $notifications
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $row = $notifications->reopen($conn, (int) $request->user()->id, $notification);
        if (! $row) {
            return response()->json(['message' => 'Melding niet gevonden.'], 404);
        }

        return response()->json([
            'success' => true,
            'notification' => $notifications->toPayload($row),
            'unread_count' => $notifications->unreadCount($conn, (int) $request->user()->id),
        ]);
    }
}
