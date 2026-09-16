<?php

namespace App\Support;

class NexaLegalLinks
{
    public const MARKER = 'data-nexa-legal-links';

    public static function baseUrl(): string
    {
        $configured = rtrim((string) config('nexa.marketing_url', ''), '/');
        if ($configured !== '') {
            return $configured;
        }

        foreach ((array) config('tenancy.central_domains', []) as $host) {
            $host = strtolower(trim((string) $host));
            if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                continue;
            }
            if (str_contains($host, ':')) {
                continue;
            }

            return 'https://'.$host;
        }

        return rtrim((string) config('app.url'), '/');
    }

    public static function termsUrl(): string
    {
        return self::baseUrl().'/voorwaarden';
    }

    public static function disclaimerUrl(): string
    {
        return self::baseUrl().'/disclaimer';
    }

    public static function htmlFooter(): string
    {
        $terms = e(self::termsUrl());
        $disclaimer = e(self::disclaimerUrl());

        return <<<HTML
<p style="margin:16px 0 0;font-size:11px;line-height:1.6;color:#9ca3af;text-align:center;" data-nexa-legal-links="1">
    <a href="{$terms}" style="color:#9ca3af;text-decoration:underline;">Algemene voorwaarden</a>
    &nbsp;·&nbsp;
    <a href="{$disclaimer}" style="color:#9ca3af;text-decoration:underline;">Disclaimer</a>
</p>
HTML;
    }

    public static function textFooter(): string
    {
        return "Algemene voorwaarden: ".self::termsUrl()."\nDisclaimer: ".self::disclaimerUrl();
    }

    public static function ensureHtmlFooter(string $html): string
    {
        if ($html === '' || str_contains($html, self::MARKER)) {
            return $html;
        }

        $footer = self::htmlFooter();
        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $footer.'</body>', $html, 1) ?? ($html.$footer);
        }

        return $html.$footer;
    }

    public static function ensureTextFooter(string $text): string
    {
        if ($text === '' || str_contains($text, self::termsUrl())) {
            return $text;
        }

        return rtrim($text)."\n\n".self::textFooter();
    }
}
