<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\GeneralSetting;
use App\Support\EmailCardHtml;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tenant-specifiek logo voor e-mailtemplates ({{ COMPANY_LOGO }}).
 *
 * Algemene templates (company_id null) gebruiken altijd het logo van de tenant in de
 * verzendcontext (rit, boeking, factuur), niet van het template-record.
 */
class CompanyEmailLogoService
{
    /**
     * Bepaal welk bedrijf het logo levert: template-bedrijf heeft voorrang, anders verzend-context.
     */
    public function resolveLogoCompanyId(?int $templateCompanyId, ?int $sendContextCompanyId): ?int
    {
        if ($templateCompanyId !== null && $templateCompanyId > 0) {
            return $templateCompanyId;
        }

        if ($sendContextCompanyId !== null && $sendContextCompanyId > 0) {
            return $sendContextCompanyId;
        }

        return null;
    }

    /** Vervangen in Mail::send vóór verzending door ingesloten afbeelding of tekstfallback. */
    public const HTML_PLACEHOLDER = '<!--NEXA_COMPANY_LOGO-->';

    public function __construct(
        protected WebsiteBuilderService $websiteBuilder,
    ) {}

    /**
     * @return array{COMPANY_LOGO: string}
     */
    public function templateVariable(?int $companyId, ?string $fallbackName = null): array
    {
        return [
            'COMPANY_LOGO' => $this->hasLogoSource($companyId)
                ? self::HTML_PLACEHOLDER
                : $this->fallbackNameHtml($fallbackName),
        ];
    }

    /**
     * Na parseTemplateVariables: logo inline embedden (betrouwbaar in Gmail/Outlook).
     */
    public function embedInHtml(string $html, Message $message, ?int $companyId, ?string $fallbackName = null): string
    {
        $html = EmailCardHtml::stripRedundantBrandKickerHtml(
            \App\Support\NexaBranding::embedInMessage($html, $message)
        );

        if (! str_contains($html, self::HTML_PLACEHOLDER)) {
            // Already-rendered logo (or none): still drop leftover grey company-name kickers.
            return EmailCardHtml::htmlHasLogoBrandMark($html)
                ? EmailCardHtml::stripCompanyNameKickers($html)
                : $html;
        }

        // Klantmails gebruiken een donkere header (#0f172a) → dark-logo (lichte tekst).
        $payload = $this->resolveEmailLogoPayload($companyId);
        if ($payload === null) {
            $html = str_replace(self::HTML_PLACEHOLDER, $this->fallbackNameHtml($fallbackName), $html);

            // Name fallback is the brand mark — drop leftover grey company-name kickers.
            return EmailCardHtml::stripCompanyNameKickers($html);
        }

        $cid = $message->embedData($payload['data'], 'company-logo', $payload['mime']);

        $html = str_replace(
            self::HTML_PLACEHOLDER,
            $this->imgHtmlWithSrc($cid, 'Logo'),
            $html
        );

        // Strip AFTER logo embed: stored body_html may still contain the old grey kicker
        // (stripRedundantBrandKickerHtml runs before the placeholder becomes an <img>).
        return EmailCardHtml::stripCompanyNameKickers($html);
    }

    /**
     * Publieke HTTPS-URL voor e-mailclients (tenant-domein indien bekend).
     * Standaard dark-variant (donkere mailheader); light alleen als er geen dark-logo is.
     */
    public function publicLogoUrl(?int $companyId, bool $preferDarkHeader = true): ?string
    {
        if ($companyId === null || $companyId <= 0) {
            return null;
        }
        if (! $this->hasLogoSource($companyId)) {
            return null;
        }

        $base = $this->resolvePublicBaseUrl($companyId);
        $path = route('email.company-logo', ['company' => $companyId], false);
        $url = rtrim($base, '/').$path;

        return $this->withDarkVariantQuery($url, $companyId, $preferDarkHeader);
    }

    /**
     * Logo-URL op het huidige host (admin-preview op localhost of admin-domein).
     * Standaard dark-variant voor e-mailheaders; PWA light blijft via pwaLogoUrls.
     */
    public function adminPreviewLogoUrl(?int $companyId, bool $preferDarkHeader = true): ?string
    {
        if ($companyId === null || $companyId <= 0 || ! $this->hasLogoSource($companyId)) {
            return null;
        }

        $url = url(route('email.company-logo', ['company' => $companyId], false));

        return $this->withDarkVariantQuery($url, $companyId, $preferDarkHeader);
    }

