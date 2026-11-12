<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\CentralDomains;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $host = strtolower((string) request()->getHost());
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        $scheme = request()->secure() ? 'https' : request()->getScheme();
        $base = rtrim($scheme.'://'.($host !== '' ? $host : request()->getHost()), '/');
        $sitemapUrl = $base.'/sitemap.xml';
        $llmsUrl = $base.'/llms.txt';
        $isCentral = CentralDomains::isCentral((string) request()->getHost())
            || CentralDomains::isCentral($host);

        $lines = [
            '# Nexa — crawlregels voor zoekmachines en AI',
            'User-agent: *',
            'Allow: /',
            'Allow: /llms.txt',
            'Allow: /sitemap.xml',
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
            'Disallow: /api/',
            'Disallow: /sanctum/',
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

        // AI-crawlers expliciet welkom (Google AI Overviews, ChatGPT, Perplexity, Claude).
        foreach ([
            'Google-Extended',
            'GPTBot',
            'ChatGPT-User',
            'PerplexityBot',
            'ClaudeBot',
            'Anthropic-AI',
            'Applebot-Extended',
        ] as $bot) {
            $lines[] = '';
            $lines[] = 'User-agent: '.$bot;
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /admin/';
            $lines[] = 'Disallow: /login';
            $lines[] = 'Disallow: /api/';
        }

        $lines[] = '';
        $lines[] = 'Host: '.$base;
        $lines[] = 'Sitemap: '.$sitemapUrl;
        $lines[] = 'LLMs-Txt: '.$llmsUrl;
        $lines[] = '';

        $body = implode("\n", $lines);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
