<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\GeneralSetting;
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
        if (! str_contains($html, self::HTML_PLACEHOLDER)) {
            return $html;
        }

        $payload = $this->resolveLogoPayload($companyId);
        if ($payload === null) {
            return str_replace(self::HTML_PLACEHOLDER, $this->fallbackNameHtml($fallbackName), $html);
        }

        $cid = $message->embedData($payload['data'], 'company-logo', $payload['mime']);

        return str_replace(
            self::HTML_PLACEHOLDER,
            $this->imgHtmlWithSrc($cid, 'Logo'),
            $html
        );
    }

    /**
     * Publieke HTTPS-URL voor e-mailclients (tenant-domein indien bekend).
     */
    public function publicLogoUrl(?int $companyId): ?string
    {
        if ($companyId === null || $companyId <= 0) {
            return null;
        }
        if (! $this->hasLogoSource($companyId)) {
            return null;
        }

        $base = $this->resolvePublicBaseUrl($companyId);
        $path = route('email.company-logo', ['company' => $companyId], false);

        return rtrim($base, '/').$path;
    }

    /**
     * Logo-URL op het huidige host (admin-preview op localhost of admin-domein).
     */
    public function adminPreviewLogoUrl(?int $companyId): ?string
    {
        if ($companyId === null || $companyId <= 0 || ! $this->hasLogoSource($companyId)) {
            return null;
        }

        return url(route('email.company-logo', ['company' => $companyId], false));
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

        return $html;
    }

    public function hasLogoSource(?int $companyId): bool
    {
        return $this->resolveLogoPayload($companyId) !== null;
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

        return '<strong>'.e($name).'</strong>';
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
