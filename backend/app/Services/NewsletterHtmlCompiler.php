<?php

namespace App\Services;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterProspect;
use App\Support\NexaBranding;

class NewsletterHtmlCompiler
{
    /**
     * @param  array<string, mixed>  $blocks
     */
    public function compile(array $blocks, ?NewsletterProspect $prospect = null, ?string $unsubscribeUrl = null): string
    {
        $contactUrl = $this->absoluteUrl((string) ($blocks['cta_url'] ?? '/contact'));
        $ctaLabel = trim((string) ($blocks['cta_label'] ?? 'Aanmelden via contact')) ?: 'Aanmelden via contact';
        $title = trim((string) ($blocks['title'] ?? 'NEXA Suite voor taxibedrijven'));
        $eyebrow = trim((string) ($blocks['eyebrow'] ?? 'Voor taxibedrijven'));
        $intro = trim((string) ($blocks['intro'] ?? ''));
        $hero = $this->absoluteUrl((string) ($blocks['hero_image'] ?? config('newsletter.stock_images.hero')));
        $company = $prospect?->company_name ?: 'uw taxibedrijf';
        $unsubscribeUrl = $unsubscribeUrl ?: ($prospect?->unsubscribeUrl() ?? url('/contact'));
        $logo = NexaBranding::defaultLogoUrl();
        $greeting = 'Beste '.($prospect?->greetingName() ?: $company).',';

        $featuresHtml = '';
        foreach (array_values(is_array($blocks['features'] ?? null) ? $blocks['features'] : []) as $feature) {
            if (! is_array($feature)) {
                continue;
            }
            $fTitle = trim((string) ($feature['title'] ?? ''));
            $fText = trim((string) ($feature['text'] ?? ''));
            if ($fTitle === '' && $fText === '') {
                continue;
            }
            $fImage = $this->absoluteUrl((string) ($feature['image'] ?? ''));
            $img = $fImage !== ''
                ? '<img src="'.e($fImage).'" alt="'.e($fTitle).'" width="520" style="width:100%;max-width:520px;height:auto;border-radius:8px;display:block;margin:0 0 12px;">'
                : '';
            $featuresHtml .= '<tr><td style="padding:0 0 22px;">'
                .$img
                .'<p style="margin:0 0 6px;font-size:16px;font-weight:700;color:#0f172a;">'.e($fTitle).'</p>'
                .'<p style="margin:0;font-size:14px;line-height:1.6;color:#334155;">'.nl2br(e($fText)).'</p>'
                .'</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{$this->e($title)}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" style="width:100%;border-collapse:collapse;background:#f4f4f4;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:22px 28px;background:#0f172a;">
        <img src="{$this->e($logo)}" alt="NEXA" height="32" style="height:32px;width:auto;display:block;margin:0 0 12px;">
        <p style="margin:0 0 6px;font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#94a3b8;">{$this->e($eyebrow)}</p>
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">{$this->e($title)}</h1>
    </td>
</tr>
<tr>
    <td style="padding:0;">
        <img src="{$this->e($hero)}" alt="" width="600" style="width:100%;max-width:600px;height:auto;display:block;">
    </td>
</tr>
<tr>
    <td style="padding:28px 32px 8px;">
        <p style="margin:0 0 14px;font-size:16px;">{$this->e($greeting)}</p>
        <p style="margin:0 0 18px;font-size:15px;line-height:1.65;color:#334155;">{$this->nl2br($intro)}</p>
    </td>
</tr>
<tr>
    <td style="padding:0 32px;">
        <table role="presentation" width="100%">{$featuresHtml}</table>
    </td>
</tr>
<tr>
    <td style="padding:8px 32px 28px;text-align:center;">
        <a href="{$this->e($contactUrl)}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:13px 24px;border-radius:6px;">
            <span style="color:#ffffff;">{$this->e($ctaLabel)}</span>
        </a>
        <p style="margin:12px 0 0;font-size:12px;color:#64748b;">Of ga naar <a href="{$this->e($contactUrl)}" style="color:#2563eb;">nexasuite.nl/contact</a></p>
    </td>
</tr>
<tr>
    <td style="padding:16px 32px 24px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
        U ontvangt deze e-mail omdat uw taxibedrijf in onze B2B-lijst voor NEXA Suite staat.
        Wilt u geen berichten meer? <a href="{$this->e($unsubscribeUrl)}" style="color:#2563eb;">Hier afmelden</a>.
        NEXA Suite · <a href="mailto:info@nexasuite.nl" style="color:#2563eb;">info@nexasuite.nl</a>
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    public function compileCampaign(NewsletterCampaign $campaign, ?NewsletterProspect $prospect = null): string
    {
        return $this->compile(is_array($campaign->blocks) ? $campaign->blocks : [], $prospect);
    }

    public function textVersion(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</h1>', '</h2>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{3,}/", "\n\n", $text) ?? $text) ?? $text);
    }

    private function absoluteUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return url('/contact');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    private function e(string $value): string
    {
        return e($value);
    }

    private function nl2br(string $value): string
    {
        return nl2br(e($value), false);
    }
}
