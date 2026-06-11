<?php

namespace App\Support\Tenancy;

use App\Http\Middleware\ApplyDevSimulatedTenantHost;
use App\Models\CompanyDomain;
use Illuminate\Http\Request;

/**
 * Per-tenant localStorage-key voor frontend light/dark mode (website layout).
 */
final class WebsiteThemeStorage
{
    public const LEGACY_KEY = 'website-theme';

    public const LEGACY_FALLBACK_KEY = 'theme';

    public static function storageKey(?Request $request = null): string
    {
        $scope = self::scopeHost($request);

        return self::LEGACY_KEY.':'.($scope !== '' ? $scope : 'default');
    }

    public static function scopeHost(?Request $request = null): string
    {
        $request = $request ?? request();
        $currentHost = CompanyDomain::normalizeHost($request->getHost());

        if ($currentHost !== '' && ! CentralDomains::isCentral($currentHost)) {
            return $currentHost;
        }

        $tenantHost = TenantFrontendUrl::resolveTenantPublicHost(null, $request);
        if (is_string($tenantHost) && $tenantHost !== '') {
            return $tenantHost;
        }

        if ($request->hasSession()) {
            $sessionHost = $request->session()->get(ApplyDevSimulatedTenantHost::SESSION_DEV_EFFECTIVE_HOST);
            if (is_string($sessionHost) && $sessionHost !== '') {
                $normalized = CompanyDomain::normalizeHost($sessionHost);
                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        return $currentHost;
    }
}
