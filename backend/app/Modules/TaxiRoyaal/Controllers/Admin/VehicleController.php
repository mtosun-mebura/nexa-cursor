<?php

namespace App\Modules\TaxiRoyaal\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Modules\TaxiRoyaal\Models\DefaultRate;
use App\Modules\TaxiRoyaal\Models\Vehicle;
use App\Modules\TaxiRoyaal\Traits\UsesModuleDatabase;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class VehicleController extends Controller
{
    use TenantFilter, UsesModuleDatabase;

    public function index(Request $request)
    {
        $this->authorizeOrPermission('vehicles.view');

        $conn = $this->moduleConnection();
        $query = Vehicle::on($conn)->with('company');
        $this->applyTenantFilter($query);

        if ($request->filled('active')) {
            if ($request->string('active')->toString() === '1') {
                $query->where('active', true);
            } elseif ($request->string('active')->toString() === '0') {
                $query->where('active', false);
            }
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('company')) {
            $query->where('company_id', $request->integer('company'));
        }
        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('license_plate', 'like', "%{$s}%");
            });
        }

        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('direction', 'asc');
        if (!in_array($sortBy, ['name', 'type', 'license_plate', 'created_at'])) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 15;
        $vehicles = $query->paginate($perPage)->withQueryString();

        $companies = auth()->user()->hasRole('super-admin')
            ? Company::on($conn)->orderBy('name')->get()
            : Company::on($conn)->where('id', $this->getTenantId())->get();

        $typeLabels = Vehicle::typeLabels();
        $baseQuery = Vehicle::on($conn);
        $this->applyTenantFilter($baseQuery);
        $activeCount = (clone $baseQuery)->where('active', true)->count();
        $inactiveCount = (clone $baseQuery)->where('active', false)->count();

        return view('taxiroyaal::admin.vehicles.index', compact('vehicles', 'companies', 'typeLabels', 'activeCount', 'inactiveCount'));
    }

    /**
     * Upload voertuigafbeelding (AJAX); retourneert URL voor hidden field.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('vehicles.create') && !auth()->user()->can('vehicles.update')) {
            return response()->json(['success' => false, 'message' => 'Geen rechten.'], 403);
        }
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'image.required' => 'Selecteer een afbeelding.',
            'image.mimes' => 'Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.',
            'image.max' => 'Het bestand mag maximaal 5MB groot zijn.',
        ]);

        $file = $request->file('image');
        $dir = 'vehicles';
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $path = $file->store($dir, 'public');
        $url = '/storage/' . ltrim($path, '/');

        return response()->json(['success' => true, 'url' => $url]);
    }

    public function create()
    {
        $this->authorizeOrPermission('vehicles.create');

        $conn = $this->moduleConnection();
        $companies = auth()->user()->hasRole('super-admin')
            ? Company::on($conn)->orderBy('name')->get()
            : Company::on($conn)->where('id', $this->getTenantId())->get();
        $companyId = $this->getTenantId();
        $typeLabels = Vehicle::typeLabels();
        $personRangeLabels = DefaultRate::getPersonRangeOptions($conn);

        return view('taxiroyaal::admin.vehicles.create', compact('companies', 'companyId', 'typeLabels', 'personRangeLabels'));
    }

    public function store(Request $request)
    {
        $this->authorizeOrPermission('vehicles.create');

        $conn = $this->moduleConnection();
        $user = auth()->user();

        // Gebruik altijd het bedrijf van de ingelogde gebruiker (niet het formulier)
        $companyId = $user->hasRole('super-admin')
            ? $request->input('company_id')
            : $user->company_id;
        $request->merge(['company_id' => $companyId]);

        $personRanges = array_keys(DefaultRate::getPersonRangeOptions($conn));
        $validated = $request->validate([
            'company_id' => ['required'],
            'name' => 'required|string|max:255',
            'type' => 'required|in:car,van,bus',
            'license_plate' => 'nullable|string|max:20',
            'person_range' => ['required', Rule::in($personRanges)],
            'active' => 'boolean',
            'base_fare' => 'nullable|numeric|min:0',
            'price_per_km' => 'nullable|numeric|min:0',
            'price_per_min' => 'nullable|numeric|min:0',
            'min_fare' => 'nullable|numeric|min:0',
            'cleaning_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'image_url' => 'nullable|string|max:500',
            'show_photo' => 'boolean',
        ]);
        $validated['active'] = $request->boolean('active', true);
        $validated['show_photo'] = $request->boolean('show_photo', false);
        [, $maxPersons] = DefaultRate::parseRangeBounds((string) $validated['person_range']);
        $validated['seats'] = max(1, $maxPersons);
        $validated = $this->normalizeVehiclePriceFields($validated);
        $validated['license_plate'] = isset($validated['license_plate']) && $validated['license_plate'] !== ''
            ? strtoupper(trim($validated['license_plate']))
            : null;

        $this->ensureTenantAccess($validated['company_id']);
        $this->ensureCompanyExistsOnModule($conn, $validated['company_id']);
        Vehicle::on($conn)->create($validated);

        return redirect()->route('admin.taxiroyaal.vehicles.index')->with('success', 'Voertuig is aangemaakt.');
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorizeOrPermission('vehicles.view');
        $this->ensureCanAccess($vehicle);

        $vehicle->load(['company', 'rideRequests' => fn ($q) => $q->latest('pickup_at')->limit(10)]);

        $conn = $this->moduleConnection();
        $defaultRates = DefaultRate::getByPersonRange($conn, (string) ($vehicle->person_range ?? ''))
            ?? DefaultRate::getDefault($conn);

        return view('taxiroyaal::admin.vehicles.show', compact('vehicle', 'defaultRates'));
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorizeOrPermission('vehicles.update');
        $this->ensureCanAccess($vehicle);

        $conn = $this->moduleConnection();
        $companies = auth()->user()->hasRole('super-admin')
            ? Company::on($conn)->orderBy('name')->get()
            : Company::on($conn)->where('id', $vehicle->company_id)->get();
        $typeLabels = Vehicle::typeLabels();
        $personRangeLabels = DefaultRate::getPersonRangeOptions($conn);

        return view('taxiroyaal::admin.vehicles.edit', compact('vehicle', 'companies', 'typeLabels', 'personRangeLabels'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeOrPermission('vehicles.update');
        $this->ensureCanAccess($vehicle);

        $conn = $this->moduleConnection();
        $user = auth()->user();
        $companyId = $user->hasRole('super-admin') ? $request->input('company_id') : $user->company_id;
        $request->merge(['company_id' => $companyId]);

        $personRanges = array_keys(DefaultRate::getPersonRangeOptions($conn));
        $validated = $request->validate([
            'company_id' => ['required'],
            'name' => 'required|string|max:255',
            'type' => 'required|in:car,van,bus',
            'license_plate' => 'nullable|string|max:20',
            'person_range' => ['required', Rule::in($personRanges)],
            'active' => 'boolean',
            'base_fare' => 'nullable|numeric|min:0',
            'price_per_km' => 'nullable|numeric|min:0',
            'price_per_min' => 'nullable|numeric|min:0',
            'min_fare' => 'nullable|numeric|min:0',
            'cleaning_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'image_url' => 'nullable|string|max:500',
            'show_photo' => 'boolean',
        ]);
        $validated['active'] = $request->boolean('active', true);
        $validated['show_photo'] = $request->boolean('show_photo', false);
        [, $maxPersons] = DefaultRate::parseRangeBounds((string) $validated['person_range']);
        $validated['seats'] = max(1, $maxPersons);
        $validated = $this->normalizeVehiclePriceFields($validated);
        $validated['license_plate'] = isset($validated['license_plate']) && $validated['license_plate'] !== ''
            ? strtoupper(trim($validated['license_plate']))
            : null;

        $this->ensureTenantAccess($validated['company_id']);
        $this->ensureCompanyExistsOnModule($conn, $validated['company_id']);
        $vehicle->update($validated);

        return redirect()->route('admin.taxiroyaal.vehicles.index')->with('success', 'Voertuig is bijgewerkt.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeOrPermission('vehicles.delete');
        $this->ensureCanAccess($vehicle);

        if ($vehicle->rideRequests()->exists()) {
            return redirect()->route('admin.taxiroyaal.vehicles.index')
                ->with('error', 'Dit voertuig heeft ritten. Verwijder eerst de ritten of wijzig het voertuig bij die ritten.');
        }
        $vehicle->delete();

        return redirect()->route('admin.taxiroyaal.vehicles.index')->with('success', 'Voertuig is verwijderd.');
    }

    private function authorizeOrPermission(string $ability): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if (!auth()->user()->can($ability)) {
            abort(403, 'Geen rechten voor deze actie.');
        }
    }

    private function ensureTenantAccess($companyId): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if ((int) $companyId !== (int) auth()->user()->company_id) {
            abort(403, 'Geen toegang tot dit bedrijf.');
        }
    }

    /**
     * Velden die in de DB NOT NULL zijn (met default 0) vullen met 0 als ze null/leeg zijn.
     */
    private function normalizeVehiclePriceFields(array $validated): array
    {
        foreach (['price_per_km', 'price_per_min', 'min_fare'] as $key) {
            if (!array_key_exists($key, $validated) || $validated[$key] === null || $validated[$key] === '') {
                $validated[$key] = 0;
            }
        }
        return $validated;
    }

    /**
     * Zorg dat het bedrijf op de module-connection bestaat (voor FK); kopieer van default indien nodig.
     */
    private function ensureCompanyExistsOnModule(string $moduleConn, $companyId): void
    {
        if (Company::on($moduleConn)->where('id', $companyId)->exists()) {
            return;
        }
        $company = Company::on(config('database.default'))->find($companyId);
        if (!$company) {
            return;
        }
        $attrs = $company->getAttributes();
        \Illuminate\Support\Facades\DB::connection($moduleConn)->table('companies')->insert($attrs);
    }

    private function ensureCanAccess(Vehicle $vehicle): void
    {
        if (!$this->canAccessResource($vehicle)) {
            abort(403, 'Geen toegang tot dit voertuig.');
        }
    }
}
