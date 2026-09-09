<?php

namespace App\Support;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AdminLogo
{
    /**
     * Logo URLs for admin UI (tenant in zijbalk, anders settings, anders eigen bedrijf, anders default).
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

        $selectedTenantId = (int) session('selected_tenant');
        if ($selectedTenantId > 0) {
            $fromTenant = self::urlsFromCompany(Company::query()->find($selectedTenantId));
            if ($fromTenant !== null) {
                return $fromTenant;
            }
        }

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

        $fromUserCompany = self::urlsFromCompany($user?->company);
        if ($fromUserCompany !== null) {
            return $fromUserCompany;
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

    public static function userCanViewCompanyLogo(?User $user, Company $company): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->can('view-companies')) {
            return true;
        }

        return (int) $user->company_id === (int) $company->id;
    }

    /**
     * @return array{source: 'company', light_url: string, dark_url: string, alt: string}|null
     */
    private static function urlsFromCompany(?Company $company): ?array
    {
        if (! $company?->hasAdminLogo()) {
            return null;
        }

        return [
            'source' => 'company',
            'light_url' => (string) $company->adminLogoLightUrl(),
            'dark_url' => (string) $company->adminLogoDarkUrl(),
            'alt' => (string) $company->name,
        ];
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
