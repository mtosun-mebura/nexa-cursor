<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DriverAppController extends Controller
{
    public function index(): View
    {
        $favicon = $this->driverFaviconMeta();

        return view('taxi::driver-app.index', [
            'apiBase' => url('/api/taxi/v1/driver'),
            'pollMs' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 2000),
            'streamEnabled' => (bool) config('taxi-dispatch.stream_enabled', false),
            'appUrl' => route('taxi.chauffeur.index'),
            'faviconUrl' => $favicon['url'],
            'faviconType' => $favicon['type'],
            'notificationIcon' => $favicon['url'],
        ]);
    }

    public function manifest(): JsonResponse
    {
        $favicon = $this->driverFaviconMeta();

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
                    'src' => $favicon['url'],
                    'sizes' => '192x192',
                    'type' => $favicon['type'],
                    'purpose' => 'any',
                ],
                [
                    'src' => $favicon['url'],
                    'sizes' => '512x512',
                    'type' => $favicon['type'],
                    'purpose' => 'any maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }

    /**
     * Zelfde favicon als de tenant-website (custom upload of Nexa-standaard).
     *
     * @return array{url: string, type: string}
     */
    private function driverFaviconMeta(): array
    {
        return app(WebsiteBuilderService::class)->publicFaviconMeta();
    }
}
