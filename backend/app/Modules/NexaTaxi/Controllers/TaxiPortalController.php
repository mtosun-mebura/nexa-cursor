<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\Request;

class TaxiPortalController extends Controller
{
    public function index(Request $request, WebsiteBuilderService $websiteBuilder)
    {
        /** @var User $user */
        $user = auth()->user();
        $sectionKey = 'component:taxi.boekingsmodule';
        $bookingResolved = $websiteBuilder->resolveBookingModuleSection($sectionKey, 'taxi');
        $homePage = $bookingResolved['page'];
        $homeSections = $homePage?->getHomeSections() ?? [];
        $googleMapsApiKey = $homePage
            ? $websiteBuilder->resolveGoogleMapsApiKeyForPage($homePage)
            : '';
        if ($googleMapsApiKey === '') {
            $googleMapsApiKey = trim((string) config('maps.api_key', ''));
        }

        return view('frontend.pages.taxi-portal', [
            'branding' => $websiteBuilder->getSiteBranding('taxi'),
            'homeSections' => $homeSections,
            'bookingConfig' => $bookingResolved['config'],
            'sectionKey' => $sectionKey,
            'page' => $homePage,
            'googleMapsApiKey' => $googleMapsApiKey,
            'bookingCustomerPrefill' => [
                'first_name' => (string) ($user->first_name ?? ''),
                'last_name' => (string) ($user->last_name ?? ''),
                'email' => (string) ($user->email ?? ''),
                'phone' => (string) ($user->phone ?? ''),
            ],
            'bookingPortalMode' => true,
            'bookingReturnUrl' => route('taxi.portal.dashboard'),
        ]);
    }
}
