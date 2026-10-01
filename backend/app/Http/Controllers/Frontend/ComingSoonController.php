<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralSetting;
use App\Services\WebsiteBuilderService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Neutrale "Coming soon"-pagina voor tenants zonder actieve homepage.
 * Teksten en afbeelding zijn configureerbaar in admin (Front-end configuraties).
 */
class ComingSoonController extends Controller
{
    /**
     * @return array<string, string>
     */
    public static function defaults(?Company $company = null): array
    {
        $name = trim((string) ($company?->name ?? ''));
        if ($name === '') {
            $name = trim((string) GeneralSetting::get('site_name', config('app.name', 'NEXA')));
        }
        if ($name === '') {
            $name = 'uw bedrijf';
        }

        return [
            'coming_soon_title' => 'Website in voorbereiding',
            'coming_soon_text' => 'Hier komt binnenkort de website van '.$name.'. We werken aan een overzichtelijke en professionele online aanwezigheid.',
            'coming_soon_secondary_text' => 'Heeft u vragen? Neem gerust contact met ons op.',
            'coming_soon_show_email' => '1',
            'coming_soon_contact_email' => trim((string) ($company?->email ?? '')),
            'coming_soon_contact_label' => 'E-mail',
            'coming_soon_footer_text' => '© {year} {site}. Website binnenkort beschikbaar.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSettings(): array
    {
        $company = static::resolvedCompany();
        $defaults = static::defaults($company);
        $settings = [];
        foreach (array_keys($defaults) as $key) {
            $settings[$key] = GeneralSetting::get($key, $defaults[$key]);
        }

        if (trim((string) ($settings['coming_soon_contact_email'] ?? '')) === '' && $company?->email) {
            $settings['coming_soon_contact_email'] = trim((string) $company->email);
        }

        $settings['site_name'] = $company?->name
            ?: GeneralSetting::get('site_name', config('app.name', 'NEXA'));

        // Coming Soon: eerst tenantlogo, anders NEXA Suite dark.
        $settings['logo_url'] = static::resolveComingSoonLogoUrl($company);

        $settings['favicon_url'] = null;
        if ($company && $company->hasFavicon()) {
            $settings['favicon_url'] = $company->publicBrandFaviconUrl();
        } else {
            $faviconPath = GeneralSetting::get('favicon');
            if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
                $settings['favicon_url'] = app(WebsiteBuilderService::class)->publicFileUrl(ltrim($faviconPath, '/'));
            }
        }

        $comingSoonImagePath = GeneralSetting::get('coming_soon_image');
        $settings['coming_soon_image_url'] = null;
        if ($comingSoonImagePath && Storage::disk('public')->exists($comingSoonImagePath)) {
            $settings['coming_soon_image_url'] = app(WebsiteBuilderService::class)->publicFileUrl(ltrim($comingSoonImagePath, '/'));
        }

        return $settings;
    }

    public function index(): View
    {
        $settings = static::getSettings();

        return view('frontend.coming-soon', [
            'settings' => $settings,
            'showEmail' => ! empty($settings['coming_soon_show_email']) && $settings['coming_soon_show_email'] !== '0',
            'contactEmail' => $settings['coming_soon_contact_email'] ?? '',
            'adminPreviewReturnUrl' => session('website_preview_admin_url'),
        ]);
    }

    protected static function resolvedCompany(): ?Company
    {
        if (app()->bound('resolved_tenant')) {
            $tenant = app('resolved_tenant');
            if ($tenant instanceof Company) {
                return $tenant;
            }
        }

        $id = GeneralSetting::resolveScopeCompanyId();
        if ($id === null || $id <= 0) {
            try {
                $st = session('selected_tenant');
                if ($st !== null && $st !== '' && is_numeric($st)) {
                    $id = (int) $st;
                }
            } catch (\Throwable) {
                $id = null;
            }
        }

        if ($id === null || $id <= 0) {
            return null;
        }

        return Company::query()->find($id);
    }

    protected static function resolveComingSoonLogoUrl(?Company $company): string
    {
        // 1) Tenantlogo (dark variant bij voorkeur, anders light)
        if ($company && $company->hasAdminLogo()) {
            return $company->publicBrandLogoUrl(filled($company->logo_dark_blob));
        }

        // 2) Geen tenantlogo → NEXA Suite dark (past bij Coming Soon-achtergrond)
        return \App\Support\NexaBranding::defaultLogoDarkUrl();
    }
}
