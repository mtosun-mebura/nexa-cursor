<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiAppLogoService;
use App\Services\WebsiteBuilderService;
use App\Support\SameOriginPath;
use Illuminate\View\View;

class TaxiAppLauncherController extends Controller
{
    public function index(TaxiAppLogoService $logos): View
    {
        $meta = app(WebsiteBuilderService::class)->publicFaviconMeta();
        $meta['url'] = SameOriginPath::fromUrl($meta['url']);
        $logoUrls = $logos->nexaTaxiLogoUrls();

        return view('taxi::app-launcher.index', [
            'faviconUrl' => $meta['url'],
            'faviconType' => $meta['type'],
            'logoLightUrl' => SameOriginPath::fromUrl($logoUrls['light']),
            'logoDarkUrl' => SameOriginPath::fromUrl($logoUrls['dark']),
            'customerUrl' => SameOriginPath::namedRoute('taxi.klant.index', '/taxi/klant'),
            'driverUrl' => SameOriginPath::namedRoute('taxi.chauffeur.index', '/taxi/chauffeur'),
            'contractUrl' => SameOriginPath::namedRoute('taxi.contract.index', '/taxi/contract'),
        ]);
    }
}
