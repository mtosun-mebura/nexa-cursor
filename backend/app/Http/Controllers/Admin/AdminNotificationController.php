<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Notification;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminNotificationController extends Controller
{
    use TenantFilter;
    
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties te bekijken.');
        }
        
        $query = Notification::with('user');
        $this->applyTenantFilter($query);
        
        // Filter op status
        if ($request->filled('status')) {
            if ($request->status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($request->status === 'unread') {
                $query->whereNull('read_at');
            }
        }
        
        // Filter op type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter op prioriteit
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('message', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%");
            });
        }
        
        // Sortering
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Valideer sorteer veld
        $allowedSortFields = ['id', 'user_id', 'type', 'status', 'created_at'];
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
        
        // Speciale behandeling voor status sortering
        if ($sortField === 'status') {
            // Sorteer op status met logische volgorde: Ongelezen, Gelezen
            $query->orderByRaw("
                CASE 
                    WHEN read_at IS NULL THEN 1
                    WHEN read_at IS NOT NULL THEN 2
                END " . $sortDirection
            )->orderBy('id', 'asc');
        } else {
            $query->orderBy($sortField, $sortDirection)->orderBy('id', 'asc');
        }
        
        // Load all notifications for client-side pagination
        $notifications = $query->get()->map(function($notification) {
            // Get sender info from notification data
            $sender = null;
            if ($notification->data) {
                $data = json_decode($notification->data, true);
                if (is_array($data)) {
                    if (isset($data['sender_id']) && $data['sender_id']) {
                        $sender = \App\Models\User::find($data['sender_id']);
                    }
                    if (!$sender && isset($data['sender_email']) && $data['sender_email']) {
                        $sender = \App\Models\User::where('email', $data['sender_email'])->first();
                    }
                    if (!$sender && isset($data['responder_id']) && $data['responder_id']) {
                        $sender = \App\Models\User::find($data['responder_id']);
                    }
                }
            }
            
            // Add sender to notification object
            $notification->sender = $sender;
            return $notification;
        });
        
        // Calculate statistics
        $statsQuery = Notification::query();
        $statsQuery = $this->applyTenantFilter($statsQuery);
        
        $stats = [
            'total_notifications' => (clone $statsQuery)->count(),
            'read' => (clone $statsQuery)->whereNotNull('read_at')->count(),
            'unread' => (clone $statsQuery)->whereNull('read_at')->count(),
            'unique_users' => (clone $statsQuery)->distinct('user_id')->count('user_id'),
        ];
        
        return view('admin.notifications.index', compact('notifications', 'stats'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties aan te maken.');
        }
        
        $companyId = $this->getTenantId();
        
        // Get backend users from the same company
        $backendUsers = \App\Models\User::where('company_id', $companyId)
            ->where('id', '!=', auth()->id())
            ->whereHas('roles', function($q) {
                $q->where('name', '!=', 'candidate');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        
        // Get candidates who have applied to vacancies of this company
        // Get matches with vacancy information
        $matches = \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
            $vq->where('company_id', $companyId);
        })->with(['vacancy', 'candidate'])->get();
        
        \Log::info('Notification create - Matches found', [
            'company_id' => $companyId,
            'matches_count' => $matches->count(),
            'match_ids' => $matches->pluck('id')->toArray(),
            'candidate_ids' => $matches->pluck('candidate_id')->unique()->toArray(),
        ]);
        
        // Get unique candidate IDs from matches
        $candidateIds = $matches->pluck('candidate_id')->unique()->filter()->values();
        
        \Log::info('Notification create - Candidate IDs from matches', [
            'candidate_ids' => $candidateIds->toArray(),
        ]);
        
        // Get candidates directly from candidates table
        $candidatesFromTable = \App\Models\Candidate::whereIn('id', $candidateIds)
            ->whereNotNull('email')
            ->get();
        
        \Log::info('Notification create - Candidates from table', [
            'candidates_count' => $candidatesFromTable->count(),
            'candidate_emails' => $candidatesFromTable->pluck('email')->toArray(),
        ]);
        
        // Get candidate emails (case-insensitive matching)
        $candidateEmails = $candidatesFromTable->pluck('email')
            ->map(function($email) {
                return strtolower(trim($email));
            })
            ->filter()
            ->unique()
            ->toArray();
        
        \Log::info('Notification create - Candidate emails (normalized)', [
            'candidate_emails' => $candidateEmails,
        ]);
        
        // Get ALL users with candidate role first
        $allCandidateUsers = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'candidate');
            })
            ->get();
        
        \Log::info('Notification create - All candidate users', [
            'all_candidate_users_count' => $allCandidateUsers->count(),
            'all_candidate_user_emails' => $allCandidateUsers->pluck('email')->map(fn($e) => strtolower(trim($e ?? '')))->toArray(),
        ]);
        
        // Get users with candidate role that match the candidate emails (case-insensitive)
        $usersWithCandidateRole = $allCandidateUsers->filter(function($user) use ($candidateEmails) {
            $userEmail = strtolower(trim($user->email ?? ''));
            $matches = in_array($userEmail, $candidateEmails);
            if ($matches) {
                \Log::info('Notification create - Matched user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'normalized_email' => $userEmail,
                ]);
            }
            return $matches;
        });
        
        // For candidates without User records, check if User exists or create them automatically
        $candidatesWithoutUsers = $candidatesFromTable->filter(function($candidate) use ($usersWithCandidateRole) {
            $candidateEmail = strtolower(trim($candidate->email ?? ''));
            return !$usersWithCandidateRole->contains(function($user) use ($candidateEmail) {
                return strtolower(trim($user->email ?? '')) === $candidateEmail;
            });
        });
        
        foreach ($candidatesWithoutUsers as $candidate) {
            if ($candidate->email) {
                $candidateEmail = strtolower(trim($candidate->email));
                
                // First check if User already exists (maybe without candidate role)
                $existingUser = \App\Models\User::whereRaw('LOWER(TRIM(email)) = ?', [$candidateEmail])->first();
                
                if ($existingUser) {
                    \Log::info('Notification create - User already exists, checking role', [
                        'user_id' => $existingUser->id,
                        'candidate_id' => $candidate->id,
                        'candidate_email' => $candidate->email,
                    ]);
                    
                    // Check if user has candidate role
                    if (!$existingUser->hasRole('candidate')) {
                        // Assign candidate role if missing
                        $candidateRole = Role::where('name', 'candidate')->first();
                        if ($candidateRole) {
                            $existingUser->assignRole($candidateRole);
                            \Log::info('Notification create - Assigned candidate role to existing user', [
                                'user_id' => $existingUser->id,
                            ]);
                        }
                    }
                    
                    $usersWithCandidateRole->push($existingUser);
                } else {
                    \Log::info('Notification create - Creating User for candidate', [
                        'candidate_id' => $candidate->id,
                        'candidate_email' => $candidate->email,
                    ]);
                    
                    // Create User record for candidate
                    $user = \App\Models\User::create([
                        'first_name' => $candidate->first_name ?? '',
                        'last_name' => $candidate->last_name ?? '',
                        'email' => $candidate->email,
                        'password' => bcrypt(str()->random(32)), // Random password, candidate will need to reset
                        'company_id' => null, // Candidates don't belong to a company
                    ]);
                    
                    // Assign candidate role
                    $candidateRole = Role::where('name', 'candidate')->first();
                    if ($candidateRole) {
                        $user->assignRole($candidateRole);
                    }
                    
                    $usersWithCandidateRole->push($user);
                    
                    \Log::info('Notification create - User created for candidate', [
                        'user_id' => $user->id,
                        'candidate_id' => $candidate->id,
                        'email' => $user->email,
                    ]);
                }
            }
        }
        
        \Log::info('Notification create - Matched candidate users', [
            'matched_users_count' => $usersWithCandidateRole->count(),
            'matched_user_emails' => $usersWithCandidateRole->pluck('email')->toArray(),
        ]);
        
        // Sort candidates by name
        $candidates = $usersWithCandidateRole->sortBy(function($user) {
            return ($user->first_name ?? '') . ' ' . ($user->last_name ?? '');
        })->values();
        
        // Create a map of candidate email to vacancy titles (case-insensitive)
        $candidateVacancies = [];
        foreach ($matches as $match) {
            if ($match->candidate && $match->candidate->email && $match->vacancy) {
                $email = strtolower(trim($match->candidate->email));
                if (!isset($candidateVacancies[$email])) {
                    $candidateVacancies[$email] = [];
                }
                $candidateVacancies[$email][] = $match->vacancy->title;
            }
        }
        
        // Map by User email for display (use original case from User)
        $candidateVacanciesByUserEmail = [];
        foreach ($candidates as $user) {
            $emailKey = strtolower(trim($user->email ?? ''));
            if (isset($candidateVacancies[$emailKey])) {
                $candidateVacanciesByUserEmail[$user->email] = $candidateVacancies[$emailKey];
            }
        }
        $candidateVacancies = $candidateVacanciesByUserEmail;
        
        \Log::info('Notification create - Final candidates', [
            'final_candidates_count' => $candidates->count(),
            'final_candidate_emails' => $candidates->pluck('email')->toArray(),
            'candidate_vacancies' => $candidateVacancies,
        ]);
        
        // Get company with mainLocation
        $company = \App\Models\Company::with('mainLocation')->find($companyId);
        
        // Get company locations
        $companyLocations = \App\Models\CompanyLocation::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        return view('admin.notifications.create', compact('backendUsers', 'candidates', 'companyLocations', 'company', 'candidateVacancies'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties aan te maken.');
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'category' => 'required|in:info,warning,success,error,reminder,update',
            'type' => 'required|in:match,interview,application,system,email,reminder,file',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'read_at' => 'nullable|date',
            'action_url' => 'nullable|url',
            'data' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => ['nullable', 'string', 'regex:#^([0-1][0-9]|2[0-3]):[0-5][0-9]$#'],
            'location_id' => ['nullable', function ($attribute, $value, $fail) {
                // Allow "company_main" (will be converted to 0), "remote" or -1 (will be converted to -1), 0, null, or empty
                // Only validate numeric values that are not 0 or -1
                if ($value !== null && $value !== '' && $value !== '0' && $value !== 0 && $value !== 'company_main' && $value !== 'remote' && $value !== '-1' && $value !== -1) {
                    // Check if it's a valid numeric ID
                    if (!is_numeric($value)) {
                        $fail('De locatie ID moet een nummer zijn.');
                        return;
                    }
                    $exists = \App\Models\CompanyLocation::where('id', $value)->exists();
                    if (!$exists) {
                        $fail('De geselecteerde locatie bestaat niet.');
                    }
                }
            }],
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Check if user belongs to the same company or is a candidate with matches
        $user = \App\Models\User::find($request->user_id);
        $companyId = $this->getTenantId();
        
        if (!$user) {
            abort(404, 'Gebruiker niet gevonden.');
        }
        
        // Allow if user is from same company OR is a candidate with matches to company vacancies
        $isCompanyUser = $user->company_id === $companyId;
        
        // Check if user is a candidate with matches - find candidate by email
        $candidate = \App\Models\Candidate::where('email', $user->email)->first();
        $isCandidateWithMatches = $user->hasRole('candidate') && $candidate && 
            \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
                $vq->where('company_id', $companyId);
            })->where('candidate_id', $candidate->id)->exists();
        
        if (!$isCompanyUser && !$isCandidateWithMatches) {
            abort(403, 'Je kunt alleen notificaties maken voor gebruikers in je eigen bedrijf of kandidaten die hebben gesolliciteerd op je vacatures.');
        }

        $data = $request->except(['file', '_token', 'scheduled_time']);
        $data['company_id'] = $this->getTenantId();
        
        // Handle scheduled_at - use hidden input value if it contains date and time combined
        // Otherwise combine scheduled_at date and scheduled_time
        if ($request->filled('scheduled_at')) {
            $scheduledAtValue = $request->input('scheduled_at');
            // Check if it's already in Y-m-d H:i:s format (from hidden input)
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $scheduledAtValue)) {
                // Already combined, use as is (add seconds if missing)
                $data['scheduled_at'] = strlen($scheduledAtValue) === 16 ? $scheduledAtValue . ':00' : $scheduledAtValue;
            } else {
                // Only date, combine with time
                $time = $request->input('scheduled_time', '00:00');
                // Combine date and time: Y-m-d H:i:s
                $data['scheduled_at'] = $scheduledAtValue . ' ' . $time . ':00';
            }
        }
        
        // Handle location_id - if it's "company_main", set to 0 (main address identifier)
        // If it's "remote" or -1, set to -1 (special identifier for "Op afstand")
        if (isset($data['location_id']) && $data['location_id'] === 'company_main') {
            $data['location_id'] = 0;
        } elseif (isset($data['location_id']) && ($data['location_id'] === 'remote' || $data['location_id'] === '-1' || $data['location_id'] === -1)) {
            // For remote, use -1 as special identifier and store "Op afstand" in data
            $data['location_id'] = -1;
            if (!isset($notificationData['location_or_type'])) {
                $notificationData['location_or_type'] = 'Op afstand';
            }
        }
        
        // Add sender info to data
        $senderId = auth()->id();
        $senderEmail = auth()->user()->email;
        if (!isset($data['data']) || empty($data['data'])) {
            $notificationData = [
                'sender_id' => $senderId,
                'sender_email' => $senderEmail,
            ];
        } else {
            $notificationData = json_decode($data['data'], true) ?? [];
            $notificationData['sender_id'] = $senderId;
            $notificationData['sender_email'] = $senderEmail;
        }
        
        // If this is an interview notification for a candidate, try to find and store match_id
        // Also set requires_response to true for interview notifications to candidates
        if ($data['type'] === 'interview' && $user->hasRole('candidate')) {
            // Set requires_response to true for interview notifications to candidates
            $data['requires_response'] = true;
            
            $candidate = \App\Models\Candidate::where('email', $user->email)->first();
            if ($candidate) {
                // Find the match for this candidate and company
                $match = \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
                    $vq->where('company_id', $companyId);
                })->where('candidate_id', $candidate->id)->orderBy('created_at', 'desc')->first();
                
                if ($match) {
                    $notificationData['match_id'] = $match->id;
                }
            }
        }
        
        $data['data'] = json_encode($notificationData);
        
        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('notifications', $fileName, 'public');
            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }
        
        Notification::create($data);
        return redirect()->route('admin.notifications.index')->with('success', 'Notificatie succesvol aangemaakt.');
    }

    public function show(Notification $notification)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        // Get sender info from notification data
        $sender = null;
        if ($notification->data) {
            $data = json_decode($notification->data, true);
            if (is_array($data)) {
                if (isset($data['sender_id']) && $data['sender_id']) {
                    $sender = \App\Models\User::find($data['sender_id']);
                }
                if (!$sender && isset($data['sender_email']) && $data['sender_email']) {
                    $sender = \App\Models\User::where('email', $data['sender_email'])->first();
                }
                if (!$sender && isset($data['responder_id']) && $data['responder_id']) {
                    $sender = \App\Models\User::find($data['responder_id']);
                }
            }
        }
        
        return view('admin.notifications.show', compact('notification', 'sender'));
    }

    public function edit(Notification $notification)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        $companyId = $this->getTenantId();
        
        // Get backend users from the same company
        $backendUsers = \App\Models\User::where('company_id', $companyId)
            ->where('id', '!=', auth()->id())
            ->whereHas('roles', function($q) {
                $q->where('name', '!=', 'candidate');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        
        // Get candidates who have applied to vacancies of this company
        // Get matches with vacancy information
        $matches = \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
            $vq->where('company_id', $companyId);
        })->with(['vacancy', 'candidate'])->get();
        
        \Log::info('Notification create - Matches found', [
            'company_id' => $companyId,
            'matches_count' => $matches->count(),
            'match_ids' => $matches->pluck('id')->toArray(),
            'candidate_ids' => $matches->pluck('candidate_id')->unique()->toArray(),
        ]);
        
        // Get unique candidate IDs from matches
        $candidateIds = $matches->pluck('candidate_id')->unique()->filter()->values();
        
        \Log::info('Notification create - Candidate IDs from matches', [
            'candidate_ids' => $candidateIds->toArray(),
        ]);
        
        // Get candidates directly from candidates table
        $candidatesFromTable = \App\Models\Candidate::whereIn('id', $candidateIds)
            ->whereNotNull('email')
            ->get();
        
        \Log::info('Notification create - Candidates from table', [
            'candidates_count' => $candidatesFromTable->count(),
            'candidate_emails' => $candidatesFromTable->pluck('email')->toArray(),
        ]);
        
        // Get candidate emails (case-insensitive matching)
        $candidateEmails = $candidatesFromTable->pluck('email')
            ->map(function($email) {
                return strtolower(trim($email));
            })
            ->filter()
            ->unique()
            ->toArray();
        
        \Log::info('Notification create - Candidate emails (normalized)', [
            'candidate_emails' => $candidateEmails,
        ]);
        
        // Get ALL users with candidate role first
        $allCandidateUsers = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'candidate');
            })
            ->get();
        
        \Log::info('Notification create - All candidate users', [
            'all_candidate_users_count' => $allCandidateUsers->count(),
            'all_candidate_user_emails' => $allCandidateUsers->pluck('email')->map(fn($e) => strtolower(trim($e ?? '')))->toArray(),
        ]);
        
        // Get users with candidate role that match the candidate emails (case-insensitive)
        $usersWithCandidateRole = $allCandidateUsers->filter(function($user) use ($candidateEmails) {
            $userEmail = strtolower(trim($user->email ?? ''));
            $matches = in_array($userEmail, $candidateEmails);
            if ($matches) {
                \Log::info('Notification create - Matched user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'normalized_email' => $userEmail,
                ]);
            }
            return $matches;
        });
        
        // For candidates without User records, check if User exists or create them automatically
        $candidatesWithoutUsers = $candidatesFromTable->filter(function($candidate) use ($usersWithCandidateRole) {
            $candidateEmail = strtolower(trim($candidate->email ?? ''));
            return !$usersWithCandidateRole->contains(function($user) use ($candidateEmail) {
                return strtolower(trim($user->email ?? '')) === $candidateEmail;
            });
        });
        
        foreach ($candidatesWithoutUsers as $candidate) {
            if ($candidate->email) {
                $candidateEmail = strtolower(trim($candidate->email));
                
                // First check if User already exists (maybe without candidate role)
                $existingUser = \App\Models\User::whereRaw('LOWER(TRIM(email)) = ?', [$candidateEmail])->first();
                
                if ($existingUser) {
                    \Log::info('Notification create - User already exists, checking role', [
                        'user_id' => $existingUser->id,
                        'candidate_id' => $candidate->id,
                        'candidate_email' => $candidate->email,
                    ]);
                    
                    // Check if user has candidate role
                    if (!$existingUser->hasRole('candidate')) {
                        // Assign candidate role if missing
                        $candidateRole = Role::where('name', 'candidate')->first();
                        if ($candidateRole) {
                            $existingUser->assignRole($candidateRole);
                            \Log::info('Notification create - Assigned candidate role to existing user', [
                                'user_id' => $existingUser->id,
                            ]);
                        }
                    }
                    
                    $usersWithCandidateRole->push($existingUser);
                } else {
                    \Log::info('Notification create - Creating User for candidate', [
                        'candidate_id' => $candidate->id,
                        'candidate_email' => $candidate->email,
                    ]);
                    
                    // Create User record for candidate
                    $user = \App\Models\User::create([
                        'first_name' => $candidate->first_name ?? '',
                        'last_name' => $candidate->last_name ?? '',
                        'email' => $candidate->email,
                        'password' => bcrypt(str()->random(32)), // Random password, candidate will need to reset
                        'company_id' => null, // Candidates don't belong to a company
                    ]);
                    
                    // Assign candidate role
                    $candidateRole = Role::where('name', 'candidate')->first();
                    if ($candidateRole) {
                        $user->assignRole($candidateRole);
                    }
                    
                    $usersWithCandidateRole->push($user);
                    
                    \Log::info('Notification create - User created for candidate', [
                        'user_id' => $user->id,
                        'candidate_id' => $candidate->id,
                        'email' => $user->email,
                    ]);
                }
            }
        }
        
        \Log::info('Notification create - Matched candidate users', [
            'matched_users_count' => $usersWithCandidateRole->count(),
            'matched_user_emails' => $usersWithCandidateRole->pluck('email')->toArray(),
        ]);
        
        // Sort candidates by name
        $candidates = $usersWithCandidateRole->sortBy(function($user) {
            return ($user->first_name ?? '') . ' ' . ($user->last_name ?? '');
        })->values();
        
        // Create a map of candidate email to vacancy titles (case-insensitive)
        $candidateVacancies = [];
        foreach ($matches as $match) {
            if ($match->candidate && $match->candidate->email && $match->vacancy) {
                $email = strtolower(trim($match->candidate->email));
                if (!isset($candidateVacancies[$email])) {
                    $candidateVacancies[$email] = [];
                }
                $candidateVacancies[$email][] = $match->vacancy->title;
            }
        }
        
        // Map by User email for display (use original case from User)
        $candidateVacanciesByUserEmail = [];
        foreach ($candidates as $user) {
            $emailKey = strtolower(trim($user->email ?? ''));
            if (isset($candidateVacancies[$emailKey])) {
                $candidateVacanciesByUserEmail[$user->email] = $candidateVacancies[$emailKey];
            }
        }
        $candidateVacancies = $candidateVacanciesByUserEmail;
        
        \Log::info('Notification create - Final candidates', [
            'final_candidates_count' => $candidates->count(),
            'final_candidate_emails' => $candidates->pluck('email')->toArray(),
            'candidate_vacancies' => $candidateVacancies,
        ]);
        
        // Get company with mainLocation
        $company = \App\Models\Company::with('mainLocation')->find($companyId);
        
        // Get company locations
        $companyLocations = \App\Models\CompanyLocation::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        return view('admin.notifications.edit', compact('notification', 'backendUsers', 'candidates', 'companyLocations', 'company', 'candidateVacancies'));
    }

    public function update(Request $request, Notification $notification)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'category' => 'required|in:info,warning,success,error,reminder,update',
            'type' => 'required|in:match,interview,application,system,email,reminder,file',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'read_at' => 'nullable|date',
            'action_url' => 'nullable|url',
            'data' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => ['nullable', 'string', 'regex:#^([0-1][0-9]|2[0-3]):[0-5][0-9]$#'],
            'location_id' => ['nullable', function ($attribute, $value, $fail) {
                // Allow "company_main" (will be converted to 0), "remote" or -1 (will be converted to -1), 0, null, or empty
                // Only validate numeric values that are not 0 or -1
                if ($value !== null && $value !== '' && $value !== '0' && $value !== 0 && $value !== 'company_main' && $value !== 'remote' && $value !== '-1' && $value !== -1) {
                    // Check if it's a valid numeric ID
                    if (!is_numeric($value)) {
                        $fail('De locatie ID moet een nummer zijn.');
                        return;
                    }
                    $exists = \App\Models\CompanyLocation::where('id', $value)->exists();
                    if (!$exists) {
                        $fail('De geselecteerde locatie bestaat niet.');
                    }
                }
            }],
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Check if user belongs to the same company or is a candidate with matches
        $user = \App\Models\User::find($request->user_id);
        $companyId = $this->getTenantId();
        
        if (!$user) {
            abort(404, 'Gebruiker niet gevonden.');
        }
        
        // Allow if user is from same company OR is a candidate with matches to company vacancies
        $isCompanyUser = $user->company_id === $companyId;
        
        // Check if user is a candidate with matches - find candidate by email
        $candidate = \App\Models\Candidate::where('email', $user->email)->first();
        $isCandidateWithMatches = $user->hasRole('candidate') && $candidate && 
            \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
                $vq->where('company_id', $companyId);
            })->where('candidate_id', $candidate->id)->exists();
        
        if (!$isCompanyUser && !$isCandidateWithMatches) {
            abort(403, 'Je kunt alleen notificaties bewerken voor gebruikers in je eigen bedrijf of kandidaten die hebben gesolliciteerd op je vacatures.');
        }

        $data = $request->except(['file', '_token', '_method', 'scheduled_time']);
        
        // Handle scheduled_at - use hidden input value if it contains date and time combined
        // Otherwise combine scheduled_at date and scheduled_time
        if ($request->filled('scheduled_at')) {
            $scheduledAtValue = $request->input('scheduled_at');
            // Check if it's already in Y-m-d H:i:s format (from hidden input)
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $scheduledAtValue)) {
                // Already combined, use as is (add seconds if missing)
                $data['scheduled_at'] = strlen($scheduledAtValue) === 16 ? $scheduledAtValue . ':00' : $scheduledAtValue;
            } else {
                // Only date, combine with time
                $time = $request->input('scheduled_time', '00:00');
                // Combine date and time: Y-m-d H:i:s
                $data['scheduled_at'] = $scheduledAtValue . ' ' . $time . ':00';
            }
        } else {
            // If scheduled_at is not provided, set it to null to clear it
            $data['scheduled_at'] = null;
        }
        
        // Handle location_id - always process it, even if empty
        if ($request->has('location_id')) {
            $locationId = $request->input('location_id');
            if ($locationId === 'company_main') {
                // Store main address as 0
                $data['location_id'] = 0;
            } elseif ($locationId === 'remote' || $locationId === '-1' || $locationId === -1) {
                // For remote, use -1 as special identifier and store "Op afstand" in data
                $data['location_id'] = -1;
                // Get existing notification data or create new
                $notificationData = json_decode($notification->data, true) ?? [];
                $notificationData['location_or_type'] = 'Op afstand';
                $data['data'] = json_encode($notificationData);
            } elseif ($locationId === '' || $locationId === null) {
                $data['location_id'] = null;
            } else {
                $data['location_id'] = $locationId;
            }
        } else {
            // If location_id is not in request, set to null to clear it
            $data['location_id'] = null;
        }
        
        // Handle file upload
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($notification->file_path) {
                \Storage::disk('public')->delete($notification->file_path);
            }
            
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('notifications', $fileName, 'public');
            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }
        
        $notification->update($data);
        return redirect()->route('admin.notifications.index')->with('success', 'Notificatie succesvol bijgewerkt.');
    }

    public function destroy(Notification $notification)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-notifications')) {
            abort(403, 'Je hebt geen rechten om notificaties te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        $notification->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'Notificatie succesvol verwijderd.');
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification)
    {
        // Check if the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // For admin users, also check company access
        if (auth()->user()->company_id && !$this->canAccessResource($notification)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the authenticated user
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $query = Notification::where('user_id', $user->id)
            ->whereNull('read_at');
        
        // For admin users, filter by company
        if ($user->company_id) {
            $query->where('company_id', $this->getTenantId());
        }
        
        $query->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get unread notification count for the authenticated user
     */
    public function getUnreadCount()
    {
        $user = auth()->user();
        $query = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereNull('archived_at');
        
        // For admin users, filter by company
        if ($user->company_id) {
            $query->where('company_id', $this->getTenantId());
        }
        
        $unreadCount = $query->count();
        
        // Get highest priority of unread notifications
        $priorityQuery = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereNull('archived_at');
        
        if ($user->company_id) {
            $priorityQuery->where('company_id', $this->getTenantId());
        }
        
        $highestPriority = $priorityQuery->orderByRaw("CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END")
            ->value('priority');
        
        return response()->json([
            'unread_count' => $unreadCount,
            'highest_priority' => $highestPriority ?? 'normal'
        ]);
    }
    
    /**
     * Get notifications for the drawer
     */
    public function getNotifications(Request $request)
    {
        $user = auth()->user();
        $query = Notification::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->orderBy('created_at', 'desc')
            ->limit(50);
        
        // For admin users, filter by company
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        } else {
            // For frontend users (candidates), show all notifications for this user
            // regardless of company_id (candidates can receive notifications from any company)
            // No additional filtering needed - just user_id is enough
        }
        
        // Get company for main address lookup
        $companyId = $this->getTenantId();
        $company = \App\Models\Company::with('mainLocation')->find($companyId);
        
        $notifications = $query->get()->map(function($notification) use ($company) {
            $sender = null;
            $data = null;
            
            // Find sender - could be from company users or candidates
            if ($notification->data) {
                $data = json_decode($notification->data, true);
                if (is_array($data)) {
                    // Try sender_id first
                    if (isset($data['sender_id']) && $data['sender_id']) {
                        $sender = \App\Models\User::find($data['sender_id']);
                    }
                    
                    // If user not found, try to find by email
                    if (!$sender && isset($data['sender_email']) && $data['sender_email']) {
                        $sender = \App\Models\User::where('email', $data['sender_email'])->first();
                    }
                    
                    // If still no sender found, check if there's a responder_id (for interview responses)
                    if (!$sender && isset($data['responder_id']) && $data['responder_id']) {
                        $sender = \App\Models\User::find($data['responder_id']);
                    }
                }
            }
            
            // Build sender info - always return sender object if found
            $senderInfo = null;
            if ($sender) {
                $name = trim($sender->first_name . ' ' . $sender->last_name);
                if (empty($name)) {
                    $name = $sender->email ?? 'Onbekende gebruiker';
                }
                
                // Use secure photo token for avatar
                $avatarUrl = asset('assets/media/avatars/300-2.png');
                if ($sender->photo_blob) {
                    try {
                        $avatarUrl = route('secure.photo', ['token' => $sender->getPhotoToken()]);
                    } catch (\Exception $e) {
                        // Fallback to default on error
                        \Log::error('Error getting sender avatar URL in getNotifications: ' . $e->getMessage());
                    }
                }
                
                $senderInfo = [
                    'id' => $sender->id,
                    'name' => $name,
                    'email' => $sender->email ?? '',
                    'avatar' => $avatarUrl,
                ];
            }
            
            // Get location information if available
            $locationInfo = null;
            // Check for location_id - handle 0 (main address), -1 (Op afstand), and regular locations
            $locationId = $notification->location_id;
            if ($locationId !== null && ($locationId == 0 || $locationId === '0' || $locationId === 0)) {
                // Main company address - use already loaded company
                // If company not loaded yet, get it from notification's company_id
                if (!$company && $notification->company_id) {
                    $company = \App\Models\Company::with('mainLocation')->find($notification->company_id);
                }
                
                if ($company) {
                    // Check if company has a mainLocation
                    if ($company->mainLocation) {
                        $mainLoc = $company->mainLocation;
                        $locationAddress = trim(($mainLoc->street ?? '') . ' ' . ($mainLoc->house_number ?? '') . ($mainLoc->house_number_extension ? '-' . $mainLoc->house_number_extension : ''));
                        $locationAddress = trim($locationAddress . ' ' . ($mainLoc->postal_code ?? '') . ' ' . ($mainLoc->city ?? ''));
                        $locationInfo = [
                            'name' => $mainLoc->name . ' (Hoofdadres)',
                            'address' => $locationAddress,
                            'city' => $mainLoc->city,
                        ];
                    } else {
                        // Use company address fields directly
                        $locationAddress = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                        $locationAddress = trim($locationAddress . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                        $locationInfo = [
                            'name' => ($company->name ?? 'Hoofdadres') . ' (Hoofdadres)',
                            'address' => $locationAddress,
                            'city' => $company->city,
                        ];
                    }
                }
            } elseif ($locationId !== null && ($locationId == -1 || $locationId === '-1')) {
                // Special value -1 means "Op afstand" (remote)
                $locationInfo = [
                    'name' => 'Op afstand',
                    'address' => null,
                    'city' => null,
                ];
            } elseif ($locationId !== null && $locationId != 0 && $locationId !== '0' && $locationId != -1 && $locationId !== '-1') {
                // Regular company location
                $location = \App\Models\CompanyLocation::find($locationId);
                if ($location) {
                    $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                    $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                    $locationInfo = [
                        'name' => $location->name,
                        'address' => $locationAddress,
                        'city' => $location->city,
                    ];
                }
            }
            
            // Check if location_or_type contains "Op afstand" or other location info (for remote interviews or when location_id is null)
            // This should be checked AFTER location_id checks, but BEFORE the final fallback
            // Note: location_id -1 is already handled above, but we also check location_or_type as fallback
            if (isset($data['location_or_type'])) {
                // Convert "Telefoon", "Telefonisch", "Telefonische", or phone type to "Op afstand"
                if ($data['location_or_type'] === 'Op afstand' || 
                    $data['location_or_type'] === 'Telefoon' || 
                    $data['location_or_type'] === 'Telefonisch' ||
                    $data['location_or_type'] === 'Telefonische' ||
                    strtolower($data['location_or_type']) === 'phone') {
                    $locationInfo = [
                        'name' => 'Op afstand',
                        'address' => null,
                        'city' => null,
                    ];
                } elseif (!$locationInfo) {
                    // Fallback: use location_or_type if no locationInfo was set from location_id
                    $locationInfo = [
                        'name' => $data['location_or_type'],
                        'address' => null,
                        'city' => null,
                    ];
                }
            }
            
            // Format scheduled_at if available
            $scheduledAtFormatted = null;
            $scheduledDate = null;
            $scheduledTime = null;
            if ($notification->scheduled_at) {
                $scheduledAtFormatted = $notification->scheduled_at->format('d-m-Y H:i');
                $scheduledDate = $notification->scheduled_at->format('d-m-Y');
                $scheduledTime = $notification->scheduled_at->format('H:i');
            }
            
            // Check if notification has been responded to
            $hasResponse = false;
            $responseType = null;
            if ($data && is_array($data) && isset($data['response'])) {
                $hasResponse = true;
                $responseType = $data['response'];
            }
            
            // Extract match_id from data - check both direct match_id and nested structures
            $matchId = null;
            if ($data && is_array($data)) {
                if (isset($data['match_id'])) {
                    $matchId = $data['match_id'];
                }
            }
            
            // Check if interview already exists for this match_id (to hide "Inplannen" button)
            // Only count interviews that were actually scheduled by admin via "Inplannen" button
            // An interview scheduled via "Inplannen" has a notification_id in the interview's notification data
            // Also check if interview has a status (to hide Accept/Decline buttons)
            $interviewExists = false;
            $interviewHasStatus = false;
            $interview = null;
            if ($matchId) {
                // Check for interviews that were actually scheduled by admin
                // An interview scheduled by admin always has interviewer_name (required field)
                $interviews = \App\Models\Interview::where('match_id', $matchId)
                    ->whereNotNull('interviewer_name')
                    ->where('interviewer_name', '!=', '')
                    ->get();
                
                // Check if interview exists based on notification type
                if ($notification->title === 'Interview reactie' && $responseType === 'accept') {
                    // For "Interview reactie" notifications with accept, only count interviews created AFTER the response
                    // This ensures "Inplannen" button shows until admin actually schedules it
                    foreach ($interviews as $int) {
                        if ($int->created_at > $notification->created_at) {
                            $interview = $int;
                            $interviewExists = true;
                            $interviewHasStatus = !empty($int->status);
                            break;
                        }
                    }
                } else if ($notification->title === 'Interview ingepland') {
                    // For "Interview ingepland" notifications, check if the referenced interview exists
                    // This notification is created when admin schedules an interview via "Inplannen" button
                    // Also check if original_notification_id is set to link to the original notification
                    if (isset($data['interview_id'])) {
                        $referencedInterview = \App\Models\Interview::find($data['interview_id']);
                        if ($referencedInterview && $referencedInterview->match_id == $matchId) {
                            $interview = $referencedInterview;
                            $interviewExists = true; // Interview definitely exists - this is a confirmation notification
                            $interviewHasStatus = !empty($referencedInterview->status);
                        }
                    }
                    
                    // Get response data from original notification if linked via original_notification_id
                    if ($notification->original_notification_id) {
                        $originalNotification = \App\Models\Notification::find($notification->original_notification_id);
                        if ($originalNotification) {
                            $originalData = json_decode($originalNotification->data, true) ?? [];
                            if (isset($originalData['response'])) {
                                $hasResponse = true;
                                $responseType = $originalData['response'];
                            }
                        }
                    } elseif (isset($data['response'])) {
                        // Fallback: check if response is in the notification data itself
                        $hasResponse = true;
                        $responseType = $data['response'];
                    }
                } else {
                    // For original interview notifications (not "Interview reactie" or "Interview ingepland")
                    // Check if interview was created FROM this notification by looking for "Interview ingepland" notification
                    // Only set interview_exists = true if there's a "Interview ingepland" notification that references an interview
                    // This ensures interview_exists is only true when interview was actually scheduled via "Inplannen" button
                    // Check if there's a "Interview ingepland" notification for this match_id that was created after this notification
                    // Use original_notification_id to find the linked "Interview ingepland" notification
                    $interviewScheduledNotification = \App\Models\Notification::where('type', 'interview')
                        ->where('title', 'Interview ingepland')
                        ->where('user_id', $notification->user_id) // Same user (candidate)
                        ->where('original_notification_id', $notification->id) // Link to this original notification
                        ->where('created_at', '>', $notification->created_at)
                        ->first();
                    
                    if ($interviewScheduledNotification) {
                        $scheduledData = json_decode($interviewScheduledNotification->data, true);
                        if (isset($scheduledData['interview_id']) && isset($scheduledData['match_id']) && $scheduledData['match_id'] == $matchId) {
                            // Find the interview referenced in the "Interview ingepland" notification
                            $referencedInterview = \App\Models\Interview::find($scheduledData['interview_id']);
                            if ($referencedInterview && $referencedInterview->match_id == $matchId) {
                                $interview = $referencedInterview;
                                $interviewExists = true;
                                $interviewHasStatus = !empty($referencedInterview->status);
                            }
                        }
                    }
                }
                
                // Debug logging
                if ($notification->title === 'Interview reactie' && $responseType === 'accept') {
                    \Log::info('Checking interview_exists for notification', [
                        'notification_id' => $notification->id,
                        'notification_created_at' => $notification->created_at,
                        'match_id' => $matchId,
                        'interview_exists' => $interviewExists,
                        'interview_has_status' => $interviewHasStatus,
                        'interview_id' => $interview ? $interview->id : null,
                        'interview_created_at' => $interview ? $interview->created_at : null,
                        'interviewer_name' => $interview ? $interview->interviewer_name : null,
                        'total_interviews_found' => $interviews->count(),
                    ]);
                }
            }
            
            // If no locationInfo yet and we have an interview, try to get location from interview
            if (!$locationInfo && $interview) {
                if ($interview->location) {
                    // Check if location is "Op afstand", "Telefoon", "Telefonisch", "Telefonische", or if type is phone
                    if ($interview->location === 'Op afstand' || 
                        $interview->location === 'Telefoon' || 
                        $interview->location === 'Telefonisch' ||
                        $interview->location === 'Telefonische' ||
                        $interview->type === 'phone') {
                        $locationInfo = [
                            'name' => 'Op afstand',
                            'address' => null,
                            'city' => null,
                        ];
                    } else {
                        // Use interview location
                        $locationInfo = [
                            'name' => $interview->location,
                            'address' => null,
                            'city' => null,
                        ];
                    }
                } elseif ($interview->type === 'phone') {
                    // If no location but type is phone, use "Op afstand"
                    $locationInfo = [
                        'name' => 'Op afstand',
                        'address' => null,
                        'city' => null,
                    ];
                }
            }
            
            // Debug: Log if this is a response notification without match_id
            if ($notification->title === 'Interview reactie' && $responseType === 'accept' && !$matchId) {
                \Log::warning('Response notification without match_id in getNotifications', [
                    'notification_id' => $notification->id,
                    'data' => $data,
                    'raw_data' => $notification->data,
                ]);
            }
            
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'category' => $notification->category ?? 'info',
                'priority' => $notification->priority ?? 'normal',
                'read_at' => $notification->read_at,
                'is_read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $notification->created_at->diffForHumans(),
                'created_at_formatted' => $notification->created_at->format('d-m-Y H:i'),
                'sender' => $senderInfo, // Will be null if no sender found
                'action_url' => $notification->action_url,
                'requires_response' => $notification->type === 'interview' && !$hasResponse && $notification->requires_response !== false && !$interviewHasStatus,
                'has_response' => $hasResponse,
                'response_type' => $responseType,
                'file_path' => $notification->file_path ? \Storage::url($notification->file_path) : null,
                'file_name' => $notification->file_name,
                'file_size' => $notification->file_size,
                'scheduled_at' => $notification->scheduled_at ? $notification->scheduled_at->format('Y-m-d H:i:s') : null,
                'scheduled_at_formatted' => $scheduledAtFormatted,
                'scheduled_date' => $scheduledDate,
                'scheduled_time' => $scheduledTime,
                'location' => $locationInfo,
                'location_id' => $notification->location_id !== null ? (int)$notification->location_id : null, // Include location_id for prefilling (preserve 0 as 0, -1 as -1, not null)
                'match_id' => $matchId,
                'interview_exists' => $interviewExists, // Flag to hide button if interview already exists
                'interview_has_status' => $interviewHasStatus, // Flag to hide Accept/Decline buttons if interview has status
                'location_or_type' => isset($data['location_or_type']) ? $data['location_or_type'] : ($locationInfo ? $locationInfo['name'] : null), // Location or type from notification data, or fallback to locationInfo name
                'data' => $data, // Include full data object for access to match_id in response notifications
            ];
        });
        
        return response()->json($notifications);
    }
    
    /**
     * Mark selected notifications as read
     */
    public function markSelectedAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);
        
        $user = auth()->user();
        $query = Notification::where('user_id', $user->id)
            ->whereIn('id', $request->notification_ids);
        
        // For admin users, filter by company
        if ($user->company_id) {
            $query->where('company_id', $this->getTenantId());
        }
        
        $query->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Archive selected notifications
     */
    public function archiveSelected(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);
        
        $user = auth()->user();
        $query = Notification::where('user_id', $user->id)
            ->whereIn('id', $request->notification_ids);
        
        // For admin users, filter by company
        if ($user->company_id) {
            $query->where('company_id', $this->getTenantId());
        }
        
        $query->update(['archived_at' => now()]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Respond to interview notification (accept/decline)
     */
    public function respondToInterview(Request $request, Notification $notification)
    {
        // Check if the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        if ($notification->type !== 'interview') {
            return response()->json(['success' => false, 'message' => 'Not an interview notification'], 400);
        }
        
        $request->validate([
            'response' => 'required|in:accept,decline',
            'message' => 'nullable|string',
        ]);
        
        // Mark original as read and update data to mark as responded
        $notificationData = json_decode($notification->data, true) ?? [];
        $notificationData['response'] = $request->response;
        $notificationData['responded_at'] = now()->toIso8601String();
        $notification->update([
            'read_at' => now(),
            'data' => json_encode($notificationData),
        ]);
        
        // Get sender from notification data
        $senderId = $notificationData['sender_id'] ?? null;
        
        if ($senderId) {
            $sender = \App\Models\User::find($senderId);
            $companyId = $sender ? $sender->company_id : $this->getTenantId();
            
            // Build response message with appointment details
            $responseMessage = $request->response === 'accept' 
                ? 'Heeft je interview uitnodiging geaccepteerd.'
                : 'Heeft je interview uitnodiging afgewezen.';
            
            // Add appointment details to message
            $appointmentDetails = [];
            if ($notification->scheduled_at) {
                $appointmentDetails[] = 'Datum: ' . $notification->scheduled_at->format('d-m-Y H:i');
            }
            
            // Check notification data for location_or_type (for "Op afstand")
            // Also check if location_id is -1 (special value for "Op afstand")
            $notificationData = json_decode($notification->data, true);
            $locationOrType = $notificationData['location_or_type'] ?? null;
            
            if ($locationOrType === 'Op afstand' || $notification->location_id == -1 || $notification->location_id === -1) {
                $appointmentDetails[] = 'Locatie: Op afstand';
            } elseif ($notification->location_id && $notification->location_id != -1) {
                $location = \App\Models\CompanyLocation::find($notification->location_id);
                if ($location) {
                    $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                    $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                    $appointmentDetails[] = 'Locatie: ' . ($location->name ?? '') . ($locationAddress ? ' - ' . $locationAddress : '');
                } elseif ($notification->location_id == 0) {
                    // Main company address
                    $company = \App\Models\Company::find($companyId);
                    if ($company) {
                        $appointmentDetails[] = 'Locatie: ' . ($company->name ?? '') . ' - Hoofdadres';
                    }
                }
            }
            
            if (!empty($appointmentDetails)) {
                $responseMessage .= ' ' . implode(', ', $appointmentDetails);
            }
            
            if ($request->message) {
                $responseMessage .= ' Bericht: ' . $request->message;
            }
            
            // Prepare response notification data
            $responseNotificationData = [
                'original_notification_id' => $notification->id,
                'response' => $request->response,
                'responder_id' => auth()->id(),
                'scheduled_at' => $notification->scheduled_at ? $notification->scheduled_at->toIso8601String() : null,
                'location_id' => $notification->location_id,
            ];
            
            // If accepted, add match_id and set requires_response for "Inplannen" button
            $matchId = null;
            if ($request->response === 'accept') {
                // First try to get match_id from original notification data
                if (isset($notificationData['match_id'])) {
                    $matchId = $notificationData['match_id'];
                } else {
                    // If not in notification data, try to find it based on the candidate and company
                    // The responder is the candidate who accepted
                    $responder = auth()->user();
                    if ($responder) {
                        // Try to find candidate by email
                        $candidate = \App\Models\Candidate::where('email', $responder->email)->first();
                        if ($candidate) {
                            // Find the match for this candidate and company
                            $match = \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
                                $vq->where('company_id', $companyId);
                            })->where('candidate_id', $candidate->id)->orderBy('created_at', 'desc')->first();
                            
                            if ($match) {
                                $matchId = $match->id;
                            }
                        }
                    }
                }
                
                // Always add match_id to response notification data if found
                // This ensures it's available in the frontend
                if ($matchId) {
                    $responseNotificationData['match_id'] = $matchId;
                    \Log::info('Added match_id to response notification', [
                        'notification_id' => $notification->id,
                        'match_id' => $matchId,
                        'responder_id' => auth()->id(),
                    ]);
                } else {
                    // Log warning if match_id could not be found
                    \Log::warning('Could not find match_id for accepted interview notification', [
                        'notification_id' => $notification->id,
                        'responder_id' => auth()->id(),
                        'responder_email' => auth()->user()->email ?? null,
                        'company_id' => $companyId,
                        'notification_data' => $notificationData,
                    ]);
                }
            }
            
            Notification::create([
                'user_id' => $senderId,
                'company_id' => $companyId,
                'type' => 'interview',
                'category' => $request->response === 'accept' ? 'success' : 'warning',
                'title' => 'Interview reactie',
                'message' => auth()->user()->first_name . ' ' . auth()->user()->last_name . ' ' . $responseMessage,
                'priority' => 'normal',
                'scheduled_at' => $notification->scheduled_at,
                'location_id' => $notification->location_id,
                'data' => json_encode($responseNotificationData),
                'requires_response' => false, // No action buttons needed - admin will schedule interview
                'original_notification_id' => $notification->id, // Link to original interview notification
            ]);
            
            // Don't create interview automatically when candidate accepts
            // Interview will be created when admin clicks "Inplannen" button
            // This allows admin to schedule the interview with proper details
        }
        
        return response()->json(['success' => true]);
    }
}
