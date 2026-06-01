<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WebsiteBuilderService;

class TaxiPortalController extends Controller
{
    public function index(WebsiteBuilderService $websiteBuilder)
    {
        return view('frontend.pages.taxi-portal', [
            'branding' => $websiteBuilder->getSiteBranding('taxi'),
        ]);
    }
}
