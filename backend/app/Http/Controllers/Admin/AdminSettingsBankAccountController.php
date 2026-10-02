<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Rules\ValidIban;
use App\Services\AdminFirstLoginService;
use App\Services\MarketplaceCompanyRegistrationService;
use App\Services\Payout\PayoutIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bankrekening voor uitbetaling (Marketplace) onder Configuraties.
 */
class AdminSettingsBankAccountController extends Controller
{
    use TenantFilter;

    public function __construct(
        private readonly PayoutIdentityService $payouts
    ) {}

    public function edit(Request $request): View
    {
        $company = $this->requireMarketplaceCompany($request);
        $identity = $this->payouts->forCompany($company)
            ?? $this->payouts->bootstrapCompanyIdentity($company, $request->user(), $request);

        return view('admin.settings.bank-account', [
            'company' => $company,
            'identity' => $identity,
            'payload' => $this->payouts->publicPayload($identity),
            'coolingOffHours' => (int) config('nexa_payout.destination_change_cooling_off_hours', 48),
            'authViaCode' => (bool) $request->session()->get('admin_auth_via_code'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->requireMarketplaceCompany($request);

        $data = $request->validate([
            'iban' => ['required', 'string', 'max:42', new ValidIban],
            'password' => ['nullable', 'string'],
            'confirmation_code' => ['nullable', 'string', 'max:12'],
        ], [
            'iban.required' => 'Vul je IBAN in voor uitbetaling van ritten.',
        ]);

        try {
            $identity = $this->payouts->setCompanyBankAccount(
                $company,
                $request->user(),
                $data['iban'],
                $data['password'] ?? null,
                $request
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $msg = $identity->hasPendingDestinationChange()
            ? 'Wijziging aangevraagd. Het nieuwe rekeningnummer wordt pas over '.$this->coolingOffHoursLabel().' gebruikt voor uitbetalingen. We bewaren alleen *** + laatste 4 cijfers.'
            : 'Bankrekening opgeslagen. Uitbetalingen (na fee) gaan naar dit rekeningnummer.';

        return redirect()
            ->route('admin.settings.bank-account', ['saved' => 1])
            ->with('success', $msg);
    }

    public function sendConfirmationCode(Request $request, AdminFirstLoginService $firstLogin): JsonResponse
    {
        $this->requireMarketplaceCompany($request);
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $result = $firstLogin->requestStepUpCode($user, (string) $request->ip());

        return response()->json(
            array_filter([
                'message' => $result['message'],
                'retry_after' => $result['retry_after'] ?? null,
            ], static fn ($value) => $value !== null),
            $result['status']
        );
    }

    private function requireMarketplaceCompany(Request $request): Company
    {
        $user = $request->user();
        if (! $user || (! $user->isSuperAdmin() && ! $user->isTenantAdmin())) {
            abort(403, 'Geen rechten om de bankrekening te beheren.');
        }

        $companyId = $this->getTenantId();
        if (! $companyId && $user->company_id) {
            $companyId = (int) $user->company_id;
        }
        $company = $companyId ? Company::query()->find($companyId) : null;
        if (! $company) {
            abort(404, 'Geen bedrijf gevonden. Selecteer een tenant.');
        }

        if (strcasecmp((string) ($company->package_key ?? ''), MarketplaceCompanyRegistrationService::PACKAGE_KEY) !== 0
            && ! $user->isSuperAdmin()) {
            abort(403, 'Deze pagina is voor Marketplace-taxibedrijven.');
        }

        return $company;
    }

    private function coolingOffHoursLabel(): string
    {
        $hours = max(1, (int) config('nexa_payout.destination_change_cooling_off_hours', 48));

        return $hours.' uur';
    }
}
