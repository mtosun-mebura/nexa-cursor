<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TenantWebsiteBundleService;
use Illuminate\Http\Request;

class AdminTenantWebsiteBundleController extends Controller
{
    use TenantFilter;

    public function __construct(
        protected TenantWebsiteBundleService $bundles
    ) {}

    public function export(Company $company)
    {
        $this->authorizeBundle($company);

        return $this->bundles->exportZip($company);
    }

    public function import(Request $request, Company $company)
    {
        $this->authorizeBundle($company);

        $request->validate([
            'bundle' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ]);

        $result = $this->bundles->importZip($company, $request->file('bundle'));

        return redirect()->route('admin.companies.show', $company)
            ->with('success', __('Import voltooid: :pages pagina\'s, :files bestand(en) gekopieerd naar storage/app/public.', ['pages' => $result['imported_pages'], 'files' => $result['copied_files']]));
    }

    private function authorizeBundle(Company $company): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403, __('Alleen super-admins kunnen website-bundels exporteren of importeren.'));
        }
        if (! $this->canAccessResource($company)) {
            abort(403, __('Je hebt geen toegang tot dit bedrijf.'));
        }
    }
}
