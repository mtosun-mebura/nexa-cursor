<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Services\EnvService;
use App\Services\WebsiteBuilderService;
use App\Support\SameOriginPath;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DriverAppController extends Controller
{
    public function index(EnvService $env): View
    {
        $favicon = $this->driverFaviconMeta();

        return view('taxi::driver-app.index', [
            'apiBase' => '/api/taxi/v1/driver',
            'pollMs' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 2000),
            'streamEnabled' => (bool) config('taxi-dispatch.stream_enabled', false),
            'loginUrl' => '/api/taxi/v1/driver/login',
            'loginCodeRequestUrl' => '/api/taxi/v1/driver/login-code/request',
            'loginCodeVerifyUrl' => '/api/taxi/v1/driver/login-code/verify',
            'appUrl' => SameOriginPath::namedRoute('taxi.chauffeur.index', '/taxi/chauffeur'),
            'guideUrl' => SameOriginPath::namedRoute('taxi.chauffeur.handleiding', '/taxi/chauffeur/handleiding'),
            'manifestUrl' => SameOriginPath::namedRoute('taxi.chauffeur.manifest', '/taxi/chauffeur/manifest.webmanifest'),
            'faviconUrl' => $favicon['url'],
            'faviconType' => $favicon['type'],
            'notificationIcon' => $favicon['url'],
            'googleMapsApiKey' => $env->getGoogleMapsApiKey(),
            'googleMapsMapId' => (string) $env->get('GOOGLE_MAPS_MAP_ID', ''),
            'googleMapsCenterLat' => (string) $env->get('GOOGLE_MAPS_CENTER_LAT', '52.3676'),
            'googleMapsCenterLng' => (string) $env->get('GOOGLE_MAPS_CENTER_LNG', '4.9041'),
        ]);
    }

    public function handleiding(): View
    {
        $favicon = $this->driverFaviconMeta();

        return view('taxi::driver-app.handleiding', [
            'appUrl' => SameOriginPath::namedRoute('taxi.chauffeur.index', '/taxi/chauffeur'),
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
            'start_url' => SameOriginPath::namedRoute('taxi.chauffeur.index', '/taxi/chauffeur'),
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
        $meta = app(WebsiteBuilderService::class)->publicFaviconMeta();
        $meta['url'] = SameOriginPath::fromUrl($meta['url']);

        return $meta;
    }
}
