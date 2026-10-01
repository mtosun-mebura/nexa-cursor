<?php

namespace App\Support;

use App\Models\EmailTemplate;
use App\Services\CompanyEmailLogoService;
use Illuminate\Mail\Message;
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
        $kickerHtml = '';
        if ($headerKicker !== null && $headerKicker !== '' && ! self::isRedundantBrandKicker($headerKicker)) {
            $kickerHtml = '<p style="margin:0 0 6px;color:#94a3b8;font-size:13px;letter-spacing:0.04em;">'
                .self::safeInline($headerKicker)
                .'</p>';
        }
        $footer = $footerInnerHtml ?? self::poweredByFooter();

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{$title}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;color-scheme:light;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {$logoHtml}
        {$kickerHtml}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">{$headingSafe}</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
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
        if ($html === '' || str_contains($html, '#0f172a')) {
            return false;
        }

        return str_contains($html, 'COMPANY_LOGO')
            || str_contains($html, 'NEXA_LOGO')
            || str_contains($html, 'max-width: 600px')
            || str_contains($html, 'max-width:600px');
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

    public static function isRedundantBrandKicker(string $kicker): bool
    {
        $normalized = mb_strtolower(trim(html_entity_decode(strip_tags($kicker), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return in_array($normalized, ['nexa suite', 'nexa'], true);
    }

    public static function stripRedundantBrandKickerHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'NEXA')) {
            return $html;
        }

        return preg_replace(
            '/<p\b[^>]*>\s*NEXA(?:\s+Suite)?\s*<\/p>/i',
            '',
            $html
        ) ?? $html;
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
