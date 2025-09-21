<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = Vacancy::with(['company', 'category'])
            ->where('is_active', true)
            ->where('published_at', '<=', now());
        
        // Search query
        if ($request->filled('q')) {
            $searchTerm = $request->get('q');
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('requirements', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->get('location') . '%');
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
        
        // Sort options
        $sortBy = $request->get('sort', 'published_at');
        $sortDirection = $request->get('order', 'desc');
        
        $allowedSorts = ['published_at', 'title', 'salary_min', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('published_at', 'desc');
        }
        
        $jobs = $query->paginate(12)->withQueryString();
        
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



