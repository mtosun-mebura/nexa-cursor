<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;

class AdminWebManifestController extends Controller
{
    public function __invoke(WebsiteBuilderService $website): JsonResponse
    {
        $companyId = $website->faviconCompanyIdForRequestContext();
        $favicon = $website->publicFaviconMeta($companyId);
        $siteName = $companyId !== null
            ? (GeneralSetting::get('site_name', null, $companyId) ?: config('app.name'))
            : (GeneralSetting::get('site_name') ?: config('app.name'));

        return response()->json([
            'name' => trim((string) $siteName).' Admin',
            'short_name' => 'Admin',
            'description' => 'Nexa admin',
            'start_url' => url('/admin'),
            'scope' => url('/admin'),
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
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
}
