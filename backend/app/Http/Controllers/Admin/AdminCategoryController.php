<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën te bekijken.');
        }
        
        $query = Category::query();
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
        
        $categories = $query->paginate($perPage);
        $categories->appends($request->query());
        
        // Check if this is an AJAX request
        if ($request->ajax()) {
            return view('admin.categories.index', compact('categories'))->render();
        }
        
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën aan te maken.');
        }
        
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën aan te maken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'slug' => 'nullable|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $categoryData = $request->all();
        
        if (empty($categoryData['slug'])) {
            $categoryData['slug'] = Str::slug($request->name);
        }

        Category::create($categoryData);

        return redirect()->route('admin.categories.index')->with('success', 'Categorie succesvol aangemaakt.');
    }

    public function show(Category $category)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën te bekijken.');
        }
        
        return view('admin.categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën te bewerken.');
        }
        
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën te bewerken.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $categoryData = $request->all();
        
        if (empty($categoryData['slug'])) {
            $categoryData['slug'] = Str::slug($request->name);
        }

        $category->update($categoryData);

        return redirect()->route('admin.categories.index')->with('success', 'Categorie succesvol bijgewerkt.');
    }

    public function destroy(Category $category)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-categories')) {
            abort(403, 'Je hebt geen rechten om categorieën te verwijderen.');
        }
        
        if ($category->vacancies()->count() > 0) {
            return back()->with('error', 'Kan categorie niet verwijderen omdat er vacatures aan gekoppeld zijn.');
        }

        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Categorie succesvol verwijderd.');
    }
}
