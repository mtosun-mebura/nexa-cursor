<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyEmailLogoService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publiek bedrijfslogo voor e-mailclients (geen auth; alleen logo-bytes).
 */
class EmailCompanyLogoController extends Controller
{
    public function __invoke(Company $company, CompanyEmailLogoService $logos): Response
    {
        $payload = $logos->resolveLogoPayload((int) $company->id);
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
