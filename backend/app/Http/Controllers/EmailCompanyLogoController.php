<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyEmailLogoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publiek bedrijfslogo voor e-mailclients (geen auth; alleen logo-bytes).
 */
class EmailCompanyLogoController extends Controller
{
    public function __invoke(Request $request, Company $company, CompanyEmailLogoService $logos): Response
    {
        $companyId = (int) $company->id;
        $payload = $request->query('variant') === 'dark'
            ? ($logos->resolveDarkLogoPayload($companyId) ?? $logos->resolveLogoPayload($companyId))
            : $logos->resolveLogoPayload($companyId);
        if ($payload === null) {
            abort(404);
        }

        return response($payload['data'], 200, [
            'Content-Type' => $payload['mime'],
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
