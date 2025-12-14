<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Vacancy;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;

class AdminVacancyController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bekijken.');
        }
        
        $query = Vacancy::with(['company', 'branch'])->withCount('matches');
        $this->applyTenantFilter($query);
        
        // Search
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }
        
        // Sorting (same convention as Users: sort + direction)
        $sortField = $request->get('sort', 'publication_date');
        $sortDirection = $request->get('direction', 'desc');
        
        $allowedSortFields = ['id', 'title', 'company_id', 'branch_id', 'status', 'publication_date', 'created_at', 'matches_count'];
        if (in_array($sortField, $allowedSortFields, true)) {
            // Special: sort by matches_count (withCount alias)
            if ($sortField === 'matches_count') {
                $query->orderBy('matches_count', $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->latest('publication_date');
        }
        
        // Load all for KT Datatable client-side pagination
        $vacancies = $query->get();
        
        // Status statistieken
        $statusStatsQuery = Vacancy::query();
        $this->applyTenantFilter($statusStatsQuery);
        
        $statusStats = [
            'Open' => (clone $statusStatsQuery)->where('status', 'Open')->count(),
            'Gesloten' => (clone $statusStatsQuery)->where('status', 'Gesloten')->count(),
            'In behandeling' => (clone $statusStatsQuery)->where('status', 'In behandeling')->count(),
        ];
        
        // Filter data
        $branches = Branch::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        
        return view('admin.vacancies.index', compact('vacancies', 'statusStats', 'branches', 'companies'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures aan te maken.');
        }
        
        $companies = auth()->user()->hasRole('super-admin') ? Company::all() : collect();
        $branches = Branch::with('functions')->orderBy('name')->get();
        return view('admin.vacancies.create', compact('companies', 'branches'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures aan te maken.');
        }

        // Enforce company_id:
        // - Super admin: allow tenant selection via sidebar
        // - Others: always use the user's company_id (no company selector in UI)
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $request->merge(['company_id' => session('selected_tenant')]);
        } elseif (!auth()->user()->hasRole('super-admin')) {
            $request->merge(['company_id' => auth()->user()->company_id]);
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:Open,Gesloten,In behandeling',
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'required_skills' => 'nullable|string',
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

        // Normalize required_skills JSON -> array of strings
        $requiredSkills = null;
        if ($request->filled('required_skills')) {
            try {
                $decoded = json_decode($request->string('required_skills')->toString(), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $requiredSkills = collect($decoded)
                        ->filter(fn ($v) => is_string($v))
                        ->map(fn ($v) => trim($v))
                        ->filter(fn ($v) => $v !== '')
                        ->map(fn ($v) => preg_replace('/\s+/', ' ', $v) ?? $v)
                        ->unique(fn ($v) => mb_strtolower($v))
                        ->take(30)
                        ->values()
                        ->all();
                }
            } catch (\Throwable $e) {
                // ignore invalid JSON, let it be null
            }
        }
        $vacancyData['required_skills'] = $requiredSkills;
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $vacancyData['company_id'] = session('selected_tenant');
        }
        // Voor overige gebruikers: forceer bedrijf van medewerker (beschermt tegen manipulatie)
        if (!auth()->user()->hasRole('super-admin')) {
            $vacancyData['company_id'] = auth()->user()->company_id;
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
        
        $vacancy->load(['company', 'branch']);
        
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
        
        $companies = auth()->user()->hasRole('super-admin') ? Company::all() : collect();
        $branches = Branch::with('functions')->orderBy('name')->get();
        return view('admin.vacancies.edit', compact('vacancy', 'companies', 'branches'));
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

        // Enforce company_id:
        // - Super admin: allow tenant selection via sidebar
        // - Others: always use the user's company_id (no company selector in UI)
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $request->merge(['company_id' => session('selected_tenant')]);
        } elseif (!auth()->user()->hasRole('super-admin')) {
            $request->merge(['company_id' => auth()->user()->company_id]);
        }
        
        // Als alleen status wordt bijgewerkt, valideer alleen status
        if ($request->has('status') && count($request->all()) <= 4) {
            $request->validate([
                'status' => 'required|in:Open,Gesloten,In behandeling',
                'title' => 'required|string|max:255',
                'company_id' => 'required|exists:companies,id',
                'branch_id' => 'required|exists:branches,id',
                'description' => 'required|string',
            ]);
        } else {
            // Volledige validatie voor normale updates
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'status' => 'required|in:Open,Gesloten,In behandeling',
                'company_id' => 'required|exists:companies,id',
                'branch_id' => 'required|exists:branches,id',
                'required_skills' => 'nullable|string',
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

        // Normalize required_skills JSON -> array of strings
        $requiredSkills = null;
        if ($request->filled('required_skills')) {
            try {
                $decoded = json_decode($request->string('required_skills')->toString(), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $requiredSkills = collect($decoded)
                        ->filter(fn ($v) => is_string($v))
                        ->map(fn ($v) => trim($v))
                        ->filter(fn ($v) => $v !== '')
                        ->map(fn ($v) => preg_replace('/\s+/', ' ', $v) ?? $v)
                        ->unique(fn ($v) => mb_strtolower($v))
                        ->take(30)
                        ->values()
                        ->all();
                }
            } catch (\Throwable $e) {
                // ignore invalid JSON, let it be null
            }
        }
        $vacancyData['required_skills'] = $requiredSkills;
        
        // Als Super Admin en tenant geselecteerd, gebruik die tenant
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $vacancyData['company_id'] = session('selected_tenant');
        }
        // Voor overige gebruikers: forceer bedrijf van medewerker (beschermt tegen manipulatie)
        if (!auth()->user()->hasRole('super-admin')) {
            $vacancyData['company_id'] = auth()->user()->company_id;
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
