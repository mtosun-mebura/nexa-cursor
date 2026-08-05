<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformBillingPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPlatformBillingPackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = PlatformBillingPackage::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $driver = DB::connection()->getDriverName();
            $query->where(function ($q) use ($search, $driver) {
                if ($driver === 'pgsql') {
                    $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('description ILIKE ?', ["%{$search}%"]);
                } else {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $packages = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.platform-billing.packages.index', compact('packages'));
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.platform-billing.packages.form', ['package' => new PlatformBillingPackage]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $data = $this->validated($request);
        PlatformBillingPackage::query()->create($data);

        return redirect()->route('admin.platform-billing.packages.index')
            ->with('success', 'Pakket aangemaakt.');
    }

    public function show(PlatformBillingPackage $package): View
    {
        $this->ensureSuperAdmin();

        return view('admin.platform-billing.packages.show', compact('package'));
    }

    public function edit(PlatformBillingPackage $package): View
    {
        $this->ensureSuperAdmin();

        return view('admin.platform-billing.packages.form', compact('package'));
    }

    public function update(Request $request, PlatformBillingPackage $package): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $package->update($this->validated($request));

        return redirect()->route('admin.platform-billing.packages.index')
            ->with('success', 'Pakket bijgewerkt.');
    }

    public function destroy(PlatformBillingPackage $package): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $package->delete();

        return redirect()->route('admin.platform-billing.packages.index')
            ->with('success', 'Pakket verwijderd.');
    }

    public function toggleStatus(PlatformBillingPackage $package): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $package->update(['is_active' => ! $package->is_active]);

        return redirect()->route('admin.platform-billing.packages.index')
            ->with('success', $package->is_active ? 'Pakket geactiveerd.' : 'Pakket gedeactiveerd.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'monthly_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}
