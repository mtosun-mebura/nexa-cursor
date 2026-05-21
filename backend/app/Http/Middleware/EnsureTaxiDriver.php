<?php

namespace App\Http\Middleware;

use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Support\TaxiDriverAccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaxiDriver
{
    public function __construct(
        protected TaxiDriverEligibilityService $eligibility
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $companyId = (int) ($request->header('X-Company-Id') ?: $user->company_id);
        if ($companyId <= 0) {
            return response()->json(['message' => 'Geen bedrijf gekoppeld aan dit account.'], 403);
        }

        if (! $this->eligibility->isChauffeurForCompany($user, $companyId)) {
            return response()->json(['message' => 'Geen chauffeur-toegang voor dit bedrijf.'], 403);
        }

        if (! TaxiDriverAccountStatus::isActive($user)) {
            return TaxiDriverAccountStatus::inactiveResponse();
        }

        $request->attributes->set('taxi_company_id', $companyId);

        return $next($request);
    }
}
