<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDomain;
use App\Services\TenantConfigAccessService;
use App\Support\TenantConfigCapability;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCompanyDomainController extends Controller
{
    use TenantFilter;

    private function ensureDomainAccess(Company $company): void
    {
        app(TenantConfigAccessService::class)->assertCan(
            auth()->user(),
            $company,
            TenantConfigCapability::DOMAIN,
            'Alleen een super-admin of gebruikers met domein-toegang kunnen tenant-domeinen beheren.'
        );
    }

    public function store(Request $request, Company $company)
    {
        $this->ensureDomainAccess($company);
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

        return $this->domainsJsonOrRedirect($request, $company, 'Domein toegevoegd.');
    }

    public function update(Request $request, Company $company, CompanyDomain $domain)
    {
        $this->ensureDomainAccess($company);
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        if ((int) $domain->company_id !== (int) $company->id) {
            abort(404);
        }

        $request->merge([
            'host' => CompanyDomain::normalizeHost((string) $request->input('host', $domain->host)),
        ]);

        $validated = $request->validate([
            'host' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9.\-]+$/',
                Rule::unique('company_domains', 'host')->ignore($domain->id),
            ],
        ], [
            'host.regex' => 'Voer een geldige hostnaam in (alleen letters, cijfers, punten en koppeltekens; geen poort).',
            'host.unique' => 'Deze hostnaam is al gekoppeld aan een bedrijf.',
        ]);

        $domain->update(['host' => $validated['host']]);

        return $this->domainsJsonOrRedirect($request, $company, 'Domein bijgewerkt.');
    }

    public function destroy(Request $request, Company $company, CompanyDomain $domain)
    {
        $this->ensureDomainAccess($company);
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        if ((int) $domain->company_id !== (int) $company->id) {
            abort(404);
        }

        $wasPrimary = (bool) $domain->is_primary;
        $domain->delete();

        if ($wasPrimary) {
            $this->ensurePrimaryDomain($company);
        }

        return $this->domainsJsonOrRedirect($request, $company, 'Domein verwijderd.');
    }

    public function setPrimary(Request $request, Company $company, CompanyDomain $domain)
    {
        $this->ensureDomainAccess($company);
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        if ((int) $domain->company_id !== (int) $company->id) {
            abort(404);
        }

        // Explicit clear (primair uitzetten): bij meerdere domeinen een ander primair maken.
        if ($request->boolean('clear') || $request->input('is_primary') === '0' || $request->input('is_primary') === 0) {
            if ($domain->is_primary) {
                $domain->update(['is_primary' => false]);
                $this->ensurePrimaryDomain($company, excludeId: (int) $domain->id);
                // Alleen-domein: blijft primair via ensurePrimaryDomain.
            }

            return $this->domainsJsonOrRedirect($request, $company, 'Primair domein bijgewerkt.');
        }

        CompanyDomain::query()->where('company_id', $company->id)->update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);

        return $this->domainsJsonOrRedirect($request, $company, 'Primair domein ingesteld.');
    }

    /**
     * Zorgt dat er altijd een primair domein is zolang er domeinen bestaan.
     */
    private function ensurePrimaryDomain(Company $company, ?int $excludeId = null): void
    {
        $company->refresh();
        $query = CompanyDomain::query()->where('company_id', $company->id);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if (! $query->clone()->where('is_primary', true)->exists()) {
            $next = $query->orderBy('id')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        // Als clear op het enige domein werd gedaan: zet het weer primair.
        if ($excludeId !== null && ! CompanyDomain::query()->where('company_id', $company->id)->where('is_primary', true)->exists()) {
            $only = CompanyDomain::query()->where('company_id', $company->id)->whereKey($excludeId)->first();
            $only?->update(['is_primary' => true]);
        }
    }

    private function domainsJsonOrRedirect(Request $request, Company $company, string $message)
    {
        $company->refresh();
        $company->load('domains');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'tbody_html' => view('admin.companies.partials.domain-table-rows', ['company' => $company])->render(),
                'has_domains' => $company->domains->isNotEmpty(),
            ]);
        }

        $redirect = $request->headers->get('referer');
        if (is_string($redirect) && $redirect !== '') {
            return redirect()->to($redirect)->with('success', $message);
        }

        return redirect()->route('admin.companies.show', $company)
            ->with('success', $message);
    }
}
