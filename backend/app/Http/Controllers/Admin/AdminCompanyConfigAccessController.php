<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\TenantConfigAccessNotifier;
use App\Services\TenantConfigAccessService;
use App\Support\TenantConfigCapability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminCompanyConfigAccessController extends Controller
{
    use TenantFilter;

    public function __construct(
        protected TenantConfigAccessService $configAccess,
        protected TenantConfigAccessNotifier $notifier,
    ) {}

    public function update(Request $request, Company $company): RedirectResponse
    {
        if (! auth()->user()?->isSuperAdmin()) {
            abort(403, 'Alleen een super-admin kan configuratie-toegang beheren.');
        }

        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $validKeys = TenantConfigCapability::keys();
        $request->validate([
            'grants' => 'nullable|array',
            'grants.*' => 'array',
            'grants.*.*' => 'string|in:'.implode(',', $validKeys),
        ]);

        $posted = $request->input('grants', []);
        if (! is_array($posted)) {
            $posted = [];
        }

        $users = User::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->get();

        $grantedBy = auth()->user();
        foreach ($users as $user) {
            $raw = $posted[$user->id] ?? [];
            $keys = is_array($raw) ? array_values($raw) : [];
            $added = $this->configAccess->syncUserGrants($company, $user, $keys, $grantedBy);
            if ($added !== [] && $grantedBy instanceof User) {
                $this->notifier->notifyGranted($company, $user, $added, $grantedBy);
            }
        }

        $redirect = $request->input('redirect_to');
        if (is_string($redirect) && $redirect !== '' && str_starts_with($redirect, '/')) {
            return redirect($redirect)->with('success', 'Configuratie-toegang opgeslagen.');
        }

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', 'Configuratie-toegang opgeslagen.');
    }
}
