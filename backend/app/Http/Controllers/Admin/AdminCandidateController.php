<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminCandidateController extends Controller
{
    public function __construct()
    {
        // Middleware wordt toegepast via routes
    }

    /**
     * Get candidate photo for company viewing
     */
    public function getCandidatePhoto(Candidate $candidate)
    {
        if (!$candidate->photo_blob) {
            abort(404);
        }

        // Generate secure token for company access
        $companyId = 1; // In real implementation, get from authenticated company
        $token = $candidate->getCompanyPhotoToken($companyId);
        
        return redirect()->route('candidate.photo', ['token' => $token]);
    }

    /**
     * Display a listing of candidates
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten te bekijken.');
        }
        
        $query = Candidate::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('experience')) {
            $query->where('experience_years', '>=', $request->experience);
        }

        if ($request->filled('education')) {
            $query->where('education_level', $request->education);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('current_position', 'like', "%{$search}%")
                  ->orWhere('desired_position', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Get per_page from request, default to 25
        $perPage = $request->get('per_page', 25);
        $candidates = $query->paginate($perPage);

        // Get statistics for dashboard
        $stats = [
            'total_candidates' => Candidate::count(),
            'pending_candidates' => Candidate::pending()->count(),
            'active_candidates' => Candidate::active()->count(),
            'rejected_candidates' => Candidate::rejected()->count(),
            'hired_candidates' => Candidate::hired()->count(),
            'by_experience' => [
                'junior' => Candidate::where('experience_years', '<', 1)->count(),
                'medior' => Candidate::whereBetween('experience_years', [1, 2])->count(),
                'senior' => Candidate::whereBetween('experience_years', [3, 6])->count(),
                'expert' => Candidate::where('experience_years', '>=', 7)->count(),
            ],
            'by_education' => Candidate::selectRaw('education_level, count(*) as count')
                ->whereNotNull('education_level')
                ->groupBy('education_level')
                ->pluck('count', 'education_level')
                ->toArray(),
            'by_source' => Candidate::selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray(),
        ];

        return view('admin.candidates.index', compact('candidates', 'stats'));
    }

    /**
     * Show the form for creating a new candidate
     */
    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten aan te maken.');
        }
        
        return view('admin.candidates.create');
    }

    /**
     * Store a newly created candidate
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten aan te maken.');
        }
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'cv_path' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'cover_letter' => 'nullable|string|max:2000',
            'linkedin_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'education_level' => 'nullable|in:high_school,vocational,bachelor,master,phd',
            'current_position' => 'nullable|string|max:255',
            'desired_position' => 'nullable|string|max:255',
            'salary_expectation' => 'nullable|numeric|min:0',
            'availability' => 'required|in:immediate,2_weeks,1_month,3_months,custom',
            'preferred_work_type' => 'required|in:full_time,part_time,freelance,contract,hybrid,remote',
            'preferred_location' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:255',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:255',
            'status' => 'required|in:pending,active,rejected,hired',
            'notes' => 'nullable|string|max:2000',
            'source' => 'required|string|max:255',
            'consent_gdpr' => 'required|boolean',
            'consent_marketing' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        // Handle CV upload
        if ($request->hasFile('cv_path')) {
            $cvPath = $request->file('cv_path')->store('candidates/cv', 'public');
            $data['cv_path'] = $cvPath;
        }

        // Convert arrays to JSON
        if (isset($data['skills'])) {
            $data['skills'] = array_filter($data['skills']);
        }
        if (isset($data['languages'])) {
            $data['languages'] = array_filter($data['languages']);
        }

        $candidate = Candidate::create($data);

        return redirect()->route('admin.candidates.index')
            ->with('success', 'Kandidaat succesvol aangemaakt.');
    }

    /**
     * Display the specified candidate
     */
    public function show(Candidate $candidate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten te bekijken.');
        }
        
        return view('admin.candidates.show', compact('candidate'));
    }

    /**
     * Show the form for editing the specified candidate
     */
    public function edit(Candidate $candidate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten te bewerken.');
        }
        
        return view('admin.candidates.edit', compact('candidate'));
    }

    /**
     * Update the specified candidate
     */
    public function update(Request $request, Candidate $candidate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten te bewerken.');
        }
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email,' . $candidate->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'cv_path' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'cover_letter' => 'nullable|string|max:2000',
            'linkedin_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'education_level' => 'nullable|in:high_school,vocational,bachelor,master,phd',
            'current_position' => 'nullable|string|max:255',
            'desired_position' => 'nullable|string|max:255',
            'salary_expectation' => 'nullable|numeric|min:0',
            'availability' => 'required|in:immediate,2_weeks,1_month,3_months,custom',
            'preferred_work_type' => 'required|in:full_time,part_time,freelance,contract,hybrid,remote',
            'preferred_location' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:255',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:255',
            'status' => 'required|in:pending,active,rejected,hired',
            'notes' => 'nullable|string|max:2000',
            'source' => 'required|string|max:255',
            'consent_gdpr' => 'required|boolean',
            'consent_marketing' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        // Handle CV upload
        if ($request->hasFile('cv_path')) {
            // Delete old CV if exists
            if ($candidate->cv_path) {
                Storage::disk('public')->delete($candidate->cv_path);
            }
            $cvPath = $request->file('cv_path')->store('candidates/cv', 'public');
            $data['cv_path'] = $cvPath;
        }

        // Convert arrays to JSON
        if (isset($data['skills'])) {
            $data['skills'] = array_filter($data['skills']);
        }
        if (isset($data['languages'])) {
            $data['languages'] = array_filter($data['languages']);
        }

        $candidate->update($data);

        return redirect()->route('admin.candidates.index')
            ->with('success', 'Kandidaat succesvol bijgewerkt.');
    }

    /**
     * Remove the specified candidate
     */
    public function destroy(Candidate $candidate)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-candidates')) {
            abort(403, 'Je hebt geen rechten om kandidaten te verwijderen.');
        }
        
        // Delete CV file if exists
        if ($candidate->cv_path) {
            Storage::disk('public')->delete($candidate->cv_path);
        }

        $candidate->delete();

        return redirect()->route('admin.candidates.index')
            ->with('success', 'Kandidaat succesvol verwijderd.');
    }

    /**
     * Toggle candidate status
     */
    public function toggleStatus(Candidate $candidate)
    {
        $newStatus = $candidate->status === 'active' ? 'pending' : 'active';
        $candidate->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', 'Status van kandidaat bijgewerkt naar ' . $newStatus);
    }

    /**
     * Download CV
     */
    public function downloadCV(Candidate $candidate)
    {
        if (!$candidate->cv_path || !Storage::disk('public')->exists($candidate->cv_path)) {
            abort(404, 'CV niet gevonden.');
        }

        return Storage::disk('public')->download($candidate->cv_path);
    }
}
