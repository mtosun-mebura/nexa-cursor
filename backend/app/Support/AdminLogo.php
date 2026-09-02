<?php

namespace App\Support;

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AdminLogo
{
    /**
     * Logo URLs for admin UI (mirrors sidebar priority: settings, company, default).
     *
     * @return array{
     *     source: 'settings'|'company'|'default',
     *     light_url: string,
     *     dark_url: string,
     *     alt: string,
     * }
     */
    public static function displayUrls(?User $user = null): array
    {
        $user ??= auth()->user();
        $fallbackLight = NexaBranding::defaultLogoUrl();
        $fallbackDark = NexaBranding::defaultLogoDarkUrl();

        $settingsLogo = trim((string) (GeneralSetting::get('logo') ?? ''));
        if ($settingsLogo === '') {
            $settingsLogo = trim((string) (GeneralSetting::query()
                ->where('key', 'logo')
                ->whereNull('company_id')
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->orderByDesc('id')
                ->value('value') ?? ''));
        }
        $hasSettingsLogo = $settingsLogo !== '' && Storage::disk('public')->exists($settingsLogo);

        if ($hasSettingsLogo) {
            $settingsLogoMode = GeneralSetting::get('logo_mode', 'single');
            $settingsLogoDark = GeneralSetting::get('logo_dark');
            $hasSettingsLogoDark = $settingsLogoDark && Storage::disk('public')->exists($settingsLogoDark);
            $logoLightUrl = route('admin.settings.logo');
            $logoDarkUrl = ($settingsLogoMode === 'light_dark' && $hasSettingsLogoDark)
                ? route('admin.settings.logo-dark')
                : $logoLightUrl;

            return [
                'source' => 'settings',
                'light_url' => $logoLightUrl,
                'dark_url' => $logoDarkUrl,
                'alt' => 'Logo',
            ];
        }

        $company = $user?->company;
        if ($company && $company->logo_blob) {
            $logoLightUrl = route('admin.companies.logo', $company);
            $logoDarkUrl = ! empty($company->logo_dark_blob)
                ? route('admin.companies.logo.dark', $company)
                : $logoLightUrl;

            return [
                'source' => 'company',
                'light_url' => $logoLightUrl,
                'dark_url' => $logoDarkUrl,
                'alt' => (string) $company->name,
            ];
        }

        return [
            'source' => 'default',
            'light_url' => $fallbackLight,
            'dark_url' => $fallbackDark,
            'alt' => 'NEXA Suite',
        ];
    }

    /** Logo URL for invoice preview/PDF (white background → light variant). */
    public static function invoicePreviewUrl(?User $user = null): string
    {
        return self::displayUrls($user)['light_url'];
    }

    /** Embedded logo for PDF generation. */
    public static function invoicePdfDataUri(?User $user = null): ?string
    {
        $settingsLogo = trim((string) (GeneralSetting::get('logo') ?? ''));
        if ($settingsLogo === '') {
            $settingsLogo = trim((string) (GeneralSetting::query()
                ->where('key', 'logo')
                ->whereNull('company_id')
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->orderByDesc('id')
                ->value('value') ?? ''));
        }
        if ($settingsLogo !== '' && Storage::disk('public')->exists($settingsLogo)) {
            return self::storagePathToDataUri($settingsLogo);
        }

        $user ??= auth()->user();
        $company = $user?->company;
        if ($company && $company->logo_blob) {
            $mime = (string) ($company->logo_mime_type ?: 'image/png');

            return 'data:'.$mime.';base64,'.$company->logo_blob;
        }

        return NexaBranding::defaultLogoDataUri();
    }

    private static function storagePathToDataUri(string $storagePath): ?string
    {
        if (! Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        $binary = Storage::disk('public')->get($storagePath);
        $mime = Storage::disk('public')->mimeType($storagePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