    /**
     * Light- en dark-logo voor chauffeur-/contract-PWA (huidige host).
     *
     * @return array{light: ?string, dark: ?string}
     */
    public function pwaLogoUrls(?int $companyId): array
    {
        $light = $this->adminPreviewLogoUrl($companyId, false);
        if ($light === null) {
            return ['light' => null, 'dark' => null];
        }

        $dark = $this->resolveDarkLogoPayload($companyId) !== null
            ? $this->withDarkVariantQuery($light, $companyId, true)
            : $light;

        return ['light' => $light, 'dark' => $dark];
    }

    /**
     * Logo voor e-mail (donkere header): dark-blob/pad, anders light.
     *
     * @return array{data: string, mime: string}|null
     */
    public function resolveEmailLogoPayload(?int $companyId): ?array
    {
        return $this->resolveDarkLogoPayload($companyId)
            ?? $this->resolveLogoPayload($companyId);
    }

    /**
     * @return array{data: string, mime: string}|null
     */
    public function resolveDarkLogoPayload(?int $companyId): ?array
    {
        if ($companyId === null || $companyId <= 0) {
            return null;
        }

        $company = Company::query()->find($companyId);
        if ($company && filled($company->logo_dark_blob)) {
            $binary = base64_decode((string) $company->logo_dark_blob, true);
            if ($binary !== false && $binary !== '') {
                return [
                    'data' => $binary,
                    'mime' => (string) ($company->logo_dark_mime_type ?: 'image/png'),
                ];
            }
        }

        $path = GeneralSetting::get('logo_dark', null, $companyId);
        if (is_string($path) && trim($path) !== '') {
            return $this->payloadFromStoragePath($path);
        }

        return null;
    }

    public function resolveEmailLogoMaxHeightPx(?int $companyId): int
    {
        $raw = GeneralSetting::get('logo_size', '56', $companyId && $companyId > 0 ? $companyId : null);
        $px = (int) $raw;

        return max(24, min(100, $px > 0 ? $px : 56));
    }

    public function previewImgHtml(?int $companyId, ?string $fallbackName = null, bool $forAdminPreview = false): string
    {
        $url = $forAdminPreview
            ? $this->adminPreviewLogoUrl($companyId)
            : $this->publicLogoUrl($companyId);

        if ($url !== null) {
            return $this->imgHtmlWithSrc($url, 'Logo', $this->resolveEmailLogoMaxHeightPx($companyId ?? 0));
        }

        return $this->fallbackNameHtml($fallbackName);
    }

    /**
     * Vervang logo-placeholders in admin-preview HTML.
     */
    public function injectPreviewLogoIntoHtml(
        string $html,
        ?int $companyId,
        ?string $fallbackName = null,
        bool $forAdminPreview = true
    ): string {
        if ($html === '') {
            return $html;
        }

        $html = EmailCardHtml::stripRedundantBrandKickerHtml(
            \App\Support\NexaBranding::injectPreviewLogo($html)
        );

        $hasPlaceholder = str_contains($html, 'COMPANY_LOGO')
            || str_contains($html, self::HTML_PLACEHOLDER);

        if (! $hasPlaceholder && ! ($companyId && preg_match('#/email-logo/'.$companyId.'#', $html))) {
            return $html;
        }

        $logoHtml = $this->previewImgHtml($companyId, $fallbackName, $forAdminPreview);

        foreach (['{{ COMPANY_LOGO }}', '{{COMPANY_LOGO}}', '{ COMPANY_LOGO }', '{COMPANY_LOGO}'] as $placeholder) {
            $html = str_replace($placeholder, $logoHtml, $html);
        }

        $html = str_replace(self::HTML_PLACEHOLDER, $logoHtml, $html);

        if ($companyId && $companyId > 0) {
            $freshUrl = $forAdminPreview
                ? $this->adminPreviewLogoUrl($companyId)
                : $this->publicLogoUrl($companyId);
            if (is_string($freshUrl) && $freshUrl !== '') {
                $maxH = $this->resolveEmailLogoMaxHeightPx($companyId);
                $html = preg_replace(
                    '#<img[^>]+src=["\'][^"\']*email-logo/'.$companyId.'[^"\']*["\'][^>]*>#i',
                    $this->imgHtmlWithSrc($freshUrl, 'Logo', $maxH),
                    $html
                ) ?? $html;
            }
        }

        if (EmailCardHtml::htmlHasLogoBrandMark($html) || trim(strip_tags($logoHtml)) !== '') {
            $html = EmailCardHtml::stripCompanyNameKickers($html);
        }

        return $html;
    }

