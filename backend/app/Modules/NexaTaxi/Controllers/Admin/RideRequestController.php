<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RideRequestController extends Controller
{
    use TenantFilter, UsesModuleDatabase;

    public function index(Request $request)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $query = RideRequest::on($conn)->with(['vehicle.company', 'driver', 'company']);
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $query->where(function ($q) {
                $q->where('company_id', session('selected_tenant'))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('company_id', session('selected_tenant')));
            });
        } elseif (! auth()->user()->hasRole('super-admin') && auth()->user()->company_id) {
            $query->where(function ($q) {
                $q->where('company_id', auth()->user()->company_id)
                    ->orWhereHas('vehicle', fn ($v) => $v->where('company_id', auth()->user()->company_id));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('pickup_at', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('pickup_at', '<=', $request->string('to'));
        }

        $allowedPerPage = [10, 15, 25, 50];
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $rideRequests = $query->orderByDesc('pickup_at')->paginate($perPage)->withQueryString();

        $vehicles = Vehicle::on($conn);
        $this->applyTenantFilter($vehicles);
        $vehicles = $vehicles->orderBy('name')->get();

        $statusLabels = RideRequest::statusLabels();

        return view('taxi::admin.ride_requests.index', compact('rideRequests', 'vehicles', 'statusLabels'));
    }

    public function create()
    {
        $this->authorizeOrPermission('rides.create');

        $conn = $this->moduleConnection();
        $vehicles = Vehicle::on($conn);
        $this->applyTenantFilter($vehicles);
        $vehicles = $vehicles->where('active', true)->orderBy('name')->get();

        $companyId = $this->resolveCompanyIdForChauffeurList(null, $conn);
        $drivers = $this->buildChauffeurQuery($companyId)->get();

        $statusLabels = RideRequest::statusLabels();

        return view('taxi::admin.ride_requests.create', compact('vehicles', 'drivers', 'statusLabels'));
    }

    public function store(Request $request)
    {
        $this->authorizeOrPermission('rides.create');

        $conn = $this->moduleConnection();
        $validated = $request->validate([
            'vehicle_id' => ['nullable', Rule::exists($conn.'.vehicles', 'id')],
            'driver_id' => ['nullable', Rule::exists('users', 'id')],
            'status' => 'required|in:draft,quoted,accepted,assigned,completed,cancelled',
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
            'pickup_lat' => 'nullable|numeric',
            'pickup_lng' => 'nullable|numeric',
            'dropoff_lat' => 'nullable|numeric',
            'dropoff_lng' => 'nullable|numeric',
            'distance_meters' => 'nullable|integer|min:0',
            'duration_seconds' => 'nullable|integer|min:0',
            'passengers' => 'required|integer|min:1|max:99',
            'pickup_at' => 'required|date',
            'quoted_price' => 'nullable|numeric|min:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_note' => 'nullable|string|max:2000',
            'quote_expires_at' => 'nullable|date',
        ]);
        $companyId = auth()->user()->hasRole('super-admin') && session('selected_tenant')
            ? session('selected_tenant')
            : auth()->user()->company_id;
        if (! empty($validated['vehicle_id'])) {
            $vehicle = Vehicle::on($conn)->find($validated['vehicle_id']);
            if ($vehicle) {
                $companyId = $vehicle->company_id;
            }
        }
        $validated['company_id'] = $companyId;
        $ride = RideRequest::on($conn)->create($validated);

        return redirect()->route('admin.taxi.ride_requests.show', $ride)->with('success', 'Rit is aangemaakt.');
    }

    public function show(RideRequest $ride_request)
    {
        $this->authorizeOrPermission('rides.view');
        $this->ensureCanAccessRide($ride_request);

        $ride_request->load(['vehicle.company', 'driver']);
        $statusLabels = RideRequest::statusLabels();

        $conn = $this->moduleConnection();
        $vehicles = Vehicle::on($conn);
        $this->applyTenantFilter($vehicles);
        $vehicles = $vehicles->where('active', true)->orderBy('name')->get();

        $rideCompanyId = $this->resolveCompanyIdForChauffeurList($ride_request, $conn);
        $drivers = $this->buildChauffeurQuery($rideCompanyId)->get();

        return view('taxi::admin.ride_requests.show', [
            'ride' => $ride_request,
            'statusLabels' => $statusLabels,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
        ]);
    }

    public function edit(RideRequest $ride_request)
    {
        $this->authorizeOrPermission('rides.update');
        $this->ensureCanAccessRide($ride_request);

        $conn = $this->moduleConnection();
        $vehicles = Vehicle::on($conn);
        $this->applyTenantFilter($vehicles);
        $vehicles = $vehicles->where('active', true)->orderBy('name')->get();

        $rideCompanyId = $this->resolveCompanyIdForChauffeurList($ride_request, $conn);
        $drivers = $this->buildChauffeurQuery($rideCompanyId)->get();

        $statusLabels = RideRequest::statusLabels();

        return view('taxi::admin.ride_requests.edit', ['ride' => $ride_request, 'vehicles' => $vehicles, 'drivers' => $drivers, 'statusLabels' => $statusLabels]);
    }

    public function update(Request $request, RideRequest $ride_request)
    {
        $this->authorizeOrPermission('rides.update');
        $this->ensureCanAccessRide($ride_request);

        $conn = $this->moduleConnection();
        $validated = $request->validate([
            'vehicle_id' => ['nullable', Rule::exists($conn.'.vehicles', 'id')],
            'driver_id' => ['nullable', Rule::exists('users', 'id')],
            'status' => 'required|in:draft,quoted,accepted,assigned,completed,cancelled',
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
            'pickup_lat' => 'nullable|numeric',
            'pickup_lng' => 'nullable|numeric',
            'dropoff_lat' => 'nullable|numeric',
            'dropoff_lng' => 'nullable|numeric',
            'distance_meters' => 'nullable|integer|min:0',
            'duration_seconds' => 'nullable|integer|min:0',
            'passengers' => 'required|integer|min:1|max:99',
            'pickup_at' => 'required|date',
            'quoted_price' => 'nullable|numeric|min:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_note' => 'nullable|string|max:2000',
            'quote_expires_at' => 'nullable|date',
        ]);
        $ride_request->update($validated);

        return redirect()->route('admin.taxi.ride_requests.show', $ride_request)->with('success', 'Rit is bijgewerkt.');
    }

    public function destroy(RideRequest $ride_request)
    {
        $this->authorizeOrPermission('rides.delete');
        $this->ensureCanAccessRide($ride_request);

        $ride_request->delete();

        return redirect()->route('admin.taxi.ride_requests.index')->with('success', 'Rit is verwijderd.');
    }

    /** Toewijzen: alleen vehicle_id en driver_id (AJAX of form). */
    public function assign(Request $request, RideRequest $ride_request)
    {
        $this->authorizeOrPermission('rides.update');
        $this->ensureCanAccessRide($ride_request);

        $conn = $this->moduleConnection();
        $companyId = $this->resolveCompanyIdForChauffeurList($ride_request, $conn);
        $request->validate([
            'vehicle_id' => ['nullable', Rule::exists($conn.'.vehicles', 'id')],
            'driver_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $this->applyChauffeurRoleAndCompanyToUserSubquery($q, $companyId);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                }),
            ],
        ]);
        $ride_request->update([
            'vehicle_id' => $request->input('vehicle_id') ?: null,
            'driver_id' => $request->input('driver_id') ?: null,
            'status' => $ride_request->status === RideRequest::STATUS_QUOTED || $ride_request->status === RideRequest::STATUS_ACCEPTED
                ? RideRequest::STATUS_ASSIGNED
                : $ride_request->status,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Toewijzing opgeslagen.']);
        }

        return redirect()->route('admin.taxi.ride_requests.show', $ride_request)->with('success', 'Voertuig en chauffeur toegewezen.');
    }

    private function authorizeOrPermission(string $ability): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if (! auth()->user()->can($ability)) {
            abort(403, 'Geen rechten voor deze actie.');
        }
    }

    private function ensureCanAccessRide(RideRequest $ride): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        $companyId = auth()->user()->company_id;
        $rideCompanyId = $ride->company_id ?? $ride->vehicle?->company_id;
        if ($rideCompanyId === null || (int) $rideCompanyId !== (int) $companyId) {
            abort(403, 'Geen toegang tot deze rit.');
        }
    }

    private function resolveTenantCompanyId(): ?int
    {
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            return (int) session('selected_tenant');
        }
        if (auth()->user()->company_id) {
            return (int) auth()->user()->company_id;
        }

        return null;
    }

    /**
     * Bedrijf waarvoor chauffeurs getoond moeten worden: rit → tenant-keuze → host → gebruiker.
     */
    private function resolveCompanyIdForChauffeurList(?RideRequest $ride, string $conn): ?int
    {
        if ($ride !== null) {
            $fromRide = $this->resolveRideCompanyId($ride, $conn);
            if ($fromRide !== null && $fromRide > 0) {
                return $fromRide;
            }
        }
        $tenant = $this->getTenantId();
        if ($tenant !== null && $tenant !== '' && (int) $tenant > 0) {
            return (int) $tenant;
        }
        if (app()->bound('resolved_tenant_id')) {
            $resolved = app('resolved_tenant_id');
            if ($resolved !== null && $resolved !== '' && (int) $resolved > 0) {
                return (int) $resolved;
            }
        }

        return $this->resolveTenantCompanyId();
    }

    private function resolveRideCompanyId(RideRequest $ride, string $conn): ?int
    {
        if (! empty($ride->company_id)) {
            return (int) $ride->company_id;
        }
        if (! empty($ride->vehicle_id)) {
            $vehicle = Vehicle::on($conn)->find($ride->vehicle_id);
            if ($vehicle && ! empty($vehicle->company_id)) {
                return (int) $vehicle->company_id;
            }
        }

        return null;
    }

    /**
     * Gebruikers met chauffeur-rol voor dit bedrijf (Spatie teams).
     *
     * - Pivot `model_has_roles.company_id` kan gelijk zijn aan het tenant-id, óf NULL wanneer de rol
     *   zonder team is toegekend; dan geldt het bedrijf van de gebruiker (`users.company_id`).
     * - Rolnemen: exact (case-insensitive) chauffeur, plus gangbare varianten.
     */
    private function buildChauffeurQuery(?int $companyId)
    {
        if ($companyId === null || $companyId <= 0) {
            return User::query()->whereRaw('1 = 0');
        }

        $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
        $rolesTable = DB::getTablePrefix().config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));

        $roleNamesLower = ['chauffeur', 'taxi-chauffeur', 'taxi_chauffeur', 'taxichauffeur'];

        return User::query()
            ->where('company_id', $companyId)
            ->whereExists(function ($sub) use ($companyId, $pivot, $rolesTable, $teamKey, $morphTypes, $roleNamesLower) {
                $sub->select(DB::raw('1'))
                    ->from($pivot)
                    ->join($rolesTable, $rolesTable.'.id', '=', $pivot.'.role_id')
                    ->whereColumn($pivot.'.model_id', 'users.id')
                    ->whereIn($pivot.'.model_type', $morphTypes)
                    ->where(function ($q) use ($pivot, $teamKey, $companyId) {
                        $q->where($pivot.'.'.$teamKey, $companyId)
                            ->orWhere(function ($q2) use ($pivot, $teamKey, $companyId) {
                                $q2->whereNull($pivot.'.'.$teamKey)
                                    ->where('users.company_id', $companyId);
                            });
                    })
                    ->whereIn($rolesTable.'.guard_name', ['web', 'api'])
                    ->where(function ($q) use ($rolesTable, $roleNamesLower) {
                        foreach ($roleNamesLower as $i => $slug) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            $q->{$method}('LOWER(TRIM('.$rolesTable.'.name)) = ?', [$slug]);
                        }
                    });
            })
            ->orderBy('first_name')
            ->orderBy('last_name');
    }

    private function applyChauffeurRoleAndCompanyToUserSubquery($query, int $companyId): void
    {
        $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
        $roles = DB::getTablePrefix().config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));
        $roleNamesLower = ['chauffeur', 'taxi-chauffeur', 'taxi_chauffeur', 'taxichauffeur'];

        $query->where('company_id', $companyId)
            ->whereExists(function ($sub) use ($companyId, $pivot, $roles, $teamKey, $morphTypes, $roleNamesLower) {
                $sub->select(DB::raw('1'))
                    ->from($pivot)
                    ->join($roles, $roles.'.id', '=', $pivot.'.role_id')
                    ->whereColumn($pivot.'.model_id', 'users.id')
                    ->whereIn($pivot.'.model_type', $morphTypes)
                    ->where(function ($q) use ($pivot, $teamKey, $companyId) {
                        $q->where($pivot.'.'.$teamKey, $companyId)
                            ->orWhere(function ($q2) use ($pivot, $teamKey, $companyId) {
                                $q2->whereNull($pivot.'.'.$teamKey)
                                    ->where('users.company_id', $companyId);
                            });
                    })
                    ->whereIn($roles.'.guard_name', ['web', 'api'])
                    ->where(function ($q) use ($roles, $roleNamesLower) {
                        foreach ($roleNamesLower as $i => $slug) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            $q->{$method}('LOWER(TRIM('.$roles.'.name)) = ?', [$slug]);
                        }
                    });
            });
    }
}
