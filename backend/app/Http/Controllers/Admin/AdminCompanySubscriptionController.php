<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformBilling\TenantSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AdminCompanySubscriptionController extends Controller
{
    public function show(TenantSubscriptionService $subscriptions): View
    {
        $company = $this->companyAdminCompany();

        return view('admin.subscriptions.show', $subscriptions->snapshot($company));
    }

    public function upgrade(Request $request, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();
        $validated = $request->validate([
            'package_key' => 'required|string|max:80',
        ]);

        try {
            $subscriptions->upgrade($company, $validated['package_key']);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['package_key' => $e->getMessage()]);
        }

        return redirect()->route('admin.subscriptions.show', ['saved' => 1])
            ->with('success', 'Je pakket is per direct geüpgraded. De nieuwe prijs geldt vanaf vandaag en gaat mee in de maandelijkse incasso.');
    }

    public function downgrade(Request $request, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();
        $validated = $request->validate([
            'package_key' => 'required|string|max:80',
        ]);

        try {
            $profile = $subscriptions->scheduleDowngrade($company, $validated['package_key']);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['package_key' => $e->getMessage()]);
        }

        $when = $profile->pending_change_effective_on?->translatedFormat('j F Y') ?? 'het einde van het contract';

        return redirect()->route('admin.subscriptions.show', ['saved' => 1])
            ->with('success', 'Downgrade ingepland per '.$when.'. Tot die datum blijft je huidige pakket en prijs actief; daarna geldt de lagere prijs in de incasso.');
    }

    public function cancel(TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();

        try {
            $profile = $subscriptions->scheduleCancel($company);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['cancel' => $e->getMessage()]);
        }

        $when = $profile->pending_change_effective_on?->translatedFormat('j F Y') ?? 'het einde van het contract';

        return redirect()->route('admin.subscriptions.show', ['saved' => 1])
            ->with('success', 'Opzegging ingepland per '.$when.'. De SEPA-incasso stopt vanaf die datum.');
    }

    public function endTrial(TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();

        try {
            $subscriptions->endTrialAndDeactivate($company);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['trial' => $e->getMessage()]);
        }

        return redirect()->route('admin.subscriptions.show')
            ->with('trial_stopped', true);
    }

    public function cancelAddon(string $addon, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();

        try {
            $result = $subscriptions->cancelPackageAddon($company, $addon);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['addon' => $e->getMessage()]);
        }

        return redirect()->route('admin.subscriptions.show', ['saved' => 1])
            ->with('success', $result['message']);
    }

    public function withdrawAddon(string $addon, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();

        try {
            $subscriptions->withdrawPackageAddonCancel($company, $addon);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['addon' => $e->getMessage()]);
        }

        return redirect()->route('admin.subscriptions.show', ['saved' => 1])
            ->with('success', 'De opzegging van de module is ingetrokken.');
    }

    public function withdraw(TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $company = $this->companyAdminCompany();
        $profile = $subscriptions->ensureProfile($company);
        $wasTrialDecline = $subscriptions->hasDeclinedTrial($profile);
        $startDate = $subscriptions->contractStart($profile);

        try {
            $subscriptions->withdrawPending($company);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.subscriptions.show')
                ->withErrors(['withdraw' => $e->getMessage()]);
        }

        $message = $wasTrialDecline
            ? 'Het abonnement is weer geactiveerd. De ingangsdatum blijft '.$startDate->translatedFormat('j F Y').'.'
            : 'De geplande wijziging is ingetrokken.';

        return redirect()->route('admin.subscriptions.show', ['saved' => 1])
            ->with('success', $message);
    }

    private function companyAdminCompany(): \App\Models\Company
    {
        $user = auth()->user();
        if (! $user?->canManageCompanySubscription()) {
            abort(403, 'Alleen de bedrijfsbeheerder kan het abonnement beheren.');
        }

        $company = $user->company;
        if (! $company) {
            abort(403, 'Geen bedrijf gekoppeld.');
        }

        return $company;
    }
}
