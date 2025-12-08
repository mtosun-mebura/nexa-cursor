<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Branch;
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
        
        $query = Branch::query();
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
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'sort_order', 'created_at', 'is_active'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('sort_order', 'asc');
        }
        
        // Pagination
        $perPage = $request->get('per_page', 25);
        $allowedPerPage = [5, 10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }
        
        $branches = $query->paginate($perPage);
        $branches->appends($request->query());
        
        // Check if this is an AJAX request
        if ($request->ajax()) {
            return view('admin.branches.index', compact('branches'))->render();
        }
        
        return view('admin.branches.index', compact('branches'));
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

        Branch::create($branchData);

        return redirect()->route('admin.branches.index')->with('success', 'Branch succesvol aangemaakt.');
    }

    public function show(Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }
        
        return view('admin.branches.show', compact('branch'));
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

        return redirect()->route('admin.branches.index')->with('success', 'Branch succesvol bijgewerkt.');
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

    public function getData(Branch $branch)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-branches')) {
            abort(403, 'Je hebt geen rechten om branches te bekijken.');
        }

        return response()->json([
            'id' => $branch->id,
            'name' => $branch->name,
            'description' => $branch->description,
            'slug' => $branch->slug,
        ]);
    }
}
