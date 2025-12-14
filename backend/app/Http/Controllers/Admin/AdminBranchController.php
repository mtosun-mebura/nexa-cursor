<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Branch;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminBranchController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }

        // Stats (overall)
        $stats = [
            'total_branches' => Branch::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
            'inactive_branches' => Branch::where('is_active', false)->count(),
        ];
        
        $query = Branch::query()
            ->withCount('vacancies as used_count');
        $this->applyTenantFilter($query);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Sort functionality
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'created_at', 'is_active', 'used_count'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        // Load all branches for client-side pagination (same as users list)
        $branches = $query->get();
        
        // Check if this is an AJAX request
        if ($request->ajax()) {
            return view('admin.branches.index', compact('branches', 'stats'))->render();
        }
        
        return view('admin.branches.index', compact('branches', 'stats'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-branches')) {
            abort(403, 'Je hebt geen rechten om branches aan te maken.');
        }
        
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-branches')) {
            abort(403, 'Je hebt geen rechten om branches aan te maken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:branches',
            'slug' => 'nullable|string|max:255|unique:branches',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $branchData = $request->all();
        
        if (empty($branchData['slug'])) {
            $branchData['slug'] = Str::slug($request->name);
        }

        $branch = Branch::create($branchData);

        return redirect()->route('admin.branches.show', $branch)->with('success', 'Branch succesvol aangemaakt.');
    }

    public function show(Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }

        $branch->loadCount('vacancies')->load(['functions' => function ($q) {
            $q->orderBy('name');
        }]);

        $recentVacancies = Vacancy::with(['company'])
            ->where('branch_id', $branch->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.branches.show', compact('branch', 'recentVacancies'));
    }

    public function edit(Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }
        
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'slug' => 'nullable|string|max:255|unique:branches,slug,' . $branch->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $branchData = $request->all();
        
        if (empty($branchData['slug'])) {
            $branchData['slug'] = Str::slug($request->name);
        }

        $branch->update($branchData);

        return redirect()->route('admin.branches.show', $branch)->with('success', 'Branch succesvol bijgewerkt.');
    }

    public function destroy(Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-branches')) {
            abort(403, 'Je hebt geen rechten om branches te verwijderen.');
        }
        
        if ($branch->vacancies()->count() > 0) {
            return back()->with('error', 'Kan branch niet verwijderen omdat er vacatures aan gekoppeld zijn.');
        }

        $branch->delete();
        return redirect()->route('admin.branches.index')->with('success', 'Branch succesvol verwijderd.');
    }

    public function toggleStatus(Request $request, Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bewerken.');
        }

        $branch->is_active = !$branch->is_active;
        $branch->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $branch->is_active,
            ]);
        }

        return back()->with('success', 'Branch status succesvol bijgewerkt.');
    }

    public function getData(Branch $branch)
    {
        if (
            !auth()->user()->hasRole('super-admin')
            && !auth()->user()->can('view-branches')
            && !auth()->user()->can('create-vacancies')
            && !auth()->user()->can('edit-vacancies')
        ) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }

        $functions = $branch->functions()
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name'])
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'display_name' => str_replace('_', ' ', (string) $f->name),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'id' => $branch->id,
            'name' => $branch->name,
            'description' => $branch->description,
            'slug' => $branch->slug,
            'functions' => $functions,
        ]);
    }

    public function getAllFunctions()
    {
        if (
            !auth()->user()->hasRole('super-admin')
            && !auth()->user()->can('view-branches')
            && !auth()->user()->can('create-vacancies')
            && !auth()->user()->can('edit-vacancies')
        ) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }

        $functions = \App\Models\BranchFunction::with('branch:id,name')
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name'])
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'display_name' => str_replace('_', ' ', (string) $f->name),
                    'branch_id' => $f->branch_id,
                    'branch_name' => $f->branch->name ?? '',
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'functions' => $functions,
        ]);
    }
}
