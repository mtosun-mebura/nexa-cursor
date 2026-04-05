<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDomain;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCompanyDomainController extends Controller
{
    use TenantFilter;

    public function store(Request $request, Company $company)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om dit te wijzigen.');
        }
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $request->merge([
            'host' => CompanyDomain::normalizeHost((string) $request->input('host', '')),
        ]);

        $validated = $request->validate([
            'host' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9.\-]+$/',
                Rule::unique('company_domains', 'host'),
            ],
            'is_primary' => 'sometimes|boolean',
        ], [
            'host.regex' => 'Voer een geldige hostnaam in (alleen letters, cijfers, punten en koppeltekens; geen poort).',
            'host.unique' => 'Deze hostnaam is al gekoppeld aan een bedrijf.',
        ]);

        $isPrimary = ! empty($validated['is_primary']);

        if ($isPrimary) {
            CompanyDomain::query()->where('company_id', $company->id)->update(['is_primary' => false]);
        }
        if (! $company->domains()->exists()) {
            $isPrimary = true;
        }

        $company->domains()->create([
            'host' => $validated['host'],
            'is_primary' => $isPrimary,
        ]);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Domein toegevoegd.');
    }

    public function destroy(Company $company, CompanyDomain $domain)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om dit te wijzigen.');
        }
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        if ((int) $domain->company_id !== (int) $company->id) {
            abort(404);
        }

        $domain->delete();

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Domein verwijderd.');
    }

    public function setPrimary(Company $company, CompanyDomain $domain)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om dit te wijzigen.');
        }
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        if ((int) $domain->company_id !== (int) $company->id) {
            abort(404);
        }

        CompanyDomain::query()->where('company_id', $company->id)->update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Primair domein ingesteld.');
    }
}
