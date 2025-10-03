<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }
        
        $query = Company::withCount(['users', 'vacancies']);
        $this->applyTenantFilter($query);
        
        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        if ($request->filled('intermediary')) {
            if ($request->intermediary === 'yes') {
                $query->where('is_intermediary', true);
            } elseif ($request->intermediary === 'no') {
                $query->where('is_intermediary', false);
            }
        }
        
        if ($request->filled('industry')) {
            $query->where('industry', $request->industry);
        }
        
        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortBy, ['name', 'created_at', 'is_active'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $perPage = $request->get('per_page', 25);
        $companies = $query->paginate($perPage);

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
            'is_intermediary' => 'nullable|boolean',
        ]);

        $companyData = $request->all();
        
        // Handle checkbox - if not present in request, set to false
        $companyData['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        
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
            'is_intermediary' => 'nullable|boolean',
        ]);

        $data = $request->all();
        
        // Handle checkbox - if not present in request, set to false
        $data['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        
        $company->update($data);

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

    public function toggleStatus(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        $company->update(['is_active' => !$company->is_active]);
        
        $status = $company->is_active ? 'geactiveerd' : 'gedeactiveerd';
        
        return redirect()->route('admin.companies.index')
            ->with('success', "Bedrijf '{$company->name}' is succesvol {$status}.");
    }
}
