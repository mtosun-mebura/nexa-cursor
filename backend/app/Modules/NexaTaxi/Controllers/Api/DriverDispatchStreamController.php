<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiDriverInboxPushService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverDispatchStreamController extends Controller
{
    public function stream(Request $request, TaxiDriverInboxPushService $push): StreamedResponse
    {
        if (! config('taxi-dispatch.stream_enabled', false)) {
            return response()->json([
                'message' => 'Live stream is uitgeschakeld in deze omgeving. Gebruik inbox-polling.',
            ], 503);
        }

        $driverId = (int) $request->user()->id;
        $maxSeconds = max(15, min(120, (int) config('taxi-dispatch.stream_max_seconds', 55)));
        $tickMs = max(250, min(2000, (int) config('taxi-dispatch.stream_tick_ms', 500)));

        return response()->stream(function () use ($push, $driverId, $maxSeconds, $tickMs): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $started = time();
            $lastVersion = null;

            echo ": connected\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while (! connection_aborted() && (time() - $started) < $maxSeconds) {
                $payload = $push->pullSignal($driverId);
                $version = is_array($payload) ? ($payload['v'] ?? null) : null;

                if ($version !== null && $version !== $lastVersion) {
                    $lastVersion = $version;
                    echo "event: inbox-update\n";
                    echo 'data: '.json_encode([
                        'v' => $version,
                        'ride_request_id' => $payload['ride_request_id'] ?? null,
                    ])."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                usleep($tickMs * 1000);
            }

            echo "event: reconnect\n";
            echo "data: {}\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
