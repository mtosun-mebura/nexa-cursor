<?php

namespace App\Http\Controllers;

use App\Services\PublicSitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected PublicSitemapBuilder $sitemapBuilder,
    ) {}

    public function index(): Response
    {
        $xml = $this->sitemapBuilder->toXml();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
