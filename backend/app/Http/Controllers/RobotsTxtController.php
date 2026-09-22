<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim(url('/'), '/').'/sitemap.xml';

        $body = implode("\n", [
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
            '',
            'Sitemap: '.$sitemapUrl,
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
