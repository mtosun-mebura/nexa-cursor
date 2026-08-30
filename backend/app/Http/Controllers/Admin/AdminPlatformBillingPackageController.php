<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformBillingPackage;
use Illuminate\Http\RedirectResponse;

class AdminPlatformBillingPackageController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function create(): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function store(): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function show(PlatformBillingPackage $package): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function edit(PlatformBillingPackage $package): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function update(PlatformBillingPackage $package): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function destroy(PlatformBillingPackage $package): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function bulkDestroy(): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    public function toggleStatus(PlatformBillingPackage $package): RedirectResponse
    {
        return $this->redirectToPricing();
    }

    private function redirectToPricing(): RedirectResponse
    {
        $this->ensureSuperAdmin();

        return redirect()->route('admin.nexa-pricing.edit');
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}
