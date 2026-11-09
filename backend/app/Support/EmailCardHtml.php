<?php

namespace App\Support;

use App\Models\EmailTemplate;
use App\Services\CompanyEmailLogoService;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class EmailCardHtml
{
    public static function wrap(
        string $pageTitle,
        string $heading,
        string $bodyInnerHtml,
        string $logoHtml,
        ?string $footerInnerHtml = null,
        ?string $headerKicker = null,
    ): string {
        $title = self::safeInline($pageTitle);
        $headingSafe = self::safeInline($heading);
        $kickerHtml = self::headerBrandKickerHtml($headerKicker, $logoHtml);
        $footer = $footerInnerHtml ?? self::poweredByFooter();

        $htmlLang = in_array(App::getLocale(), ['nl', 'en'], true) ? App::getLocale() : 'nl';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>{$title}</title>
    <style>:root { color-scheme: light only; }</style>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#e5e7eb;color:#111827;color-scheme:light only;">
<table role="presentation" width="100%" bgcolor="#e5e7eb" style="width:100%;border-collapse:collapse;background-color:#e5e7eb;">
<tr><td bgcolor="#e5e7eb" style="padding:24px 12px;background-color:#e5e7eb;">
<table role="presentation" width="100%" bgcolor="#ffffff" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:12px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#ffffff" style="padding:28px 32px 20px;background-color:#ffffff;border-radius:12px 12px 0 0;">
        {$logoHtml}
        {$kickerHtml}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#0f172a;">{$headingSafe}</h1>
    </td>
</tr>
<tr>
    <td bgcolor="#ffffff" style="padding:8px 32px 28px;background-color:#ffffff;border-radius:0 0 12px 12px;">
        {$bodyInnerHtml}
        {$footer}
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function nexaLogoMarkup(): string
    {
        return NexaBranding::EMAIL_LOGO_PLACEHOLDER;
    }

    public static function companyLogoMarkup(): string
    {
        return '{{ COMPANY_LOGO }}';
    }

    public static function poweredByFooter(): string
    {
        return '<p style="margin:20px 0 0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">'
            .'Powered by <a href="https://nexasuite.nl" style="color:#6b7280;text-decoration:underline;">NEXA Suite</a>.'
            .'</p>';
    }

    /**
     * "Vragen? Neem contact op met {tenant}." — $companyNameHtml mag platte tekst of een <a> zijn.
     */
    public static function questionsContactLine(string $companyNameHtml): string
    {
        $name = trim($companyNameHtml);
        if ($name === '') {
            $name = 'ons';
        }

        return '<p style="margin:20px 0 0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">'
            .'Vragen? Neem contact op met '.$name.'.'
            .'</p>';
    }

    /**
     * Tenantnaam als link naar primaire website, anders ge-escaped platte tekst.
     */
    public static function companyNameHtml(string $companyName, ?string $websiteUrl = null): string
    {
        $name = trim($companyName);
        if ($name === '') {
            return 'ons';
        }

        $url = self::normalizeWebsiteUrl($websiteUrl);
        if ($url === null) {
            return e($name);
        }

        return '<a href="'.e($url).'" style="color:#6b7280;text-decoration:underline;">'.e($name).'</a>';
    }

    public static function questionsAndPoweredByFooter(string $companyNameHtml): string
    {
        $powered = '<p style="margin:8px 0 0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">'
            .'Powered by <a href="https://nexasuite.nl" style="color:#6b7280;text-decoration:underline;">NEXA Suite</a>.'
            .'</p>';

        return self::questionsContactLine($companyNameHtml).$powered;
    }

    /**
     * Zet platte "Powered by NEXA Suite." om naar een link (bestaande templates).
     */
    public static function ensurePoweredByLink(string $html): string
    {
        if ($html === '' || str_contains($html, 'nexasuite.nl')) {
            return $html;
        }

        if (preg_match('/Dit bericht is automatisch verstuurd door NEXA Suite\./', $html)) {
            $html = preg_replace(
                '/Dit bericht is automatisch verstuurd door NEXA Suite\./',
                'Powered by <a href="https://nexasuite.nl" style="color:#6b7280;text-decoration:underline;">NEXA Suite</a>.',
                $html,
                1
            ) ?? $html;
        }

        if (str_contains($html, 'Powered by NEXA Suite.') && ! str_contains($html, 'nexasuite.nl')) {
            $html = str_replace(
                'Powered by NEXA Suite.',
                'Powered by <a href="https://nexasuite.nl" style="color:#6b7280;text-decoration:underline;">NEXA Suite</a>.',
                $html
            );
        }

        if (! str_contains($html, 'Powered by') && ! str_contains($html, 'nexasuite.nl')) {
            $powered = self::poweredByFooter();
            if (str_contains($html, '</body>')) {
                $html = str_replace('</body>', $powered.'</body>', $html);
            } else {
                $html .= $powered;
            }
        }

        return $html;
    }

    public static function normalizeWebsiteUrl(?string $websiteUrl): ?string
    {
        $raw = trim((string) $websiteUrl);
        if ($raw === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $raw)) {
            $raw = 'https://'.ltrim($raw, '/');
        }

        if (filter_var($raw, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $raw;
    }

    /**
     * Primaire tenant-host als https-URL (company_domains.is_primary).
     */
    public static function primaryWebsiteUrlForCompany(?int $companyId): ?string
    {
        if ($companyId === null || $companyId <= 0) {
            return null;
        }

        $host = \App\Support\Tenancy\TenantFrontendUrl::resolvePrimaryHostForCompany($companyId);

        return self::normalizeWebsiteUrl($host);
    }

    public static function bodyFromPlainText(string $text): string
    {
        $parts = preg_split("/\n{2,}/", trim($text)) ?: [];
        $html = '';
        foreach ($parts as $index => $part) {
            $margin = $index === array_key_last($parts) ? '0 0 0' : '0 0 16px';
            $html .= '<p style="margin:'.$margin.';font-size:15px;line-height:1.6;white-space:pre-wrap;">'
                .nl2br(e($part), false)
                .'</p>';
        }

        return $html;
    }

    /**
     * @param  callable(Message):void|null  $configure
     */
    public static function sendNexa(
        string $to,
        string $subject,
        string $heading,
        string $plainBody,
        ?callable $configure = null,
        ?string $toName = null,
    ): void {
        $html = self::wrap(
            $subject,
            $heading,
            self::bodyFromPlainText($plainBody),
            self::nexaLogoMarkup(),
            self::poweredByFooter(),
        );

        Mail::send([], [], function (Message $message) use ($to, $toName, $subject, $html, $plainBody, $configure) {
            $htmlBody = app(CompanyEmailLogoService::class)->embedInHtml($html, $message, null, 'NEXA Suite');
            $message->to($to, $toName)->subject($subject)->html($htmlBody)->text($plainBody);
            if ($configure) {
                $configure($message);
            }
        });
    }

    public static function looksLikeLegacyLayout(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        // Current light card chrome (grey page + white rounded card).
        if (str_contains($html, 'background-color:#e5e7eb') && str_contains($html, 'color-scheme: light only')) {
            return false;
        }

        // Dark-header card era → upgrade to light chrome.
        if (preg_match('/bgcolor=["\']#0f172a["\']/i', $html) === 1) {
            return true;
        }

        return str_contains($html, 'COMPANY_LOGO')
            || str_contains($html, 'NEXA_LOGO')
            || str_contains($html, 'max-width: 600px')
            || str_contains($html, 'max-width:600px');
    }

    /**
     * Force light page + white rounded card header so the light-mode logo stays readable
     * even when the mail client opens the message in dark mode.
     */
    public static function ensureLightEmailChrome(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        // Header cells that used the old slate band.
        $html = preg_replace(
            '/(<td\b[^>]*?)\sbgcolor=(["\'])#0f172a\2/i',
            '$1 bgcolor=$2#ffffff$2',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/(padding:\s*2[48]px\s+3[02]px;?)([^"\']*?)background-color:\s*#0f172a/i',
            '$1$2background-color:#ffffff',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/background-color:\s*#0f172a([^"\']*?)(padding:\s*2[48]px\s+3[02]px)/i',
            'background-color:#ffffff$1$2',
            $html
        ) ?? $html;

        // Headings that were white on the dark band.
        $html = preg_replace(
            '/(<h1\b[^>]*style="[^"]*)\bcolor:\s*#ffffff\b/i',
            '$1color:#0f172a',
            $html
        ) ?? $html;

        // Page canvas behind the card.
        $html = preg_replace(
            '/(<body\b[^>]*style="[^"]*)background-color:\s*#f4f4f4/i',
            '$1background-color:#e5e7eb',
            $html
        ) ?? $html;

        if (self::htmlHasLogoBrandMark($html)) {
            $html = self::stripCompanyNameKickers($html);
        }

        return $html;
    }

    public static function upgradeTypeToCardLayout(string $type, callable $htmlFactory): void
    {
        EmailTemplate::query()
            ->where('type', $type)
            ->get()
            ->each(function (EmailTemplate $template) use ($htmlFactory): void {
                $current = (string) $template->html_content;
                if (! self::looksLikeLegacyLayout($current)) {
                    return;
                }

                $next = (string) $htmlFactory($template);
                if ($next === '' || $next === $current) {
                    return;
                }

                $template->html_content = $next;
                $template->save();
            });
    }

    /**
     * Any content in the logo slot (img, placeholder, or name fallback) is the brand mark.
     * Company-name kicker is only used when the logo slot is empty.
     */
    public static function logoImpliesBrandMark(?string $logoHtml): bool
    {
        return trim((string) $logoHtml) !== '';
    }

    /**
     * Company name under the logo only when there is no logo; then larger/clearer as brand stand-in.
     */
    public static function headerBrandKickerHtml(?string $headerKicker, ?string $logoHtml = null): string
    {
        if ($headerKicker === null || trim($headerKicker) === '' || self::isRedundantBrandKicker($headerKicker)) {
            return '';
        }
        if (self::logoImpliesBrandMark($logoHtml)) {
            return '';
        }

        return '<p style="margin:0 0 10px;color:#0f172a;font-size:22px;line-height:1.25;font-weight:700;letter-spacing:0.01em;">'
            .self::safeInline($headerKicker)
            .'</p>';
    }

    public static function isRedundantBrandKicker(string $kicker): bool
    {
        $normalized = mb_strtolower(trim(html_entity_decode(strip_tags($kicker), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return in_array($normalized, ['nexa suite', 'nexa'], true);
    }

    public static function stripRedundantBrandKickerHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        if (str_contains($html, 'NEXA')) {
            $html = preg_replace(
                '/<p\b[^>]*>\s*NEXA(?:\s+Suite)?\s*<\/p>/i',
                '',
                $html
            ) ?? $html;
        }

        // Logo slot filled (img, CID, placeholder, or {{ COMPANY_LOGO }}) → drop legacy grey kickers.
        if (self::htmlHasLogoBrandMark($html)) {
            $html = self::stripCompanyNameKickers($html);
        }

        return $html;
    }

    /**
     * True when the HTML already has (or will have) a logo brand mark in the header.
     */
    public static function htmlHasLogoBrandMark(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        if (str_contains($html, CompanyEmailLogoService::HTML_PLACEHOLDER)) {
            return true;
        }

        if (preg_match('/\{\{\s*COMPANY_LOGO\s*\}\}|\{\{\s*NEXA_LOGO\s*\}\}/i', $html) === 1) {
            return true;
        }

        if (preg_match('/src=(["\'])cid:/i', $html) === 1) {
            return true;
        }

        return preg_match('/<img\b[^>]*>/i', $html) === 1;
    }

    /**
     * Remove brand kickers under the logo (company name repeated as text).
     * Covers legacy grey 13px, dark-header white 22px, and light-header slate 22px — not muted body/footer copy.
     */
    public static function stripCompanyNameKickers(string $html): string
    {
        $patterns = [
            '/<p\b[^>]*style="(?=[^"]*color:\s*#94a3b8)(?=[^"]*font-size:\s*13px)[^"]*"[^>]*>\s*[^<]*?\s*<\/p>/is',
            '/<p\b[^>]*style="(?=[^"]*color:\s*#ffffff)(?=[^"]*font-size:\s*22px)[^"]*"[^>]*>\s*[^<]*?\s*<\/p>/is',
            '/<p\b[^>]*style="(?=[^"]*color:\s*#0f172a)(?=[^"]*font-size:\s*22px)(?=[^"]*font-weight:\s*700)[^"]*"[^>]*>\s*[^<]*?\s*<\/p>/is',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html) ?? $html;
        }

        return $html;
    }

    public static function stripRedundantBrandKickerFromStoredTemplates(): int
    {
        $updated = 0;
        EmailTemplate::query()
            ->where(function ($query): void {
                $query->where('html_content', 'like', '%NEXA Suite</p>%')
                    ->orWhere('html_content', 'like', '%NEXA</p>%');
            })
            ->get()
            ->each(function (EmailTemplate $template) use (&$updated): void {
                $current = (string) $template->html_content;
                $next = self::stripRedundantBrandKickerHtml($current);
                if ($next === $current) {
                    return;
                }

                $template->html_content = $next;
                $template->save();
                $updated++;
            });

        return $updated;
    }

    private static function safeInline(string $value): string
    {
        if (str_contains($value, '{{') || str_contains($value, '<!--')) {
            return $value;
        }

        return e($value);
    }
}
