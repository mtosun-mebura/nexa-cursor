<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Publieke weergave van bedrijfslogo's (wizard / bedrijfsprofiel) voor frontend header/footer.
 * Alleen voor het resolved tenant-domein of ingelogde gebruikers van hetzelfde bedrijf.
 */
class CompanyBrandLogoController extends Controller
{
    public function show(Company $company): Response
    {
        return $this->serve($company, false);
    }

    public function showDark(Company $company): Response
    {
        return $this->serve($company, true);
    }

    private function serve(Company $company, bool $dark): Response
    {
        if (! $this->canView($company)) {
            abort(404);
        }

        $blob = $dark ? $company->logo_dark_blob : $company->logo_blob;
        $mime = $dark ? $company->logo_dark_mime_type : $company->logo_mime_type;

        if (! $blob) {
            if ($dark && $company->logo_blob) {
                $blob = $company->logo_blob;
                $mime = $company->logo_mime_type;
            } else {
                abort(404);
            }
        }

        $content = base64_decode($blob, true);
        if ($content === false) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => $mime ?: 'image/png',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function canView(Company $company): bool
    {
        if (app()->bound('resolved_tenant_id')) {
            $tenantId = app('resolved_tenant_id');
            if ($tenantId !== null && $tenantId !== '' && (int) $tenantId === (int) $company->id) {
                return true;
            }
        }

        $user = Auth::user();
        if ($user && $user->hasRole('super-admin')) {
            return true;
        }
        if ($user && $user->company_id && (int) $user->company_id === (int) $company->id) {
            return true;
        }

        return false;
    }
}
