<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLocation;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Services\EnvService;

class AdminCompanyLocationController extends Controller
{
    use TenantFilter;

    protected $envService;

    public function __construct(EnvService $envService)
    {
        $this->envService = $envService;
    }

    public function getLocationsJson(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen te bekijken.');
        }

        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $locations = CompanyLocation::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'city']);

        // Add main company address as first option
        $mainAddress = null;
        if ($company->city || $company->street) {
            $mainAddressText = $company->city;
            if ($company->street) {
                $mainAddressText = $company->street;
                if ($company->house_number) {
                    $mainAddressText .= ' ' . $company->house_number;
                    if ($company->house_number_extension) {
                        $mainAddressText .= $company->house_number_extension;
                    }
                }
                if ($company->city && $company->city != $company->street) {
                    $mainAddressText .= ', ' . $company->city;
                }
            }
            $mainAddress = [
                'id' => 0,
                'name' => $mainAddressText,
                'city' => $company->city,
                'is_main_address' => true
            ];
        }

        return response()->json([
            'locations' => $locations,
            'mainAddress' => $mainAddress
        ]);
    }

    public function show(Company $company, CompanyLocation $location)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen te bekijken.');
        }

        if (!$this->canAccessResource($company) || $location->company_id !== $company->id) {
            abort(403, 'Je hebt geen toegang tot deze vestiging.');
        }

        return view('admin.company-locations.show', compact('company', 'location'));
    }

    public function create(Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen toe te voegen.');
        }

        if (!$this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        $googleMapsApiKey = $this->envService->get('GOOGLE_MAPS_API_KEY', '');
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');
        return view('admin.company-locations.create', compact('company', 'googleMapsApiKey', 'googleMapsZoom', 'googleMapsCenterLat', 'googleMapsCenterLng', 'googleMapsType'));
    }

    public function edit(Company $company, CompanyLocation $location)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen te bewerken.');
        }

        if (!$this->canAccessResource($company) || $location->company_id !== $company->id) {
            abort(403, 'Je hebt geen toegang tot deze vestiging.');
        }

        $googleMapsApiKey = $this->envService->get('GOOGLE_MAPS_API_KEY', '');
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');
        return view('admin.company-locations.edit', compact('company', 'location', 'googleMapsApiKey', 'googleMapsZoom', 'googleMapsCenterLat', 'googleMapsCenterLng', 'googleMapsType'));
    }

    public function store(Request $request, Company $company)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen toe te voegen.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'house_number_extension' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_main' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $data = $request->all();
        // Handle checkbox: if not present, set to false
        $data['is_main'] = $request->has('is_main') ? (bool) $request->input('is_main') : false;
        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        // Als deze vestiging als hoofdkantoor wordt aangemerkt, zet alle andere op false
        if ($data['is_main']) {
            CompanyLocation::where('company_id', $company->id)
                ->update(['is_main' => false]);
        }

        $location = new CompanyLocation($data);
        $location->company_id = $company->id;
        $location->save();

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Vestiging succesvol toegevoegd.');
    }

    public function update(Request $request, Company $company, CompanyLocation $location)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen te bewerken.');
        }

        // Check if location belongs to company
        if ($location->company_id !== $company->id) {
            abort(403, 'Deze vestiging behoort niet tot dit bedrijf.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'house_number_extension' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_main' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $data = $request->all();
        // Handle checkbox: if not present, set to false
        $data['is_main'] = $request->has('is_main') ? (bool) $request->input('is_main') : false;
        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        // Als deze vestiging als hoofdkantoor wordt aangemerkt, zet alle andere op false
        if ($data['is_main']) {
            CompanyLocation::where('company_id', $company->id)
                ->where('id', '!=', $location->id)
                ->update(['is_main' => false]);
        }

        $location->update($data);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Vestiging succesvol bijgewerkt.');
    }

    public function destroy(Company $company, CompanyLocation $location)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen te verwijderen.');
        }

        // Check if location belongs to company
        if ($location->company_id !== $company->id) {
            abort(403, 'Deze vestiging behoort niet tot dit bedrijf.');
        }

        $location->delete();

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Vestiging succesvol verwijderd.');
    }

    public function setMain(Company $company, CompanyLocation $location)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om het hoofdkantoor aan te wijzen.');
        }

        // Check if location belongs to company
        if ($location->company_id !== $company->id) {
            abort(403, 'Deze vestiging behoort niet tot dit bedrijf.');
        }

        // Zet alle andere vestigingen op false
        CompanyLocation::where('company_id', $company->id)
            ->update(['is_main' => false]);

        // Zet deze vestiging als hoofdkantoor
        $location->update(['is_main' => true]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Vestiging '{$location->name}' is succesvol aangewezen als hoofdkantoor.",
                'is_main' => $location->is_main
            ]);
        }

        return redirect()->route('admin.companies.show', $company)
            ->with('success', 'Hoofdkantoor succesvol aangewezen.');
    }

    public function toggleStatus(Company $company, CompanyLocation $location)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om vestigingen te bewerken.');
        }

        // Check if location belongs to company
        if ($location->company_id !== $company->id) {
            abort(403, 'Deze vestiging behoort niet tot dit bedrijf.');
        }

        $location->update(['is_active' => !$location->is_active]);

        $status = $location->is_active ? 'geactiveerd' : 'gedeactiveerd';

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Vestiging '{$location->name}' is succesvol {$status}.",
                'is_active' => $location->is_active
            ]);
        }

        return redirect()->route('admin.companies.show', $company)
            ->with('success', "Vestiging '{$location->name}' is succesvol {$status}.");
    }
}

