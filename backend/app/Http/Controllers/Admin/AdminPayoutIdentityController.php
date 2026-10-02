<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayoutIdentity;
use App\Services\Payout\PayoutIdentityService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminPayoutIdentityController extends Controller
{
    use TenantFilter;

    public function __construct(
        private readonly PayoutIdentityService $payouts
    ) {}

    public function index(Request $request)
    {
        $this->authorizeView();

        $company = $this->resolveCompany($request);
        $identity = null;
        if ($company) {
            $identity = $this->payouts->forCompany($company)
                ?? $this->payouts->bootstrapCompanyIdentity($company, $request->user(), $request);
        }

        return view('admin.payout-identities.index', [
            'company' => $company,
            'identity' => $identity,
            'payload' => $identity ? $this->payouts->publicPayload($identity) : null,
            'allowIndependentDriverPayouts' => (bool) config('nexa_payout.allow_independent_driver_payouts'),
            'coolingOffHours' => (int) config('nexa_payout.destination_change_cooling_off_hours', 48),
        ]);
    }

    public function ensureCompany(Request $request)
    {
        $this->authorizeManage();
        $company = $this->requireCompany($request);

        try {
            if ($request->filled('iban')) {
                $this->payouts->setCompanyBankAccount(
                    $company,
                    $request->user(),
                    (string) $request->input('iban'),
                    $request->input('password'),
                    $request
                );
            } else {
                $safe = $request->except(['iban', 'password', '_token']);
                $this->payouts->rejectSecretFields($safe);
                $this->payouts->ensureCompanyIdentity(
                    $company,
                    $request->user(),
                    $request->only(['provider_organization_id', 'provider_account_id', 'masked_destination']),
                    $request
                );
            }
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('admin.payout-identities.index')
            ->with('success', 'Payout-onboarding voor het bedrijf is gestart of bijgewerkt.');
    }

    public function setBankAccount(Request $request)
    {
        $this->authorizeManage();
        $company = $this->requireCompany($request);

        $data = $request->validate([
            'iban' => ['required', 'string', 'max:42'],
            'password' => ['nullable', 'string'],
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
            ? 'Wijziging aangevraagd. Het nieuwe rekeningnummer wordt pas over '.(int) config('nexa_payout.destination_change_cooling_off_hours', 48).' uur gebruikt voor uitbetalingen. We bewaren alleen *** + laatste 4 cijfers.'
            : 'Bankrekening opgeslagen. Alleen *** + laatste 4 cijfers worden bewaard.';

        return redirect()
            ->route('admin.payout-identities.index')
            ->with('success', $msg);
    }

    public function sync(Request $request, PayoutIdentity $payoutIdentity)
    {
        $this->authorizeManage();
        $this->assertCanAccessIdentity($payoutIdentity);

        $identity = $this->payouts->syncFromProvider($payoutIdentity, $request->user(), $request);

        return redirect()
            ->route('admin.payout-identities.index')
            ->with('success', 'Status gesynchroniseerd: '.$identity->capability_status);
    }

    public function requestDestinationChange(Request $request, PayoutIdentity $payoutIdentity)
    {
        $this->authorizeManage();
        $this->assertCanAccessIdentity($payoutIdentity);

        $data = $request->validate([
            'provider_account_id' => ['required', 'string', 'max:128'],
            'masked_destination' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        try {
            $this->payouts->rejectSecretFields($request->all());
            $identity = $this->payouts->requestDestinationChange($payoutIdentity, $request->user(), $data, $request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('admin.payout-identities.index')
            ->with('success', 'Bestemmingswijziging aangevraagd. Cooling-off tot '.$identity->destination_change_eligible_at?->timezone(config('app.timezone'))->format('d-m-Y H:i').'.');
    }

    public function applyDestinationChange(Request $request, PayoutIdentity $payoutIdentity)
    {
        $this->authorizeManage();
        $this->assertCanAccessIdentity($payoutIdentity);

        try {
            $this->payouts->applyPendingDestinationChange($payoutIdentity, $request->user(), $request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('admin.payout-identities.index')
            ->with('success', 'Nieuwe payout-bestemming is doorgevoerd (nog pending provider-verificatie).');
    }

    public function disable(Request $request, PayoutIdentity $payoutIdentity)
    {
        $this->authorizeManage();
        $this->assertCanAccessIdentity($payoutIdentity);

        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        try {
            $this->payouts->disable($payoutIdentity, $request->user(), $data['password'], $request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('admin.payout-identities.index')
            ->with('success', 'Payout-identiteit is uitgeschakeld.');
    }

    public function ensureIndependentDriver(Request $request)
    {
        $this->authorizeManage();
        $company = $this->requireCompany($request);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $driver = \App\Models\User::query()->findOrFail($data['user_id']);

        try {
            $this->payouts->ensureIndependentDriverIdentity($driver, $company, $request->user(), $request->all(), $request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('error', 'Onverwachte status.');
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        if (
            $user->hasRole('super-admin')
            || $user->isTenantAdmin()
            || $user->can('view-payment-providers')
        ) {
            return;
        }

        abort(403, 'Geen rechten om payout-identiteiten te bekijken.');
    }

    private function authorizeManage(): void
    {
        $user = auth()->user();
        if (
            $user->hasRole('super-admin')
            || $user->isTenantAdmin()
            || $user->can('edit-payment-providers')
        ) {
            return;
        }

        abort(403, 'Geen rechten om payout-identiteiten te beheren.');
    }

    private function resolveCompany(Request $request): ?Company
    {
        $companyId = $this->getTenantId();
        if ($companyId) {
            return Company::query()->find($companyId);
        }

        if (auth()->user()->hasRole('super-admin') && $request->filled('company_id')) {
            return Company::query()->find((int) $request->integer('company_id'));
        }

        return null;
    }

    private function requireCompany(Request $request): Company
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            abort(422, 'Selecteer eerst een tenant/bedrijf.');
        }

        return $company;
    }

    private function assertCanAccessIdentity(PayoutIdentity $identity): void
    {
        $tenantId = $this->getTenantId();
        if ($tenantId && (int) $identity->company_id !== (int) $tenantId) {
            abort(403, 'Geen toegang tot deze payout-identiteit.');
        }
    }
}
