<?php

namespace App\Modules\Skillmatching\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Modules\Skillmatching\Models\JobMatch;
use App\Modules\Skillmatching\Models\Vacancy;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    use TenantFilter;
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bekijken.');
        }
        
        $query = JobMatch::with(['candidate', 'vacancy.company']);
        
        // Apply tenant filtering via vacancy relationship
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            if (session('selected_tenant')) {
                $query->whereHas('vacancy', function($q) {
                    $q->where('company_id', session('selected_tenant'));
                });
            }
            // Als geen tenant geselecteerd, toon alle matches (geen filtering)
        } else {
            // Company admin en staff kunnen alleen matches van hun eigen bedrijf zien
            $query->whereHas('vacancy', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });
        }
        
        // Filter op status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter op bedrijf (alleen voor super-admin)
        if ($request->filled('company') && auth()->user()->hasRole('super-admin')) {
            $query->whereHas('vacancy', function($q) use ($request) {
                $q->where('company_id', $request->company);
            });
        }
        
        // Filter op score
        if ($request->filled('score')) {
            switch ($request->score) {
                case 'high':
                    $query->where('match_score', '>=', 80);
                    break;
                case 'medium':
                    $query->whereBetween('match_score', [60, 79]);
                    break;
                case 'low':
                    $query->where('match_score', '<', 60);
                    break;
            }
        }
        
        // Filter op vacature
        if ($request->filled('vacancy')) {
            $query->where('vacancy_id', $request->vacancy);
        }
        
        // Filter op leeftijd
        if ($request->filled('age_range')) {
            $now = \Carbon\Carbon::now();
            switch ($request->age_range) {
                case '18-25':
                    $minDate = $now->copy()->subYears(25)->startOfYear();
                    $maxDate = $now->copy()->subYears(18)->endOfYear();
                    $query->whereHas('candidate', function($q) use ($minDate, $maxDate) {
                        $q->whereNotNull('date_of_birth')
                          ->whereBetween('date_of_birth', [$minDate, $maxDate]);
                    });
                    break;
                case '26-30':
                    $minDate = $now->copy()->subYears(30)->startOfYear();
                    $maxDate = $now->copy()->subYears(26)->endOfYear();
                    $query->whereHas('candidate', function($q) use ($minDate, $maxDate) {
                        $q->whereNotNull('date_of_birth')
                          ->whereBetween('date_of_birth', [$minDate, $maxDate]);
                    });
                    break;
                case '31-35':
                    $minDate = $now->copy()->subYears(35)->startOfYear();
                    $maxDate = $now->copy()->subYears(31)->endOfYear();
                    $query->whereHas('candidate', function($q) use ($minDate, $maxDate) {
                        $q->whereNotNull('date_of_birth')
                          ->whereBetween('date_of_birth', [$minDate, $maxDate]);
                    });
                    break;
                case '36-40':
                    $minDate = $now->copy()->subYears(40)->startOfYear();
                    $maxDate = $now->copy()->subYears(36)->endOfYear();
                    $query->whereHas('candidate', function($q) use ($minDate, $maxDate) {
                        $q->whereNotNull('date_of_birth')
                          ->whereBetween('date_of_birth', [$minDate, $maxDate]);
                    });
                    break;
                case '41-50':
                    $minDate = $now->copy()->subYears(50)->startOfYear();
                    $maxDate = $now->copy()->subYears(41)->endOfYear();
                    $query->whereHas('candidate', function($q) use ($minDate, $maxDate) {
                        $q->whereNotNull('date_of_birth')
                          ->whereBetween('date_of_birth', [$minDate, $maxDate]);
                    });
                    break;
                case '50+':
                    $maxDate = $now->copy()->subYears(50)->endOfYear();
                    $query->whereHas('candidate', function($q) use ($maxDate) {
                        $q->whereNotNull('date_of_birth')
                          ->where('date_of_birth', '<=', $maxDate);
                    });
                    break;
            }
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('candidate', function($candidateQuery) use ($search) {
                    $candidateQuery->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('vacancy', function($vacancyQuery) use ($search) {
                    $vacancyQuery->where('title', 'like', "%{$search}%");
                })
                ->orWhere('match_score', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
        
        // Sortering
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Valideer sorteer veld
        $allowedSortFields = ['id', 'candidate_id', 'vacancy_id', 'match_score', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // Set default direction based on sort field
        if (!$sortDirection || !in_array($sortDirection, ['asc', 'desc'])) {
            if (in_array($sortField, ['created_at'])) {
                $sortDirection = 'desc';
            } else {
                $sortDirection = 'asc';
            }
        }
        
        $query->orderBy($sortField, $sortDirection)->orderBy('id', 'asc');
        
        // Load all matches for client-side pagination (like users)
        $matches = $query->get();
        
        
        // Calculate statistics
        $statsQuery = JobMatch::query();
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            if (session('selected_tenant')) {
                $statsQuery->whereHas('vacancy', function($q) {
                    $q->where('company_id', session('selected_tenant'));
                });
            }
        } else {
            $statsQuery->whereHas('vacancy', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });
        }
        
        $stats = [
            'total_matches' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'accepted' => (clone $statsQuery)->where('status', 'accepted')->count(),
            'interview' => (clone $statsQuery)->where('status', 'interview')->count(),
        ];
        
        // Get companies for filter (only for super-admin)
        $companies = auth()->user()->hasRole('super-admin') ? \App\Models\Company::orderBy('name')->get() : collect();
        
        // Get vacancies for filter
        $vacanciesQuery = Vacancy::query();
        if (!$user->hasRole('super-admin')) {
            $vacanciesQuery->where('company_id', $user->company_id);
        } elseif (session('selected_tenant')) {
            $vacanciesQuery->where('company_id', session('selected_tenant'));
        }
        $vacancies = $vacanciesQuery->orderBy('title')->get();
        
        return view('skillmatching::admin.matches.index', compact('matches', 'stats', 'companies', 'vacancies'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-matches')) {
            abort(403, 'Je hebt geen rechten om matches aan te maken.');
        }
        
        // Haal alleen kandidaten en vacatures op (tenzij super-admin, dan alle vacatures)
        $candidates = \App\Models\Candidate::orderBy('first_name')->orderBy('last_name')->get();
        
        if (auth()->user()->hasRole('super-admin')) {
            $vacancies = Vacancy::all();
        } else {
            $vacancies = Vacancy::where('company_id', auth()->user()->company_id)->get();
        }
        
        return view('skillmatching::admin.matches.create', compact('candidates', 'vacancies'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-matches')) {
            abort(403, 'Je hebt geen rechten om matches aan te maken.');
        }
        
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'vacancy_id' => 'required|exists:vacancies,id',
            'match_score' => 'nullable|numeric|between:0,100',
            'status' => 'required|in:pending,accepted,rejected,interview_scheduled,hired',
            'ai_recommendation' => 'nullable|in:strong_match,good_match,moderate_match,weak_match,not_recommended',
            'application_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'ai_analysis' => 'nullable|string',
        ]);

        // Voor non-super-admin gebruikers: controleer of de vacature bij hun bedrijf hoort
        if (!auth()->user()->hasRole('super-admin')) {
            $vacancy = Vacancy::findOrFail($request->vacancy_id);
            if ($vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je kunt alleen matches aanmaken voor vacatures van je eigen bedrijf.');
            }
        }

        $matchData = $request->all();
        
        // Parse application_date from dd-MM-yyyy format
        if (isset($matchData['application_date']) && !empty($matchData['application_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $matchData['application_date'])) {
                    $matchData['application_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $matchData['application_date'])->format('Y-m-d');
                } else {
                    $matchData['application_date'] = \Carbon\Carbon::parse($matchData['application_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse application_date', ['input' => $matchData['application_date'], 'error' => $e->getMessage()]);
                $matchData['application_date'] = null;
            }
        }
        
        JobMatch::create($matchData);
        return redirect()->route('admin.skillmatching.matches.index')->with('success', 'Match succesvol aangemaakt.');
    }

    public function show(JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessMatch($match)) {
            abort(403, 'Je hebt geen toegang tot deze match.');
        }
        
        $match->load(['candidate', 'vacancy.company', 'vacancy.contactUser', 'interviews']);
        
        // Build activity timeline
        $activities = [];
        
        // 1. Match created (application submitted)
        if ($match->created_at) {
            $activities[] = [
                'type' => 'application',
                'icon' => 'ki-filled ki-file-added',
                'title' => 'Sollicitatie ingediend',
                'description' => $match->candidate ? $match->candidate->first_name . ' ' . $match->candidate->last_name . ' heeft gesolliciteerd op ' . ($match->vacancy ? $match->vacancy->title : 'de vacature') : 'Sollicitatie ingediend',
                'date' => $match->created_at,
                'color' => 'primary'
            ];
        }
        
        // 2. Application date (if different from created_at)
        if ($match->application_date && $match->application_date->format('Y-m-d') !== $match->created_at->format('Y-m-d')) {
            $activities[] = [
                'type' => 'application_date',
                'icon' => 'ki-filled ki-calendar-tick',
                'title' => 'Sollicitatiedatum geregistreerd',
                'description' => 'Officiële sollicitatiedatum: ' . $match->application_date->format('d-m-Y'),
                'date' => $match->application_date,
                'color' => 'primary'
            ];
        }
        
        // 3. Status changes (based on updated_at when status changed)
        if ($match->status) {
            $statusLabels = [
                'pending' => 'In afwachting',
                'accepted' => 'Geaccepteerd',
                'rejected' => 'Afgewezen',
                'interview' => 'Interview gepland',
                'interview_scheduled' => 'Interview gepland',
                'hired' => 'Aangenomen'
            ];
            
            $activities[] = [
                'type' => 'status',
                'icon' => 'ki-filled ki-check-circle',
                'title' => 'Status: ' . ($statusLabels[$match->status] ?? ucfirst($match->status)),
                'description' => 'De status is gewijzigd naar: ' . ($statusLabels[$match->status] ?? ucfirst($match->status)),
                'date' => $match->updated_at,
                'color' => $match->status === 'accepted' ? 'success' : ($match->status === 'rejected' ? 'danger' : ($match->status === 'interview' || $match->status === 'interview_scheduled' ? 'info' : 'warning'))
            ];
        }
        
        // 4. Interviews
        if ($match->interviews && $match->interviews->count() > 0) {
            foreach ($match->interviews->sortBy('scheduled_at') as $interview) {
                $activities[] = [
                    'type' => 'interview',
                    'icon' => 'ki-filled ki-calendar',
                    'title' => 'Interview gepland',
                    'description' => 'Interview op ' . ($interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y H:i') : 'onbekende datum') . ($interview->location ? ' - ' . $interview->location : ''),
                    'date' => $interview->scheduled_at ?? $interview->created_at,
                    'color' => 'info'
                ];
                
                if ($interview->status) {
                    $interviewStatusLabels = [
                        'scheduled' => 'Gepland',
                        'completed' => 'Voltooid',
                        'cancelled' => 'Geannuleerd',
                        'no_show' => 'Niet verschenen'
                    ];
                    
                    $activities[] = [
                        'type' => 'interview_status',
                        'icon' => 'ki-filled ki-check',
                        'title' => 'Interview status: ' . ($interviewStatusLabels[$interview->status] ?? ucfirst($interview->status)),
                        'description' => $interview->notes ? $interview->notes : 'Interview status bijgewerkt',
                        'date' => $interview->updated_at,
                        'color' => $interview->status === 'completed' ? 'success' : ($interview->status === 'cancelled' || $interview->status === 'no_show' ? 'danger' : 'info')
                    ];
                }
            }
        }
        
        // 5. Match score calculated
        if ($match->match_score) {
            $activities[] = [
                'type' => 'match_score',
                'icon' => 'ki-filled ki-chart-simple',
                'title' => 'Match score berekend',
                'description' => 'AI heeft een match score van ' . $match->match_score . '% berekend',
                'date' => $match->created_at,
                'color' => $match->match_score >= 80 ? 'success' : ($match->match_score >= 60 ? 'warning' : 'danger')
            ];
        }
        
        // Sort activities by date (newest first)
        usort($activities, function($a, $b) {
            return $b['date']->timestamp <=> $a['date']->timestamp;
        });
        
        // If this is an AJAX request, return only the activity HTML
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'activities' => $activities,
                'html' => view('skillmatching::admin.matches.partials.activity', compact('activities'))->render()
            ]);
        }
        
        return view('skillmatching::admin.matches.show', compact('match', 'activities'));
    }

    public function edit(JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessMatch($match)) {
            abort(403, 'Je hebt geen toegang tot deze match.');
        }
        
        $match->load(['candidate', 'vacancy.company', 'vacancy.contactUser']);
        return view('skillmatching::admin.matches.edit', compact('match'));
    }

    public function update(Request $request, JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessMatch($match)) {
            abort(403, 'Je hebt geen toegang tot deze match.');
        }
        
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'vacancy_id' => 'required|exists:vacancies,id',
            'match_score' => 'nullable|numeric|between:0,100',
            'status' => 'required|in:pending,accepted,rejected,interview_scheduled,hired',
            'ai_recommendation' => 'nullable|in:strong_match,good_match,moderate_match,weak_match,not_recommended',
            'application_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'ai_analysis' => 'nullable|string',
        ]);

        $matchData = $request->all();
        
        // Parse application_date from dd-MM-yyyy format
        if (isset($matchData['application_date']) && !empty($matchData['application_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $matchData['application_date'])) {
                    $matchData['application_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $matchData['application_date'])->format('Y-m-d');
                } else {
                    $matchData['application_date'] = \Carbon\Carbon::parse($matchData['application_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse application_date', ['input' => $matchData['application_date'], 'error' => $e->getMessage()]);
                $matchData['application_date'] = null;
            }
        }

        $match->update($matchData);
        return redirect()->route('admin.skillmatching.matches.index')->with('success', 'Match succesvol bijgewerkt.');
    }

    public function destroy(JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-matches')) {
            abort(403, 'Je hebt geen rechten om matches te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessMatch($match)) {
            abort(403, 'Je hebt geen toegang tot deze match.');
        }
        
        $match->delete();
        return redirect()->route('admin.skillmatching.matches.index')->with('success', 'Match succesvol verwijderd.');
    }
    
    public function candidates($vacancyId, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-matches')) {
            abort(403, 'Je hebt geen rechten om kandidaten te bekijken.');
        }
        
        $vacancy = Vacancy::with(['company', 'contactUser'])->findOrFail($vacancyId);
        
        // Check if user can access this vacancy
        $user = auth()->user();
        if (!$user->hasRole('super-admin')) {
            if ($vacancy->company_id !== $user->company_id) {
                abort(403, 'Je hebt geen toegang tot deze vacature.');
            }
        } else {
            if (session('selected_tenant') && $vacancy->company_id !== session('selected_tenant')) {
                abort(403, 'Je hebt geen toegang tot deze vacature.');
            }
        }
        
        // Get all matches (candidates) for this vacancy
        $query = JobMatch::with(['candidate'])
            ->where('vacancy_id', $vacancyId);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('candidate', function($candidateQuery) use ($search) {
                $candidateQuery->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter op status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter op score
        if ($request->filled('score')) {
            switch ($request->score) {
                case 'high':
                    $query->where('match_score', '>=', 80);
                    break;
                case 'medium':
                    $query->whereBetween('match_score', [60, 79]);
                    break;
                case 'low':
                    $query->where('match_score', '<', 60);
                    break;
            }
        }
        
        // Sortering
        $sortField = $request->get('sort', 'match_score');
        $sortDirection = $request->get('direction', 'desc');
        
        $allowedSortFields = ['match_score', 'status', 'created_at', 'first_name', 'last_name'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'match_score';
        }
        
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        
        if ($sortField === 'first_name' || $sortField === 'last_name') {
            $query->join('candidates', 'matches.candidate_id', '=', 'candidates.id')
                  ->orderBy('candidates.' . $sortField, $sortDirection)
                  ->select('matches.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        $candidates = $query->get();
        
        return view('skillmatching::admin.matches.candidates', compact('vacancy', 'candidates'));
    }
    
    /**
     * Check if user can access a specific match
     */
    protected function canAccessMatch($match)
    {
        $user = auth()->user();
        
        // Super admin kan alles benaderen
        if ($user->hasRole('super-admin')) {
            return true;
        }
        
        // Andere gebruikers kunnen alleen matches van hun eigen bedrijf benaderen
        return $match->vacancy->company_id === $user->company_id;
    }
}
