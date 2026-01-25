<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Interview;
use App\Models\JobMatch;
use App\Models\Company;
use App\Models\Vacancy;
use App\Models\User;

class AgendaController extends Controller
{
    use TenantFilter;

    public function index(Request $request)
    {
        // Check if user has permission to view agenda
        if (!auth()->user()->can('view-agenda')) {
            abort(403, 'Je hebt geen rechten om de agenda te bekijken.');
        }
        
        // Get users for dropdown (only for super-admin)
        // Filter by tenant and exclude candidates (only backend users)
        $users = collect();
        if (auth()->user()->hasRole('super-admin')) {
            $tenantId = $this->getTenantId();
            
            $query = User::whereDoesntHave('roles', function($q) {
                // Exclude super-admin and candidate roles
                $q->whereIn('name', ['super-admin', 'candidate']);
            });
            
            // Filter by tenant if selected
            if ($tenantId) {
                $query->where('company_id', $tenantId);
            }
            
            $users = $query->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }
        
        return view('admin.pages.agenda', compact('users'));
    }
    
    public function events(Request $request)
    {
        // Check if user has permission to view agenda
        if (!auth()->user()->can('view-agenda')) {
            abort(403, 'Je hebt geen rechten om de agenda te bekijken.');
        }
        
        try {
            $start = $request->get('start');
            $end = $request->get('end');
            $selectedUserId = $request->get('user_id'); // For super-admin to filter by user
            
            \Log::info('Admin agenda events requested', [
                'start' => $start, 
                'end' => $end, 
                'user' => auth()->id(),
                'selected_user_id' => $selectedUserId
            ]);
            
            // Get appointments with optional user filtering
            $appointments = $this->getAppointmentsForDateRange($start, $end, $selectedUserId);
            
            \Log::info('Admin agenda events response', ['count' => count($appointments)]);
            
            return response()->json($appointments);
        } catch (\Exception $e) {
            \Log::error('Admin agenda events error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch events'], 500);
        }
    }
    
    private function getAppointmentsForDateRange($start, $end, $selectedUserId = null)
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        // Build query - show all interviews (not filtered by user)
        // Convert start/end to Carbon instances for proper date comparison
        $startDate = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);
        
        $query = Interview::with(['match.candidate', 'match.vacancy', 'company'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$startDate, $endDate]);
        
        \Log::info('Admin agenda - Date range filter', [
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
        ]);

        // Apply tenant filtering (only if not super-admin or if tenant is selected)
        // BUT: if a specific user is selected, don't apply tenant filter (super-admin wants to see all)
        if ((!$user->hasRole('super-admin') || session('selected_tenant')) && !$selectedUserId) {
            $query = $this->applyTenantFilter($query);
        }

        // If super-admin selected a specific user, filter by that user
        // Show interviews where the user is either the candidate OR the interviewer
        if ($selectedUserId && $user->hasRole('super-admin')) {
            $selectedUser = User::find($selectedUserId);
            if ($selectedUser) {
                \Log::info('Admin agenda - Filtering by selected user', [
                    'selected_user_id' => $selectedUserId,
                    'selected_user_email' => $selectedUser->email,
                ]);
                
                // First, check how many interviews exist for this user
                $totalInterviewsForUser = Interview::where(function($q) use ($selectedUser) {
                    $q->where('interviewer_user_id', $selectedUser->id)
                      ->orWhere('user_id', $selectedUser->id)
                      ->orWhereHas('match.candidate', function($candidateQuery) use ($selectedUser) {
                          $candidateQuery->where('email', $selectedUser->email);
                      });
                })->count();
                
                \Log::info('Admin agenda - Total interviews for user (before date filter)', [
                    'user_id' => $selectedUserId,
                    'total_count' => $totalInterviewsForUser,
                ]);
                
                $query->where(function($q) use ($selectedUser) {
                    // Interviews where user is the interviewer (via interviewer_user_id) - check this first
                    $q->where('interviewer_user_id', $selectedUser->id)
                    // OR fallback: interviews where user_id matches (backward compatibility)
                    ->orWhere('user_id', $selectedUser->id)
                    // OR interviews where user is the candidate (via match.candidate.email)
                    ->orWhereHas('match.candidate', function($candidateQuery) use ($selectedUser) {
                        $candidateQuery->where('email', $selectedUser->email);
                    });
                });
            }
        }

        // Log the SQL query for debugging
        \Log::info('Admin agenda - Query SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        $interviews = $query->get();
        
        \Log::info('Admin agenda - Interviews found', [
            'total_interviews' => $interviews->count(),
            'selected_user_id' => $selectedUserId,
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s'),
        ]);
        
        // Log first few interview IDs for debugging
        if ($interviews->count() > 0) {
            \Log::info('Admin agenda - Sample interview IDs', [
                'interview_ids' => $interviews->take(5)->pluck('id')->toArray(),
            ]);
        }

        $appointments = [];
        $skippedCount = 0;

        foreach ($interviews as $interview) {
            // Skip interviews without match
            if (!$interview->match) {
                \Log::warning('Admin agenda - Interview without match', ['interview_id' => $interview->id]);
                $skippedCount++;
                continue;
            }
            
            $candidate = $interview->match->candidate ?? null;
            $candidateName = 'Onbekend';
            if ($candidate) {
                $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''));
                if (empty($candidateName)) {
                    $candidateName = 'Onbekend';
                }
            }
            
            // Get candidate user for photo token
            $candidateUser = null;
            $userPhotoToken = null;
            if ($candidate && $candidate->email) {
                $candidateUser = User::where('email', $candidate->email)->first();
                if ($candidateUser && method_exists($candidateUser, 'getPhotoToken')) {
                    try {
                        $userPhotoToken = $candidateUser->getPhotoToken();
                    } catch (\Exception $e) {
                        \Log::warning('Error getting photo token for candidate', ['candidate_id' => $candidate->id]);
                    }
                }
            }
            
            try {
                // Format times without timezone conversion - use local server time
                $startTime = $interview->scheduled_at->format('Y-m-d\TH:i:s');
                $endTime = $interview->scheduled_at->copy()->addMinutes($interview->duration ?? 60)->format('Y-m-d\TH:i:s');
                
                $appointments[] = [
                    'id' => $interview->id,
                    'title' => $this->getInterviewTitle($interview, $candidateName),
                    'start' => $startTime,
                    'end' => $endTime,
                    'color' => $this->getEventColor($interview->type ?? 'interview'),
                    'extendedProps' => [
                        'candidate_id' => $candidate ? $candidate->id : null,
                        'candidate_name' => $candidateName,
                        'user_id' => $candidateUser ? $candidateUser->id : null,
                        'user_photo_token' => $userPhotoToken,
                        'location' => $interview->location ?? 'Locatie niet opgegeven',
                        'type' => $interview->type ?? 'interview',
                        'status' => $interview->status ?? 'scheduled',
                        'interviewer_name' => $interview->interviewer_name ?? 'Onbekend',
                        'interviewer_email' => $interview->interviewer_email ?? '',
                        'company_name' => $interview->company->name ?? 'Onbekend bedrijf',
                        'company_address' => $this->getCompanyAddress($interview->company),
                        'company_phone' => $interview->company->phone ?? '',
                        'vacancy_title' => $interview->match->vacancy->title ?? 'Onbekende functie',
                        'notes' => $interview->notes ?? '',
                        'feedback' => $interview->feedback ?? '',
                        'scheduled_at' => $interview->scheduled_at->format('d-m-Y H:i'),
                        'duration' => $interview->duration ?? 60
                    ]
                ];
            } catch (\Exception $e) {
                \Log::error('Admin agenda - Error processing interview', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $skippedCount++;
            }
        }
        
        \Log::info('Admin agenda - Appointments created', [
            'total_appointments' => count($appointments),
            'skipped_interviews' => $skippedCount,
        ]);

        return $appointments;
    }

    private function getInterviewTitle($interview, $candidateName = null)
    {
        $type = $interview->type ?? 'interview';
        
        if ($candidateName === null) {
            $candidate = $interview->match->candidate ?? null;
            if ($candidate) {
                $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''));
                if (empty($candidateName)) {
                    $candidateName = 'Onbekend';
                }
            } else {
                $candidateName = 'Onbekend';
            }
        }
        
        $typeLabels = [
            'interview' => 'Interview',
            'meeting' => 'Meeting',
            'call' => 'Telefoongesprek',
            'assessment' => 'Assessment'
        ];
        
        $typeLabel = $typeLabels[$type] ?? 'Interview';
        
        return "{$typeLabel} met {$candidateName}";
    }

    private function getCompanyAddress($company)
    {
        if (!$company) {
            return 'Adres niet beschikbaar';
        }
        
        $address = [];
        if ($company->address) $address[] = $company->address;
        if ($company->city) $address[] = $company->city;
        if ($company->postal_code) $address[] = $company->postal_code;
        
        return implode(', ', $address) ?: 'Adres niet beschikbaar';
    }
    
    private function getEventColor($type)
    {
        $colors = [
            'interview' => '#3b82f6',    // Blue
            'meeting' => '#10b981',      // Green
            'call' => '#f59e0b',         // Yellow
            'assessment' => '#ef4444'    // Red
        ];
        
        return $colors[$type] ?? '#6b7280'; // Default gray
    }
}
