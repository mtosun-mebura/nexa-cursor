<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacancy::with(['company', 'category'])->latest();
        
        // Filtering
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->string('location') . '%');
        }
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->string('employment_type'));
        }
        if ($request->filled('remote_work')) {
            $query->where('remote_work', $request->boolean('remote_work'));
        }
        
        // Sortering
        $sortBy = $request->get('sort_by', 'publication_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['publication_date', 'title', 'location', 'company_id', 'category_id', 'status'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $vacancies = $query->paginate(20);
        
        // Voeg status statistieken toe
        $statusStats = [
            'Open' => Vacancy::where('status', 'Open')->count(),
            'Gesloten' => Vacancy::where('status', 'Gesloten')->count(),
            'In behandeling' => Vacancy::where('status', 'In behandeling')->count(),
        ];
        
        return response()->json([
            'vacancies' => $vacancies,
            'status_stats' => $statusStats,
            'filters' => [
                'categories' => Category::orderBy('name')->get(),
                'companies' => Company::orderBy('name')->get(),
                'employment_types' => $this->getEmploymentTypes(),
                'statuses' => ['Open', 'Gesloten', 'In behandeling'],
            ]
        ]);
    }

    public function show(int $id)
    {
        $vacancy = Vacancy::with(['company', 'category'])->findOrFail($id);
        
        // Voeg gerelateerde vacatures toe
        $relatedVacancies = Vacancy::with(['company', 'category'])
            ->where('id', '!=', $id)
            ->where(function($query) use ($vacancy) {
                $query->where('category_id', $vacancy->category_id)
                      ->orWhere('location', $vacancy->location)
                      ->orWhere('company_id', $vacancy->company_id);
            })
            ->active()
            ->latest()
            ->limit(5)
            ->get();
        
        return response()->json([
            'vacancy' => $vacancy,
            'related_vacancies' => $relatedVacancies,
            'seo_data' => [
                'title' => $vacancy->meta_title,
                'description' => $vacancy->meta_description,
                'keywords' => $vacancy->meta_keywords,
                'structured_data' => $vacancy->structured_data,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:50',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'offer' => 'nullable|string',
            'application_instructions' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'reference_number' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'salary_range' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'working_hours' => 'nullable|string|max:50',
            'travel_expenses' => 'boolean',
            'remote_work' => 'boolean',
            'status' => 'nullable|string|in:Open,Gesloten,In behandeling',
            'language' => 'nullable|string|max:20',
            'publication_date' => 'nullable|date',
            'closing_date' => 'nullable|date|after:publication_date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        // Standaard waarden instellen
        $data['status'] = $data['status'] ?? 'Open';
        $data['language'] = $data['language'] ?? 'Nederlands';
        $data['publication_date'] = $data['publication_date'] ?? now();

        $vacancy = Vacancy::create($data);
        
        return response()->json([
            'message' => 'Vacature succesvol aangemaakt',
            'vacancy' => $vacancy->load(['company', 'category'])
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $vacancy = Vacancy::findOrFail($id);
        
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'employment_type' => 'sometimes|nullable|string|max:50',
            'description' => 'sometimes|string',
            'requirements' => 'sometimes|nullable|string',
            'offer' => 'sometimes|nullable|string',
            'application_instructions' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'reference_number' => 'sometimes|nullable|string|max:100',
            'logo' => 'sometimes|nullable|string|max:255',
            'salary_range' => 'sometimes|nullable|string|max:100',
            'start_date' => 'sometimes|nullable|date',
            'working_hours' => 'sometimes|nullable|string|max:50',
            'travel_expenses' => 'sometimes|boolean',
            'remote_work' => 'sometimes|boolean',
            'status' => 'sometimes|nullable|string|in:Open,Gesloten,In behandeling',
            'language' => 'sometimes|nullable|string|max:20',
            'publication_date' => 'sometimes|nullable|date',
            'closing_date' => 'sometimes|nullable|date|after:publication_date',
            'meta_title' => 'sometimes|nullable|string|max:255',
            'meta_description' => 'sometimes|nullable|string',
            'meta_keywords' => 'sometimes|nullable|string',
        ]);
        
        $vacancy->update($data);
        
        return response()->json([
            'message' => 'Vacature succesvol bijgewerkt',
            'vacancy' => $vacancy->load(['company', 'category'])
        ]);
    }

    public function destroy(int $id)
    {
        $vacancy = Vacancy::findOrFail($id);
        $vacancy->delete();
        
        return response()->json([
            'message' => 'Vacature succesvol verwijderd'
        ]);
    }

    /**
     * Publieke vacatures overzicht voor frontend
     */
    public function publicIndex(Request $request)
    {
        $query = Vacancy::with(['company', 'category'])
            ->active()
            ->latest();
        
        // Filtering
        if ($request->filled('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->string('category'));
            });
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->string('location') . '%');
        }
        
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->string('employment_type'));
        }
        
        if ($request->filled('remote_work')) {
            $query->where('remote_work', $request->boolean('remote_work'));
        }
        
        // Sortering
        $sortBy = $request->get('sort_by', 'publication_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['publication_date', 'title', 'location'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $vacancies = $query->paginate(12);
        
        return response()->json([
            'vacancies' => $vacancies,
            'filters' => [
                'categories' => Category::orderBy('name')->get(),
                'employment_types' => $this->getEmploymentTypes(),
            ]
        ]);
    }

    /**
     * Vacature detail voor publieke frontend
     */
    public function publicShow($companySlug, $vacancyId)
    {
        $vacancy = Vacancy::with(['company', 'category'])
            ->whereHas('company', function($query) use ($companySlug) {
                $query->where('slug', $companySlug);
            })
            ->where('id', $vacancyId)
            ->active()
            ->firstOrFail();
        
        // Gerelateerde vacatures
        $relatedVacancies = Vacancy::with(['company', 'category'])
            ->where('id', '!=', $vacancy->id)
            ->where(function($query) use ($vacancy) {
                $query->where('category_id', $vacancy->category_id)
                      ->orWhere('location', $vacancy->location)
                      ->orWhere('company_id', $vacancy->company_id);
            })
            ->active()
            ->latest()
            ->limit(5)
            ->get();
        
        return response()->json([
            'vacancy' => $vacancy,
            'related_vacancies' => $relatedVacancies,
            'seo_data' => [
                'title' => $vacancy->meta_title,
                'description' => $vacancy->meta_description,
                'keywords' => $vacancy->meta_keywords,
                'structured_data' => $vacancy->structured_data,
            ]
        ]);
    }

    /**
     * Krijg beschikbare werktypes
     */
    private function getEmploymentTypes()
    {
        return [
            'Fulltime',
            'Parttime',
            'Contract',
            'Tijdelijke',
            'Stage',
            'Traineeship',
            'Freelance',
            'ZZP',
        ];
    }
}


