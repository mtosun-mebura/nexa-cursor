<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformBillingLineItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPlatformBillingLineItemController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = PlatformBillingLineItem::query();

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

        $lineItems = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.platform-billing.line-items.index', compact('lineItems'));
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.platform-billing.line-items.form', ['lineItem' => new PlatformBillingLineItem]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        PlatformBillingLineItem::query()->create($this->validated($request));

        return redirect()->route('admin.platform-billing.line-items.index')
            ->with('success', 'Factuurregel aangemaakt.');
    }

    public function edit(PlatformBillingLineItem $lineItem): View
    {
        $this->ensureSuperAdmin();

        return view('admin.platform-billing.line-items.form', compact('lineItem'));
    }

    public function update(Request $request, PlatformBillingLineItem $lineItem): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $lineItem->update($this->validated($request));

        return redirect()->route('admin.platform-billing.line-items.index')
            ->with('success', 'Factuurregel bijgewerkt.');
    }

    public function destroy(PlatformBillingLineItem $lineItem): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $lineItem->delete();

        return redirect()->route('admin.platform-billing.line-items.index')
            ->with('success', 'Factuurregel verwijderd.');
    }

    public function toggleStatus(PlatformBillingLineItem $lineItem): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $lineItem->update(['is_active' => ! $lineItem->is_active]);

        return redirect()->route('admin.platform-billing.line-items.index')
            ->with('success', $lineItem->is_active ? 'Factuurregel geactiveerd.' : 'Factuurregel gedeactiveerd.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'unit_price' => 'required|numeric|min:0',
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
