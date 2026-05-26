<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DriverAppController extends Controller
{
    public function index(): View
    {
        return view('taxi::driver-app.index', [
            'apiBase' => url('/api/taxi/v1/driver'),
            'pollMs' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 2000),
            'streamEnabled' => (bool) config('taxi-dispatch.stream_enabled', false),
            'appUrl' => route('taxi.chauffeur.index'),
            'notificationIcon' => asset('assets/media/app/nexa-chauffeur-icon-192.png'),
        ]);
    }

    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => 'Nexa Taxi Chauffeur',
            'short_name' => 'Chauffeur',
            'description' => 'Ritten accepteren en beheren',
            'start_url' => route('taxi.chauffeur.index'),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0f172a',
            'theme_color' => '#16a34a',
            'icons' => [
                [
                    'src' => asset('assets/media/app/nexa-chauffeur-icon-32.png'),
                    'sizes' => '32x32',
                    'type' => 'image/png',
                ],
                [
                    'src' => asset('assets/media/app/nexa-chauffeur-icon-180.png'),
                    'sizes' => '180x180',
                    'type' => 'image/png',
                ],
                [
                    'src' => asset('assets/media/app/nexa-chauffeur-icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/media/app/nexa-chauffeur-icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }
}
