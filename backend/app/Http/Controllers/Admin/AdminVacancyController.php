<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Vacancy;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminVacancyController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bekijken.');
        }
        
        $query = Vacancy::with(['company', 'category']);
        $this->applyTenantFilter($query);
        
        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }
        
        // Sortering
        $sortBy = $request->get('sort_by', 'publication_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['id', 'title', 'company_id', 'category_id', 'status', 'publication_date'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('publication_date');
        }
        
        $perPage = $request->get('per_page', 5);
        $vacancies = $query->paginate($perPage);
        
        // Status statistieken
        $statusStatsQuery = Vacancy::query();
        $this->applyTenantFilter($statusStatsQuery);
        
        $statusStats = [
            'Open' => (clone $statusStatsQuery)->where('status', 'Open')->count(),
            'Gesloten' => (clone $statusStatsQuery)->where('status', 'Gesloten')->count(),
            'In behandeling' => (clone $statusStatsQuery)->where('status', 'In behandeling')->count(),
        ];
        
        // Filter data
        $categories = Category::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        
        return view('admin.vacancies.index', compact('vacancies', 'statusStats', 'categories', 'companies'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures aan te maken.');
        }
        
        $companies = Company::all();
        $categories = Category::all();
        return view('admin.vacancies.create', compact('companies', 'categories'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures aan te maken.');
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:Open,Gesloten,In behandeling',
            'company_id' => 'required|exists:companies,id',
            'category_id' => 'nullable|exists:categories,id',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:Fulltime,Parttime,Contract,Tijdelijke,Stage,Traineeship,Freelance,ZZP',
            'salary_range' => 'nullable|string|max:100',
            'requirements' => 'nullable|string',
            'offer' => 'nullable|string',
            'application_instructions' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:50',
            'travel_expenses' => 'boolean',
            'remote_work' => 'boolean',
            'language' => 'nullable|string|max:20',
            'publication_date' => 'nullable|date',
            'closing_date' => 'nullable|date|after:publication_date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        $vacancyData = $request->all();
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $vacancyData['company_id'] = session('selected_tenant');
        }
        
        // Standaard waarden instellen
        $vacancyData['status'] = $vacancyData['status'] ?? 'Open';
        $vacancyData['language'] = $vacancyData['language'] ?? 'Nederlands';
        $vacancyData['publication_date'] = $vacancyData['publication_date'] ?? now();

        Vacancy::create($vacancyData);
        return redirect()->route('admin.vacancies.index')->with('success', 'Vacature succesvol aangemaakt.');
    }

    public function show(Vacancy $vacancy)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }
        
        $vacancy->load(['company', 'category']);
        
        return view('admin.vacancies.show', compact('vacancy'));
    }

    public function edit(Vacancy $vacancy)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }
        
        $companies = Company::all();
        $categories = Category::all();
        return view('admin.vacancies.edit', compact('vacancy', 'companies', 'categories'));
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }
        
        // Als alleen status wordt bijgewerkt, valideer alleen status
        if ($request->has('status') && count($request->all()) <= 4) {
            $request->validate([
                'status' => 'required|in:Open,Gesloten,In behandeling',
                'title' => 'required|string|max:255',
                'company_id' => 'required|exists:companies,id',
                'description' => 'required|string',
            ]);
        } else {
            // Volledige validatie voor normale updates
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'status' => 'required|in:Open,Gesloten,In behandeling',
                'company_id' => 'required|exists:companies,id',
                'category_id' => 'nullable|exists:categories,id',
                'location' => 'nullable|string|max:255',
                'employment_type' => 'nullable|in:Fulltime,Parttime,Contract,Tijdelijke,Stage,Traineeship,Freelance,ZZP',
                'salary_range' => 'nullable|string|max:100',
                'requirements' => 'nullable|string',
                'offer' => 'nullable|string',
                'application_instructions' => 'nullable|string',
                'reference_number' => 'nullable|string|max:100',
                'working_hours' => 'nullable|string|max:50',
                'travel_expenses' => 'boolean',
                'remote_work' => 'boolean',
                'language' => 'nullable|string|max:20',
                'publication_date' => 'nullable|date',
                'closing_date' => 'nullable|date|after:publication_date',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
            ]);
        }

        $vacancyData = $request->all();
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $vacancyData['company_id'] = session('selected_tenant');
        }

        $vacancy->update($vacancyData);
        return redirect()->route('admin.vacancies.index')->with('success', 'Vacature succesvol bijgewerkt.');
    }

    public function destroy(Vacancy $vacancy)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }
        
        $vacancy->delete();
        return redirect()->route('admin.vacancies.index')->with('success', 'Vacature succesvol verwijderd.');
    }
}
