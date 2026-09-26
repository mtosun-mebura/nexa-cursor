<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim(url('/'), '/').'/sitemap.xml';
        $isCentral = \App\Support\Tenancy\CentralDomains::isCentral((string) request()->getHost());

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /mijn-taxi',
            'Disallow: /marketing',
            'Disallow: /marketing/',
            'Disallow: /demo',
            'Disallow: /test-404',
            'Disallow: /nieuwsbrief/',
        ];

        // Op tenant-sites: blokkeer centrale NEXA-/Skillmatching-pagina's (verkeerde content voor Google).
        if (! $isCentral) {
            $lines = array_merge($lines, [
                'Disallow: /privacy',
                'Disallow: /help',
                'Disallow: /voorwaarden',
                'Disallow: /terms',
                'Disallow: /disclaimer',
                'Disallow: /starten',
            ]);
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$sitemapUrl;
        $lines[] = '';

        $body = implode("\n", $lines);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
