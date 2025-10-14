<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\Category;
use App\Models\Company;
use App\Helpers\GeoHelper;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacancy::with(['company', 'category'])
            ->where('is_active', true)
            ->where('published_at', '<=', now());
        
        // Search query (case insensitive)
        if ($request->filled('q')) {
            $searchTerm = $request->get('q');
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                  ->orWhereRaw('LOWER(requirements) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                  ->orWhereRaw('LOWER(location) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                  ->orWhereHas('company', function($companyQuery) use ($searchTerm) {
                      $companyQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
                  })
                  ->orWhereHas('category', function($categoryQuery) use ($searchTerm) {
                      $categoryQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
                  });
            });
        }
        
        // Skills filter
        if ($request->filled('skills')) {
            $skills = explode(',', $request->get('skills'));
            $query->where(function($q) use ($skills) {
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    $q->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($skill) . '%'])
                      ->orWhereRaw('LOWER(requirements) LIKE ?', ['%' . strtolower($skill) . '%'])
                      ->orWhereRaw('LOWER(title) LIKE ?', ['%' . strtolower($skill) . '%']);
                }
            });
        }
        
        // Location filter (case insensitive) - only if no distance filter is applied
        if ($request->filled('location') && (!$request->filled('distance') || $request->get('distance') === '')) {
            $location = $request->get('location');
            $query->whereRaw('LOWER(location) LIKE ?', ['%' . strtolower($location) . '%']);
        }
        
        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }
        
        // Salary range filter
        if ($request->filled('salary_min')) {
            $query->where('salary_max', '>=', $request->get('salary_min'));
        }
        
        if ($request->filled('salary_max')) {
            $query->where('salary_min', '<=', $request->get('salary_max'));
        }
        
        // Employment type filter
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->get('employment_type'));
        }
        
        // Experience level filter
        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->get('experience_level'));
        }
        
        // Remote work filter
        if ($request->filled('remote_work')) {
            $query->where('remote_work', true);
        }
        
        // Travel expenses filter
        if ($request->filled('travel_expenses')) {
            $query->where('travel_expenses', true);
        }
        
        // Distance filter with real geo coordinates
        if ($request->filled('distance') && $request->get('distance') !== '') {
            $distance = (int) $request->get('distance');
            $location = $request->get('location', 'Amsterdam'); // Default to Amsterdam if no location
            
            // Get coordinates for the search location
            $searchCoords = GeoHelper::getCityCoordinates($location);
            
            if ($searchCoords) {
                $query->where(function($subQuery) use ($searchCoords, $distance) {
                    $subQuery->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->whereRaw("
                                (6371 * acos(
                                    cos(radians(?)) * cos(radians(latitude)) * 
                                    cos(radians(longitude) - radians(?)) + 
                                    sin(radians(?)) * sin(radians(latitude))
                                )) <= ?
                            ", [
                                $searchCoords['latitude'],
                                $searchCoords['longitude'],
                                $searchCoords['latitude'],
                                $distance
                            ]);
                });
            } else {
                // Fallback to simple location matching if coordinates not found
                if ($request->filled('location')) {
                    $query->whereRaw('LOWER(location) LIKE ?', ['%' . strtolower($location) . '%']);
                }
            }
        }
        
        // Sort options
        $sortBy = $request->get('sort', 'published_at');
        
        $allowedSorts = ['published_at', 'title', 'salary_min', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            // Set appropriate sort direction based on field
            $sortDirection = 'desc';
            if ($sortBy === 'title') {
                $sortDirection = 'asc'; // A-Z for titles
            } elseif ($sortBy === 'salary_min') {
                $sortDirection = 'desc'; // High to low for salary
            } elseif ($sortBy === 'created_at') {
                $sortDirection = 'asc'; // Oldest first
            }
            
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('published_at', 'desc');
        }
        
        // Get per_page parameter with default of 15
        $perPage = $request->get('per_page', 15);
        $allowedPerPage = [5, 15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }
        
        $jobs = $query->paginate($perPage)->withQueryString();
        
        // Get filter options
        $categories = Category::orderBy('name')->get();
        $companies = Company::whereHas('vacancies', function($q) {
            $q->where('is_active', true);
        })->orderBy('name')->get();
        
        return view('frontend.pages.jobs.index', compact('jobs', 'categories', 'companies'));
    }
    
    public function show(Vacancy $job)
    {
        // Check if job is active and published
        if (!$job->is_active || $job->published_at > now()) {
            abort(404);
        }
        
        $job->load(['company', 'category']);
        
        // Get related jobs
        $relatedJobs = Vacancy::with(['company', 'category'])
            ->where('id', '!=', $job->id)
            ->where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function($q) use ($job) {
                $q->where('category_id', $job->category_id)
                  ->orWhere('company_id', $job->company_id);
            })
            ->limit(4)
            ->get();
        
        return view('frontend.pages.jobs.show', compact('job', 'relatedJobs'));
    }
}