    public function hasLogoSource(?int $companyId): bool
    {
        return $this->resolveLogoPayload($companyId) !== null
            || $this->resolveDarkLogoPayload($companyId) !== null;
    }

    private function withDarkVariantQuery(string $url, int $companyId, bool $preferDarkHeader): string
    {
        if (! $preferDarkHeader || $this->resolveDarkLogoPayload($companyId) === null) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'variant=dark';
    }

    /**
     * @return array{data: string, mime: string}|null
     */
    public function resolveLogoPayload(?int $companyId): ?array
    {
        if ($companyId !== null && $companyId > 0) {
            $company = Company::query()->find($companyId);
            if ($company) {
                $fromBlob = $this->payloadFromCompanyBlob($company);
                if ($fromBlob !== null) {
                    return $fromBlob;
                }

                $logoPath = $company->logo_path;
                if (is_string($logoPath) && trim($logoPath) !== '') {
                    $fromPath = $this->payloadFromStoragePath($logoPath);
                    if ($fromPath !== null) {
                        return $fromPath;
                    }
                }
            }

            $fromSettings = $this->payloadFromGeneralSettings($companyId);
            if ($fromSettings !== null) {
                return $fromSettings;
            }

            return null;
        }

        return $this->payloadFromGeneralSettings(null);
    }

    public function fallbackNameHtml(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        // Brand stand-in when no logo: larger and clearer in the dark email header.
        return '<p style="margin:0 0 10px;color:#ffffff;font-size:22px;line-height:1.25;font-weight:700;letter-spacing:0.01em;">'
            .e($name)
            .'</p>';
    }

    public function imgHtmlWithSrc(string $src, ?string $alt = null, ?int $maxHeightPx = null): string
    {
        $altText = trim((string) ($alt ?? 'Logo'));
        $maxH = max(24, min(100, $maxHeightPx ?? 56));
        $maxW = (int) round($maxH * 4.5);

        return '<img src="'.e($src).'" alt="'.e($altText).'" style="max-height:'.$maxH.'px;max-width:'.$maxW.'px;width:auto;height:auto;display:block;object-fit:contain;">';
    }

    /**
     * @return array{data: string, mime: string}|null
     */
    private function payloadFromGeneralSettings(?int $companyId): ?array
    {
        $path = GeneralSetting::get('logo', null, $companyId);
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return $this->payloadFromStoragePath($path);
    }

    /**
     * @return array{data: string, mime: string}|null
     */
    private function payloadFromStoragePath(string $path): ?array
    {
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $data = Storage::disk('public')->get($path);
        if ($data === '' || $data === null) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: $this->guessMimeFromPath($path);

        return ['data' => $data, 'mime' => $mime];
    }

    /**
     * @return array{data: string, mime: string}|null
     */
    private function payloadFromCompanyBlob(Company $company): ?array
    {
        if (! $company->logo_blob || ! $company->logo_mime_type) {
            return null;
        }

        $binary = base64_decode($company->logo_blob, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        return [
            'data' => $binary,
            'mime' => $company->logo_mime_type,
        ];
    }

    private function guessMimeFromPath(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }

    private function resolvePublicBaseUrl(int $companyId): string
    {
        $company = Company::query()->with('domains')->find($companyId);
        $host = null;
        if ($company) {
            $primary = $company->domains->firstWhere('is_primary', true);
            $host = $primary?->host ?? $company->domains->sortBy('id')->first()?->host;
            if (is_string($host) && $host !== '') {
                $host = CompanyDomain::normalizeHost($host);
            } else {
                $host = null;
            }
        }

        if ($host === null) {
            foreach (config('tenancy.dev_host_company_map', []) as $mapHost => $mapCompanyId) {
                if ((int) $mapCompanyId === $companyId) {
                    $host = CompanyDomain::normalizeHost((string) $mapHost);
                    break;
                }
            }
        }

        $appUrl = config('app.url', '');
        $scheme = 'https';
        if (is_string($appUrl) && Str::startsWith($appUrl, 'http://')) {
            $scheme = 'http';
        }

        if ($host !== null && $host !== '') {
            return $scheme.'://'.$host;
        }

        return rtrim((string) $appUrl, '/') ?: url('/');
    }
}
