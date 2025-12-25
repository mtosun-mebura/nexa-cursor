<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }
        
        $query = Company::withCount(['users', 'vacancies'])->with('mainLocation');
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
        
        // Load all companies for client-side pagination (like demo1)
        // The KTDataTable library will handle pagination client-side
        $companies = $query->get();
        
        // Calculate statistics
        $statsQuery = Company::query();
        $this->applyTenantFilter($statsQuery);
        
        $tenantId = $this->getTenantId();
        
        $stats = [
            'total_companies' => (clone $statsQuery)->count(),
            'active_companies' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive_companies' => (clone $statsQuery)->where('is_active', false)->count(),
            'intermediaries' => (clone $statsQuery)->where('is_intermediary', true)->count(),
            'total_users' => $tenantId ? \App\Models\User::where('company_id', $tenantId)->count() : \App\Models\User::count(),
            'total_vacancies' => $tenantId ? \App\Models\Vacancy::where('company_id', $tenantId)->count() : \App\Models\Vacancy::count(),
        ];
        
        // Get unique industries for filter
        $industries = Company::select('industry')
            ->distinct()
            ->whereNotNull('industry')
            ->orderBy('industry')
            ->pluck('industry');

        return view('admin.companies.index', compact('companies', 'stats', 'industries'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }
        
        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        
        return view('admin.companies.create', compact('branches'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => ['nullable', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_intermediary' => 'nullable|boolean',
            'locations' => 'nullable|array',
            'locations.*.name' => 'required_with:locations|string|max:255|min:2',
            'locations.*.street' => 'nullable|string|max:255',
            'locations.*.house_number' => 'nullable|string|max:20',
            'locations.*.postal_code' => ['nullable', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'locations.*.city' => 'nullable|string|max:255',
            'locations.*.country' => 'nullable|string|max:255',
            'locations.*.phone' => ['nullable', 'string', 'max:50', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'locations.*.email' => 'nullable|email:rfc,dns|max:255',
            'locations.*.is_main' => 'nullable|boolean',
            'locations.*.is_active' => 'nullable|boolean',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:5120',
        ], [
            'name.required' => 'Bedrijfsnaam is verplicht.',
            'name.min' => 'Bedrijfsnaam moet minimaal 2 tekens bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'kvk_number.regex' => 'KVK nummer moet 8 cijfers bevatten (bijv. 12345678).',
            'phone.regex' => 'Voer een geldig Nederlands telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'postal_code.regex' => 'Voer een geldige Nederlandse postcode in (bijv. 1234AB).',
            'website.url' => 'Voer een geldige URL in (bijv. https://www.voorbeeld.nl).',
            'locations.*.name.required_with' => 'Vestigingsnaam is verplicht wanneer een vestiging wordt toegevoegd.',
            'locations.*.name.min' => 'Vestigingsnaam moet minimaal 2 tekens bevatten.',
            'locations.*.postal_code.regex' => 'Voer een geldige Nederlandse postcode in (bijv. 1234AB).',
            'locations.*.phone.regex' => 'Voer een geldig Nederlands telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'locations.*.email.email' => 'Voer een geldig e-mailadres in.',
        ]);

        $companyData = $request->all();
        
        // Handle checkbox - if not present in request, set to false
        $companyData['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        
        // Handle branch selection: if branch_select is set and not "other", use that value for industry
        if ($request->has('branch_select') && $request->input('branch_select') !== 'other' && $request->input('branch_select') !== '') {
            $companyData['industry'] = $request->input('branch_select');
        } elseif ($request->has('branch_select') && $request->input('branch_select') === 'other') {
            // If "other" is selected, use the custom industry value
            $companyData['industry'] = $request->input('industry', '');
        }
        // Remove branch_select from data as it's not a database field
        unset($companyData['branch_select']);
        
        // Remove locations from company data
        $locations = $companyData['locations'] ?? [];
        unset($companyData['locations']);
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $mimeType = $request->file('logo')->getMimeType();
            $companyData['logo_blob'] = base64_encode($fileContent);
            $companyData['logo_mime_type'] = $mimeType;
            $companyData['logo_path'] = null; // Clear old file path
        }
        
        $company = Company::create($companyData);
        
        // Create locations if provided
        if (!empty($locations)) {
            $hasMainLocation = false;
            foreach ($locations as $locationData) {
                // Only create location if name is provided
                if (!empty($locationData['name'])) {
                    // Handle checkboxes
                    $locationData['is_main'] = isset($locationData['is_main']) ? (bool) $locationData['is_main'] : false;
                    $locationData['is_active'] = isset($locationData['is_active']) ? (bool) $locationData['is_active'] : true;
                    
                    // If this is marked as main, unset previous main locations
                    if ($locationData['is_main'] && !$hasMainLocation) {
                        $hasMainLocation = true;
                    } elseif ($locationData['is_main'] && $hasMainLocation) {
                        // If another main location was already set, unset this one
                        $locationData['is_main'] = false;
                    }
                    
                    $company->locations()->create($locationData);
                }
            }
        }

        return redirect()->route('admin.companies.show', $company)
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
        
        $company->load(['users', 'vacancies.branch', 'locations', 'mainLocation']);
        
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
        
        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $company->load('mainLocation');
        
        return view('admin.companies.edit', compact('company', 'branches'));
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
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => ['nullable', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_intermediary' => 'nullable|boolean',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:5120',
        ], [
            'name.required' => 'Bedrijfsnaam is verplicht.',
            'name.min' => 'Bedrijfsnaam moet minimaal 2 tekens bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'kvk_number.regex' => 'KVK nummer moet 8 cijfers bevatten (bijv. 12345678).',
            'phone.regex' => 'Voer een geldig Nederlands telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'postal_code.regex' => 'Voer een geldige Nederlandse postcode in (bijv. 1234AB).',
            'website.url' => 'Voer een geldige URL in (bijv. https://www.voorbeeld.nl).',
        ]);

        $data = $request->all();
        
        // Handle checkbox - if not present in request, set to false
        $data['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        
        // Handle branch selection: if branch_select is set and not "other", use that value for industry
        if ($request->has('branch_select') && $request->input('branch_select') !== 'other' && $request->input('branch_select') !== '') {
            $data['industry'] = $request->input('branch_select');
        } elseif ($request->has('branch_select') && $request->input('branch_select') === 'other') {
            // If "other" is selected, use the custom industry value
            $data['industry'] = $request->input('industry', '');
        }
        // Remove branch_select from data as it's not a database field
        unset($data['branch_select']);
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $mimeType = $request->file('logo')->getMimeType();
            $data['logo_blob'] = base64_encode($fileContent);
            $data['logo_mime_type'] = $mimeType;
            $data['logo_path'] = null; // Clear old file path
        }
        
        $company->update($data);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Bedrijf succesvol bijgewerkt.');
    }

    public function uploadLogo(Request $request, Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        $request->validate([
            'logo' => 'required|file|mimes:svg,png,jpg,jpeg|max:5120',
        ], [
            'logo.required' => 'Selecteer een logo bestand.',
            'logo.file' => 'Het bestand moet een geldig bestand zijn.',
            'logo.mimes' => 'Alleen SVG, PNG, JPG en JPEG bestanden zijn toegestaan.',
            'logo.max' => 'Het bestand mag maximaal 5MB groot zijn.',
        ]);
        
        // Get file content and MIME type
        $fileContent = file_get_contents($request->file('logo')->getRealPath());
        $mimeType = $request->file('logo')->getMimeType();
        
        // Store logo as BLOB in database
        try {
            $company->update([
                'logo_blob' => base64_encode($fileContent),
                'logo_mime_type' => $mimeType,
                'logo_path' => null, // Clear old file path
                'updated_at' => now() // Force update timestamp
            ]);
        } catch (\Exception $e) {
            \Log::error('Database storage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het opslaan van het logo: ' . $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Logo succesvol geüpload.',
            'logo_url' => route('admin.companies.logo', ['company' => $company->id])
        ]);
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
        
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Bedrijf '{$company->name}' is succesvol {$status}.",
                'is_active' => $company->is_active
            ]);
        }
        
        return redirect()->route('admin.companies.index')
            ->with('success', "Bedrijf '{$company->name}' is succesvol {$status}.");
    }

    public function toggleMainLocation(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }
        
        // Toggle the is_main field on the company itself
        $company->update(['is_main' => !$company->is_main]);
        
        $status = $company->is_main ? 'aangewezen' : 'uitgeschakeld';
        $message = "Bedrijf '{$company->name}' is succesvol als hoofdkantoor {$status}.";
        
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'has_main_location' => $company->is_main
            ]);
        }
        
        return redirect()->route('admin.companies.show', $company)
            ->with('success', $message);
    }

    public function getUsersJson(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bekijken.');
        }

        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $users = User::where('company_id', $company->id)
            ->whereDoesntHave('roles', function($q) {
                $q->where('name', 'super-admin');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'email']);

        return response()->json(['users' => $users]);
    }
}
