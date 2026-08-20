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
            'appUrl' => $this->chauffeurNamedUrl('taxi.chauffeur.index', '/taxi/chauffeur'),
            'guideUrl' => $this->chauffeurNamedUrl('taxi.chauffeur.handleiding', '/taxi/chauffeur/handleiding'),
            'faviconUrl' => $favicon['url'],
            'faviconType' => $favicon['type'],
            'notificationIcon' => $favicon['url'],
        ]);
    }

    public function handleiding(): View
    {
        $favicon = $this->driverFaviconMeta();

        return view('taxi::driver-app.handleiding', [
            'appUrl' => $this->chauffeurNamedUrl('taxi.chauffeur.index', '/taxi/chauffeur'),
            'faviconUrl' => $favicon['url'],
            'faviconType' => $favicon['type'],
        ]);
    }

    public function manifest(): JsonResponse
    {
        $favicon = $this->driverFaviconMeta();

        return response()->json([
            'name' => 'Nexa Taxi Chauffeur',
            'short_name' => 'Chauffeur',
            'description' => 'Ritten accepteren en beheren',
            'start_url' => $this->chauffeurNamedUrl('taxi.chauffeur.index', '/taxi/chauffeur'),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0f172a',
            'theme_color' => '#f97316',
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
     * @return array{url: string, type: string}
     */
    private function driverFaviconMeta(): array
    {
        return app(WebsiteBuilderService::class)->publicFaviconMeta();
    }

    private function chauffeurNamedUrl(string $routeName, string $fallbackPath): string
    {
        return \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName)
            : url($fallbackPath);
    }
}
