<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiAppLogoService;
use App\Services\EnvService;
use App\Services\WebsiteBuilderService;
use App\Support\SameOriginPath;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CustomerAppController extends Controller
{
    public function index(EnvService $env, TaxiAppLogoService $logos): View
    {
        $favicon = $this->faviconMeta();
        $logoUrls = $logos->nexaTaxiLogoUrls();

        return view('taxi::customer-app.index', [
            'apiBase' => '/api/taxi/v1/customer',
            'loginUrl' => '/api/taxi/v1/customer/login',
            'registerUrl' => '/api/taxi/v1/customer/register',
            'bookUrl' => '/api/taxi/v1/customer/book',
            'quoteUrl' => '/api/taxi/v1/customer/quote',
            'liveUrl' => '/api/taxi/v1/customer/live',
            'addressSearchUrl' => '/nexa-taxi/booking/address-search',
            'nearbyTaxisUrl' => '/nexa-taxi/booking/nearby-taxis',
            'appUrl' => SameOriginPath::namedRoute('taxi.klant.index', '/taxi/klant'),
            'launcherUrl' => SameOriginPath::namedRoute('taxi.app.index', '/taxi/app'),
            'manifestUrl' => SameOriginPath::namedRoute('taxi.klant.manifest', '/taxi/klant/manifest.webmanifest'),
            'faviconUrl' => $favicon['url'],
            'faviconType' => $favicon['type'],
            'logoLightUrl' => SameOriginPath::fromUrl($logoUrls['light']),
            'logoDarkUrl' => SameOriginPath::fromUrl($logoUrls['dark']),
            'googleMapsApiKey' => $env->getGoogleMapsApiKey(),
            'googleMapsMapId' => (string) $env->get('GOOGLE_MAPS_MAP_ID', ''),
            'googleMapsCenterLat' => (string) $env->get('GOOGLE_MAPS_CENTER_LAT', '52.3676'),
            'googleMapsCenterLng' => (string) $env->get('GOOGLE_MAPS_CENTER_LNG', '4.9041'),
        ]);
    }

    public function manifest(): JsonResponse
    {
        $favicon = $this->faviconMeta();

        return response()->json([
            'name' => 'Nexa Taxi',
            'short_name' => 'Nexa Taxi',
            'description' => 'Boek een taxi en volg je rit',
            'start_url' => SameOriginPath::namedRoute('taxi.klant.index', '/taxi/klant'),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0f172a',
            'theme_color' => '#2563eb',
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
    private function faviconMeta(): array
    {
        $meta = app(WebsiteBuilderService::class)->publicFaviconMeta();
        $meta['url'] = SameOriginPath::fromUrl($meta['url']);

        return $meta;
    }
}
