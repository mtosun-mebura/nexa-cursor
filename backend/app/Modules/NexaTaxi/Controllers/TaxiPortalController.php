<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\Request;

class TaxiPortalController extends Controller
{
    public function index(Request $request, WebsiteBuilderService $websiteBuilder)
    {
        if (! auth()->check()) {
            return redirect()->route('login', [
                'intended' => $request->getUri(),
            ]);
        }

        return view('frontend.pages.taxi-portal', [
            'branding' => $websiteBuilder->getSiteBranding('taxi'),
        ]);
    }
}
