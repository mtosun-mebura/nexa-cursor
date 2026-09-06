<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\PlatformBilling\TenantSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SaasTrialStopController extends Controller
{
    public function show(Request $request, Company $company, TenantSubscriptionService $subscriptions): View
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Deze link is ongeldig of verlopen.');
        }

        $profile = $subscriptions->ensureProfile($company);
        if (! $subscriptions->isInTrial($profile)) {
            return view('frontend.saas-trial.expired', [
                'company' => $company,
            ]);
        }

        $request->session()->put('saas_trial_stop_company_id', $company->id);

        return view('frontend.saas-trial.stop', [
            'company' => $company,
            'trialEndsAt' => $profile->trial_ends_at,
            'daysRemaining' => $subscriptions->trialDaysRemaining($profile),
        ]);
    }

    public function store(Request $request, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $companyId = (int) $request->session()->pull('saas_trial_stop_company_id', 0);
        $company = Company::query()->find($companyId);
        if (! $company) {
            abort(403, 'Deze actie is niet meer geldig.');
        }

        try {
            $profile = $subscriptions->endTrialAndDeactivate($company);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('saas.trial.stopped')
                ->with('error', $e->getMessage());
        }

        $user = $request->user();
        if (
            $user
            && (int) $user->company_id === (int) $company->id
            && $user->hasRole('company-admin')
            && ! $user->hasRole('super-admin')
        ) {
            return redirect()->route('admin.subscriptions.show')
                ->with('trial_stopped', true);
        }

        return redirect()->route('saas.trial.stopped')->with([
            'trial_ends_at' => $profile->trial_ends_at,
            'start_date' => $subscriptions->contractStart($profile),
        ]);
    }

    public function stopped(): View
    {
        return view('frontend.saas-trial.stopped');
    }
}
