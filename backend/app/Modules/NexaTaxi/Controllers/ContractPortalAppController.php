<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ContractPortalAppController extends Controller
{
    public function index(): View
    {
        $favicon = $this->faviconMeta();

        return view('taxi::contract-portal.index', [
            'apiBase' => url('/api/taxi/v1/contract'),
            'appUrl' => route('taxi.contract.index'),
            'faviconUrl' => $favicon['url'],
            'faviconType' => $favicon['type'],
            'pollMs' => 15000,
        ]);
    }

    public function manifest(): JsonResponse
    {
        $favicon = $this->faviconMeta();

        return response()->json([
            'name' => 'Nexa Taxi Contract',
            'short_name' => 'Contract',
            'description' => 'Leerlingen en status volgen, afmelden bij afwezigheid',
            'start_url' => route('taxi.contract.index'),
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
        return app(WebsiteBuilderService::class)->publicFaviconMeta();
    }
}
