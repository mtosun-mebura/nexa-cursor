<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    use TenantFilter;
    
    public function index()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }
        
        $query = Company::withCount(['users', 'vacancies']);
        $this->applyTenantFilter($query);
        $companies = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }
        
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'kvk_number' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $companyData = $request->all();
        Company::create($companyData);

        return redirect()->route('admin.companies.index')
            ->with('success', 'Bedrijf succesvol aangemaakt.');
    }

    public function show(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        $company->load(['users', 'vacancies.category']);
        
        return view('admin.companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'kvk_number' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $company->update($request->all());

        return redirect()->route('admin.companies.index')
            ->with('success', 'Bedrijf succesvol bijgewerkt.');
    }

    public function destroy(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        if ($company->users()->count() > 0) {
            return back()->with('error', 'Kan bedrijf niet verwijderen: er zijn nog gebruikers gekoppeld.');
        }

        if ($company->vacancies()->count() > 0) {
            return back()->with('error', 'Kan bedrijf niet verwijderen: er zijn nog vacatures gekoppeld.');
        }

        $company->delete();

        return redirect()->route('admin.companies.index')
            ->with('success', 'Bedrijf succesvol verwijderd.');
    }
}
