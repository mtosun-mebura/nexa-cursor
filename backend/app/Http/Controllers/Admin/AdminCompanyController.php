<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\Module as ModuleModel;
use App\Models\User;
use App\Services\EnvService;
use App\Services\ModuleManager;
use App\Support\ModuleSchemaAvailability;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCompanyController extends Controller
{
    use TenantFilter;

    protected $envService;

    public function __construct(EnvService $envService)
    {
        $this->envService = $envService;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }

        $query = Company::withCount('users')->with('mainLocation');
        if (ModuleSchemaAvailability::vacanciesTableExists()) {
            $query->withCount('vacancies');
        }
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
            'total_vacancies' => ModuleSchemaAvailability::vacanciesTableExists()
                ? ($tenantId ? \App\Models\Vacancy::where('company_id', $tenantId)->count() : \App\Models\Vacancy::count())
                : 0,
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
        if (! auth()->user()->canCreateCompanies()) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }

        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        $publishedFrontendThemes = FrontendTheme::active()->orderBy('name')->get();

        return view('admin.companies.create', compact('branches', 'googleMapsApiKey', 'googleMapsZoom', 'googleMapsCenterLat', 'googleMapsCenterLng', 'googleMapsType', 'publishedFrontendThemes'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->canCreateCompanies()) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }

        $request->merge([
            'building_image' => $request->filled('building_image') ? (int) $request->input('building_image') : null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'building_image' => 'nullable|integer|in:1,2,3',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'nullable|string|max:255',
            'street' => 'required|string|max:255|min:2',
            'house_number' => 'required|string|max:20|min:1',
            'postal_code' => ['required', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'city' => 'required|string|max:255|min:2',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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
            'company_logo_mode' => 'nullable|in:single,light_dark',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:5120',
            'logo_dark' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:5120',
            'frontend_theme_id' => 'nullable|integer|exists:frontend_themes,id',
        ], [
            'name.required' => 'Bedrijfsnaam is verplicht.',
            'name.min' => 'Bedrijfsnaam moet minimaal 2 tekens bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'phone.required' => 'Telefoonnummer is verplicht.',
            'phone.regex' => 'Voer een geldig Nederlands telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'street.required' => 'Straat is verplicht.',
            'street.min' => 'Straat moet minimaal 2 tekens bevatten.',
            'house_number.required' => 'Huisnummer is verplicht.',
            'house_number.min' => 'Huisnummer is verplicht.',
            'postal_code.required' => 'Postcode is verplicht.',
            'postal_code.regex' => 'Voer een geldige Nederlandse postcode in (bijv. 1234AB).',
            'city.required' => 'Plaats is verplicht.',
            'city.min' => 'Plaats moet minimaal 2 tekens bevatten.',
            'kvk_number.regex' => 'KVK nummer moet 8 cijfers bevatten (bijv. 12345678).',
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

        // Handle logo upload (must run before create; do not pass UploadedFile to create)
        unset($companyData['logo'], $companyData['logo_dark'], $companyData['company_logo_mode']);
        $companyData['frontend_theme_id'] = $this->normalizeCompanyFrontendThemeId($request->input('frontend_theme_id'));
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $companyData['logo_blob'] = base64_encode(file_get_contents($file->getRealPath()));
            $companyData['logo_mime_type'] = $file->getMimeType();
            $companyData['logo_path'] = null;
        }
        $useLightDarkLogo = $request->input('company_logo_mode') === 'light_dark';
        if ($useLightDarkLogo && $request->hasFile('logo_dark')) {
            $dark = $request->file('logo_dark');
            $companyData['logo_dark_blob'] = base64_encode(file_get_contents($dark->getRealPath()));
            $companyData['logo_dark_mime_type'] = $dark->getMimeType();
        } elseif (! $useLightDarkLogo) {
            $companyData['logo_dark_blob'] = null;
            $companyData['logo_dark_mime_type'] = null;
        }

        $company = Company::create($companyData);

        // Create locations if provided; eerste vestiging krijgt het contactadres van het bedrijf
        if (! empty($locations)) {
            $hasMainLocation = false;
            $isFirstLocation = true;
            foreach ($locations as $locationData) {
                // Only create location if name is provided
                if (! empty($locationData['name'])) {
                    // Handle checkboxes
                    $locationData['is_main'] = isset($locationData['is_main']) ? (bool) $locationData['is_main'] : false;
                    $locationData['is_active'] = isset($locationData['is_active']) ? (bool) $locationData['is_active'] : true;

                    // Eerste vestiging: adres overnemen van contactinformatie
                    if ($isFirstLocation) {
                        $locationData['street'] = $companyData['street'] ?? $locationData['street'] ?? '';
                        $locationData['house_number'] = $companyData['house_number'] ?? $locationData['house_number'] ?? '';
                        $locationData['house_number_extension'] = $companyData['house_number_extension'] ?? $locationData['house_number_extension'] ?? null;
                        $locationData['postal_code'] = $companyData['postal_code'] ?? $locationData['postal_code'] ?? '';
                        $locationData['city'] = $companyData['city'] ?? $locationData['city'] ?? '';
                        $locationData['country'] = $companyData['country'] ?? $locationData['country'] ?? '';
                        $isFirstLocation = false;
                    }

                    // If this is marked as main, unset previous main locations
                    if ($locationData['is_main'] && ! $hasMainLocation) {
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
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $eagerLoad = ['users', 'locations', 'mainLocation', 'domains', 'modules'];
        if (ModuleSchemaAvailability::vacanciesTableExists()) {
            $eagerLoad[] = 'vacancies.branch';
        }
        $company->load($eagerLoad);

        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        $companyWebsiteDevPreviewUrl = null;
        $companyWebsiteDevPreviewHost = null;
        $companyWebsiteHomeInactive = false;
        $companyWebsiteInactivePages = collect();
        $devTenantHostQueryParam = (string) config('tenancy.dev_effective_host_query_param', '');
        if (! app()->isProduction() && $devTenantHostQueryParam !== '') {
            $primaryDomain = $company->domains->firstWhere('is_primary', true);
            $previewHost = $primaryDomain?->host ?? $company->domains->sortBy('id')->first()?->host;
            if ($previewHost !== null && $previewHost !== '') {
                $companyWebsiteDevPreviewHost = $previewHost;
                $companyWebsiteDevPreviewUrl = url('/').'?'.http_build_query(
                    [$devTenantHostQueryParam => $previewHost],
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                );
                app()->instance('resolved_tenant_id', $company->id);
                $websiteBuilder = app(\App\Services\WebsiteBuilderService::class);
                $companyWebsiteHomeInactive = $websiteBuilder->tenantHasInactiveConfiguredHomePage($company->id);
                $companyWebsiteInactivePages = $websiteBuilder->getInactiveTenantWebsitePages($company->id);
            }
        }

        return view('admin.companies.show', compact(
            'company',
            'googleMapsApiKey',
            'googleMapsZoom',
            'googleMapsCenterLat',
            'googleMapsCenterLng',
            'googleMapsType',
            'companyWebsiteDevPreviewUrl',
            'companyWebsiteDevPreviewHost',
            'companyWebsiteHomeInactive',
            'companyWebsiteInactivePages'
        ));
    }

    public function edit(Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $company->load(['mainLocation', 'modules']);
        $allModules = ModuleModel::query()->orderBy('display_name')->get();

        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        $publishedFrontendThemes = FrontendTheme::active()->orderBy('name')->get();

        return view('admin.companies.edit', compact('company', 'branches', 'allModules', 'googleMapsApiKey', 'googleMapsZoom', 'googleMapsCenterLat', 'googleMapsCenterLng', 'googleMapsType', 'publishedFrontendThemes'));
    }

    public function update(Request $request, Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $request->merge([
            'building_image' => $request->filled('building_image') ? (int) $request->input('building_image') : null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'building_image' => 'nullable|integer|in:1,2,3',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'nullable|string|max:255',
            'street' => 'required|string|max:255|min:2',
            'house_number' => 'required|string|max:20|min:1',
            'postal_code' => ['required', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'city' => 'required|string|max:255|min:2',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string|max:5000',
            'is_intermediary' => 'nullable|boolean',
            'is_main' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'contact_first_name' => 'nullable|string|max:255',
            'contact_middle_name' => 'nullable|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'company_logo_mode' => 'nullable|in:single,light_dark',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:5120',
            'logo_dark' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:5120',
            'module_ids' => [Rule::requiredIf(ModuleModel::query()->exists()), 'array', 'min:1'],
            'module_ids.*' => 'integer|exists:modules,id',
            'apply_module_sync' => 'nullable|boolean',
            'frontend_theme_id' => 'nullable|integer|exists:frontend_themes,id',
        ], [
            'name.required' => 'Bedrijfsnaam is verplicht.',
            'name.min' => 'Bedrijfsnaam moet minimaal 2 tekens bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'phone.required' => 'Telefoonnummer is verplicht.',
            'phone.regex' => 'Voer een geldig Nederlands telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'street.required' => 'Straat is verplicht.',
            'street.min' => 'Straat moet minimaal 2 tekens bevatten.',
            'house_number.required' => 'Huisnummer is verplicht.',
            'house_number.min' => 'Huisnummer is verplicht.',
            'postal_code.required' => 'Postcode is verplicht.',
            'postal_code.regex' => 'Voer een geldige Nederlandse postcode in (bijv. 1234AB).',
            'city.required' => 'Plaats is verplicht.',
            'city.min' => 'Plaats moet minimaal 2 tekens bevatten.',
            'kvk_number.regex' => 'KVK nummer moet 8 cijfers bevatten (bijv. 12345678).',
            'website.url' => 'Voer een geldige URL in (bijv. https://www.voorbeeld.nl).',
            'module_ids.required' => 'Selecteer minimaal één module.',
            'module_ids.min' => 'Selecteer minimaal één module.',
        ]);

        $data = $request->all();

        // Handle checkbox - if not present in request, set to false
        $data['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        $data['is_main'] = $request->boolean('is_main');
        $data['is_active'] = $request->boolean('is_active');

        // Handle branch selection: if branch_select is set and not "other", use that value for industry
        if ($request->has('branch_select') && $request->input('branch_select') !== 'other' && $request->input('branch_select') !== '') {
            $data['industry'] = $request->input('branch_select');
        } elseif ($request->has('branch_select') && $request->input('branch_select') === 'other') {
            // If "other" is selected, use the custom industry value
            $data['industry'] = $request->input('industry', '');
        }
        // Remove branch_select from data as it's not a database field
        unset($data['branch_select']);

        unset($data['logo'], $data['logo_dark'], $data['company_logo_mode'], $data['module_ids'], $data['apply_module_sync']);
        $data['frontend_theme_id'] = $this->normalizeCompanyFrontendThemeId($request->input('frontend_theme_id'));

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $mimeType = $request->file('logo')->getMimeType();
            $data['logo_blob'] = base64_encode($fileContent);
            $data['logo_mime_type'] = $mimeType;
            $data['logo_path'] = null; // Clear old file path
        }
        $useLightDarkLogo = $request->input('company_logo_mode') === 'light_dark';
        if ($useLightDarkLogo && $request->hasFile('logo_dark')) {
            $darkFile = $request->file('logo_dark');
            $data['logo_dark_blob'] = base64_encode(file_get_contents($darkFile->getRealPath()));
            $data['logo_dark_mime_type'] = $darkFile->getMimeType();
        } elseif (! $useLightDarkLogo) {
            $data['logo_dark_blob'] = null;
            $data['logo_dark_mime_type'] = null;
        }

        $company->update($data);

        if ($request->boolean('apply_module_sync')) {
            try {
                $this->syncCompanyModulesFromWizardSelection($company, $request->input('module_ids', []));
            } catch (\Throwable $e) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Modules bijwerken mislukt: '.$e->getMessage());
            }
        }

        // Eerste vestiging gelijk trekken met contactadres
        $firstLocation = $company->locations()->orderBy('id')->first();
        if ($firstLocation) {
            $firstLocation->update([
                'street' => $data['street'] ?? $firstLocation->street,
                'house_number' => $data['house_number'] ?? $firstLocation->house_number,
                'house_number_extension' => $data['house_number_extension'] ?? $firstLocation->house_number_extension,
                'postal_code' => $data['postal_code'] ?? $firstLocation->postal_code,
                'city' => $data['city'] ?? $firstLocation->city,
                'country' => $data['country'] ?? $firstLocation->country,
            ]);
        }

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Bedrijf succesvol bijgewerkt.');
    }

    public function uploadLogo(Request $request, Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
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
                'updated_at' => now(), // Force update timestamp
            ]);
        } catch (\Exception $e) {
            \Log::error('Database storage error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het opslaan van het logo: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logo succesvol geüpload.',
            'logo_url' => route('admin.companies.logo', ['company' => $company->id]),
        ]);
    }

    public function destroy(Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('delete-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te verwijderen.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        if ($company->users()->count() > 0) {
            return back()->with('error', 'Kan bedrijf niet verwijderen: er zijn nog gebruikers gekoppeld.');
        }

        if (ModuleSchemaAvailability::vacanciesTableExists() && $company->vacancies()->count() > 0) {
            return back()->with('error', 'Kan bedrijf niet verwijderen: er zijn nog vacatures gekoppeld.');
        }

        $company->delete();

        return redirect()->route('admin.companies.index')
            ->with('success', 'Bedrijf succesvol verwijderd.');
    }

    public function toggleStatus(Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $company->update(['is_active' => ! $company->is_active]);

        $status = $company->is_active ? 'geactiveerd' : 'gedeactiveerd';

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Bedrijf '{$company->name}' is succesvol {$status}.",
                'is_active' => $company->is_active,
            ]);
        }

        return redirect()->route('admin.companies.index')
            ->with('success', "Bedrijf '{$company->name}' is succesvol {$status}.");
    }

    public function toggleMainLocation(Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        // Toggle the is_main field on the company itself
        $company->update(['is_main' => ! $company->is_main]);

        $status = $company->is_main ? 'aangewezen' : 'uitgeschakeld';
        $message = "Bedrijf '{$company->name}' is succesvol als hoofdkantoor {$status}.";

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'has_main_location' => $company->is_main,
            ]);
        }

        return redirect()->route('admin.companies.show', $company)
            ->with('success', $message);
    }

    public function getUsersJson(Company $company)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bekijken.');
        }

        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $users = User::where('company_id', $company->id)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super-admin');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'email']);

        return response()->json(['users' => $users]);
    }

    /**
     * Zelfde koppeling als tenant-wizard stap 4: pivot + install/activate per gekozen module.
     *
     * @param  array<int|string>  $moduleIds
     */
    private function syncCompanyModulesFromWizardSelection(Company $company, array $moduleIds): void
    {
        $moduleManager = app(ModuleManager::class);
        $ids = array_values(array_unique(array_map('intval', $moduleIds)));
        $sync = [];
        foreach ($ids as $id) {
            $sync[$id] = ['settings' => null];
        }
        $company->modules()->sync($sync);

        foreach ($ids as $id) {
            $mod = ModuleModel::query()->find($id);
            if ($mod === null) {
                continue;
            }
            $name = $mod->name;
            if (! $mod->installed) {
                $moduleManager->installModule($name);
                $mod = $mod->fresh();
            }
            if ($mod && ! $mod->active) {
                $moduleManager->activateModule($name);
            }
        }
    }

    private function normalizeCompanyFrontendThemeId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $id = (int) $raw;
        if ($id <= 0) {
            return null;
        }
        $theme = FrontendTheme::query()->whereKey($id)->where('is_active', true)->first();

        return $theme ? $theme->id : null;
    }
}
