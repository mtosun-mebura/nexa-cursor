<?php

namespace App\Modules\Skillmatching\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Modules\Skillmatching\Models\Interview;
use App\Modules\Skillmatching\Models\JobMatch;
use App\Models\Company;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    use TenantFilter;
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bekijken.');
        }
        
        $query = Interview::with(['match.vacancy.company', 'company']);
        
        // Apply tenant filtering
        $query = $this->applyTenantFilter($query);
        
        // Filter op status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter op bedrijf (alleen voor super-admin)
        if ($request->filled('company') && auth()->user()->hasRole('super-admin')) {
            $query->where('company_id', $request->company);
        }
        
        // Filter op type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('match.candidate', function($candidateQuery) use ($search) {
                    $candidateQuery->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('match.vacancy', function($vacancyQuery) use ($search) {
                    $vacancyQuery->where('title', 'like', "%{$search}%");
                })
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
        
        // Sortering
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Valideer sorteer veld
        $allowedSortFields = ['id', 'match_id', 'company_id', 'scheduled_at', 'location', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // Set default direction based on sort field
        if (!$sortDirection || !in_array($sortDirection, ['asc', 'desc'])) {
            if (in_array($sortField, ['created_at', 'scheduled_at'])) {
                $sortDirection = 'desc';
            } else {
                $sortDirection = 'asc';
            }
        }
        
        // Speciale behandeling voor verschillende sorteervelden
        if ($sortField === 'vacancy_id') {
            $query->join('matches', 'interviews.match_id', '=', 'matches.id')
                  ->orderBy('matches.vacancy_id', $sortDirection)
                  ->select('interviews.*');
        } elseif ($sortField === 'status') {
            // Sorteer op status met logische volgorde: Niet gepland, Gepland, Afgelopen
            $query->orderByRaw("
                CASE 
                    WHEN scheduled_at IS NULL THEN 1
                    WHEN scheduled_at > NOW() THEN 2
                    WHEN scheduled_at <= NOW() THEN 3
                END " . $sortDirection
            )->orderBy('id', 'asc');
        } else {
            $query->orderBy($sortField, $sortDirection)->orderBy('id', 'asc');
        }
        
        // Load all interviews for client-side pagination
        $interviews = $query->get();
        
        // Calculate statistics
        $statsQuery = Interview::query();
        $statsQuery = $this->applyTenantFilter($statsQuery);
        
        $stats = [
            'total_interviews' => (clone $statsQuery)->count(),
            'scheduled' => (clone $statsQuery)->whereNotNull('scheduled_at')->where('scheduled_at', '>', now())->count(),
            'past' => (clone $statsQuery)->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now())->count(),
            'not_scheduled' => (clone $statsQuery)->whereNull('scheduled_at')->count(),
        ];
        
        // Get companies for filter (only for super-admin)
        $companies = auth()->user()->hasRole('super-admin') ? Company::orderBy('name')->get() : collect();
        
        return view('skillmatching::admin.interviews.index', compact('interviews', 'stats', 'companies'));
    }

    public function create(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews aan te maken.');
        }
        
        // Haal bedrijven en matches op basis van tenant filtering
        $companyQuery = Company::query();
        $companyQuery = $this->applyTenantFilter($companyQuery);
        $companies = $companyQuery->get();
        
        $matchQuery = JobMatch::with(['candidate', 'vacancy.company']);
        if (!auth()->user()->hasRole('super-admin') || session('selected_tenant')) {
            $tenantId = $this->getTenantId();
            $matchQuery->whereHas('vacancy', function($q) use ($tenantId) {
                $q->where('company_id', $tenantId);
            });
        }
        $matches = $matchQuery->get();
        
        // Get prefilled data from query parameters (from notification)
        $prefilledData = [
            'match_id' => $request->query('match_id'),
            'notification_id' => $request->query('notification_id'),
            'scheduled_at' => $request->query('scheduled_at'),
            'scheduled_date' => $request->query('scheduled_date'),
            'scheduled_time' => $request->query('scheduled_time'),
            'location_id' => $request->query('location_id'), // Can be -1 or "remote" for "Op afstand"
            'company_id' => $request->query('company_id'),
        ];
        
        // If match_id is provided, try to get company_id from the match
        if ($prefilledData['match_id'] && !$prefilledData['company_id']) {
            $match = JobMatch::with('vacancy')->find($prefilledData['match_id']);
            if ($match && $match->vacancy) {
                $prefilledData['company_id'] = $match->vacancy->company_id;
            }
        }
        
        // Get company locations for the selected company (or first company for super-admin)
        $companyLocations = collect();
        $selectedCompany = null;
        $selectedCompanyId = $prefilledData['company_id'] ?? null;
        
        if (!$selectedCompanyId && !auth()->user()->hasRole('super-admin')) {
            // For non-super-admin, use their company
            $selectedCompanyId = auth()->user()->company_id;
        }
        
        if ($selectedCompanyId) {
            $selectedCompany = \App\Models\Company::with('mainLocation')->find($selectedCompanyId);
            if ($selectedCompany) {
                $companyLocations = \App\Models\CompanyLocation::where('company_id', $selectedCompanyId)
                    ->where('is_active', true)
                    ->orderBy('is_main', 'desc')
                    ->orderBy('name')
                    ->get();
            }
        }
        
        // Get company users for interviewer dropdown
        $companyUsers = collect();
        if ($selectedCompanyId) {
            $companyUsers = \App\Models\User::where('company_id', $selectedCompanyId)
                ->whereDoesntHave('roles', function($q) {
                    $q->where('name', 'super-admin');
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }
        
        return view('skillmatching::admin.interviews.create', compact('companies', 'matches', 'prefilledData', 'companyLocations', 'selectedCompany', 'companyUsers'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews aan te maken.');
        }
        
        $request->validate([
            'match_id' => 'required|exists:matches,id',
            'company_id' => 'required|exists:companies,id',
            'type' => 'required|in:phone,video,onsite,assessment,final',
            'scheduled_at' => 'required|date',
            'scheduled_time' => 'nullable|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'duration' => 'nullable|integer|min:15|max:480',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,rescheduled',
            'company_location_id' => ['nullable', function ($attribute, $value, $fail) {
                // Allow -1 for "Op afstand" (remote), 0 for main address, null, or valid company_location_id
                if ($value !== null && $value !== '' && $value !== '0' && $value !== 0 && $value !== '-1' && $value !== -1 && $value !== 'remote') {
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
            'location' => 'nullable|string|max:255',
            'interviewer_name' => 'required|string|max:255',
            'interviewer_email' => 'required|email|max:255',
            'interviewer_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'feedback' => 'nullable|string',
            'notification_id' => 'nullable|exists:notifications,id',
        ]);
        
        // Combine scheduled_at date and time if both are provided
        $data = $request->all();
        if ($request->filled('scheduled_at')) {
            $scheduledAt = $request->input('scheduled_at');
            $time = $request->input('scheduled_time');
            
            // Check if scheduled_at already contains time (format: Y-m-d H:i or Y-m-d H:i:s)
            $alreadyHasTime = preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $scheduledAt);
            
            if ($alreadyHasTime) {
                // scheduled_at already has time, use it directly (add seconds if needed)
                if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $scheduledAt)) {
                    $data['scheduled_at'] = $scheduledAt . ':00';
                } else {
                    $data['scheduled_at'] = $scheduledAt;
                }
            } elseif ($time) {
                // scheduled_at is date only, combine with scheduled_time
                $data['scheduled_at'] = $scheduledAt . ' ' . $time . ':00';
            } else {
                // No time provided, use midnight
                $data['scheduled_at'] = $scheduledAt . ' 00:00:00';
            }
        }
        
        // Handle company_location_id - location_id 0 = hoofdadres, "remote" = Op afstand
        // If it's "0" or 0, treat as main address and set company_location_id to null
        // If it's "remote", "-1", or -1, treat as "Op afstand" and set company_location_id to null
        if (isset($data['company_location_id'])) {
            $locationId = $data['company_location_id'];
            
            // Check for remote/Op afstand first
            if ($locationId === 'remote' || $locationId === '-1' || $locationId === -1) {
                // Remote interview (Op afstand) - set company_location_id to null (foreign key constraint)
                $data['location'] = 'Op afstand';
                $data['company_location_id'] = null; // Set to null for remote (interviews table has foreign key constraint)
            } elseif ($locationId === '0' || $locationId === 0 || $locationId === '') {
                // Main company address (location_id 0 = hoofdadres)
                $company = \App\Models\Company::with('mainLocation')->find($data['company_id']);
                if ($company) {
                    if ($company->mainLocation) {
                        $mainLoc = $company->mainLocation;
                        $address = trim(($mainLoc->street ?? '') . ' ' . ($mainLoc->house_number ?? '') . ($mainLoc->house_number_extension ? '-' . $mainLoc->house_number_extension : ''));
                        $address = trim($address . ' ' . ($mainLoc->postal_code ?? '') . ' ' . ($mainLoc->city ?? ''));
                        $data['location'] = $mainLoc->name . ($address ? ' - ' . $address : '');
                    } else {
                        $address = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                        $address = trim($address . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                        $data['location'] = $company->name . ($address ? ' - ' . $address : '');
                    }
                    $data['company_location_id'] = null; // Set to null for main address (location_id 0)
                }
            } elseif (!empty($locationId)) {
                // Regular company location
                $location = \App\Models\CompanyLocation::find($data['company_location_id']);
                if ($location) {
                    $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                    $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                    $data['location'] = $location->name . ($locationAddress ? ' - ' . $locationAddress : '');
                }
            }
        }

        // Voor non-super-admin gebruikers: controleer of het bedrijf en match bij hun bedrijf horen
        if (!auth()->user()->hasRole('super-admin')) {
            if ($request->company_id != auth()->user()->company_id) {
                abort(403, 'Je kunt alleen interviews aanmaken voor je eigen bedrijf.');
            }
            
            $match = JobMatch::findOrFail($request->match_id);
            if ($match->vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je kunt alleen interviews aanmaken voor matches van je eigen bedrijf.');
            }
        }

        // Final check: ensure company_location_id is never -1 (foreign key constraint)
        if (isset($data['company_location_id']) && ($data['company_location_id'] === -1 || $data['company_location_id'] === '-1')) {
            $data['company_location_id'] = null;
            if (empty($data['location'])) {
                $data['location'] = 'Op afstand';
            }
        }
        
        // Ensure interviewer_user_id is also set in user_id for backward compatibility
        if (isset($data['interviewer_user_id']) && !isset($data['user_id'])) {
            $data['user_id'] = $data['interviewer_user_id'];
        }
        
        // Log interviewer data before saving
        \Log::info('Saving interview with final data', [
            'company_location_id' => $data['company_location_id'] ?? null,
            'location' => $data['location'] ?? null,
            'interviewer_name' => $data['interviewer_name'] ?? null,
            'interviewer_email' => $data['interviewer_email'] ?? null,
            'interviewer_user_id' => $data['interviewer_user_id'] ?? null,
        ]);
        
        $interview = Interview::create($data);
        
        // Log after creation to verify what was saved
        \Log::info('Interview created', [
            'interview_id' => $interview->id,
            'interviewer_name' => $interview->interviewer_name,
            'interviewer_email' => $interview->interviewer_email,
        ]);
        
        // Send email to candidate when interview is scheduled
        $match = JobMatch::with(['candidate', 'vacancy.company'])->find($data['match_id']);
        if ($match && $match->candidate && $match->vacancy) {
            try {
                $emailService = app(\App\Services\EmailTemplateService::class);
                $emailService->sendInterviewScheduledEmail($match->candidate, $match->vacancy, $interview);
            } catch (\Exception $e) {
                \Log::error('Failed to send interview scheduled email', [
                    'candidate_email' => $match->candidate->email ?? 'unknown',
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // If interview was created from a notification, send notification to candidate
        $notificationId = $request->input('notification_id');
        if ($notificationId) {
            \Log::info('Creating interview from notification', [
                'notification_id' => $notificationId,
                'match_id' => $data['match_id'],
                'interview_id' => $interview->id,
            ]);
            
            $notification = \App\Models\Notification::find($notificationId);
            if ($notification) {
                // Get candidate user from the match
                $match = JobMatch::with('candidate')->find($data['match_id']);
                if ($match && $match->candidate) {
                    $candidateUser = \App\Models\User::where('email', $match->candidate->email)->first();
                    if ($candidateUser) {
                        // Format scheduled date and time
                        $scheduledDate = \Carbon\Carbon::parse($data['scheduled_at'])->format('d-m-Y');
                        $scheduledTime = \Carbon\Carbon::parse($data['scheduled_at'])->format('H:i');
                        
                        // Get status label in Dutch
                        $statusMap = [
                            'scheduled' => 'Gepland',
                            'confirmed' => 'Bevestigd',
                            'completed' => 'Voltooid',
                            'cancelled' => 'Geannuleerd',
                            'rescheduled' => 'Herpland',
                        ];
                        $statusLabel = $statusMap[$data['status']] ?? $data['status'];
                        
                        // Get type label in Dutch
                        $typeMap = [
                            'phone' => 'Telefoon',
                            'video' => 'Video',
                            'onsite' => 'Op locatie',
                            'assessment' => 'Assessment',
                            'final' => 'Eindgesprek',
                        ];
                        $typeLabel = $typeMap[$data['type']] ?? $data['type'];
                        
                        // Build message
                        $message = "Je geaccepteerde afspraak heeft de status {$statusLabel} gekregen.";
                        
                        // Store location or type in notification data for display in Afspraakdetails
                        // If location is empty and type is phone, use "Op afstand" instead of "Telefoon"
                        $locationOrType = !empty($data['location']) ? $data['location'] : $typeLabel;
                        if (empty($data['location']) && $data['type'] === 'phone') {
                            $locationOrType = 'Op afstand';
                        }
                        
                        // Get response data from the "Interview reactie" notification
                        // The notification_id passed is the "Interview reactie" notification that the admin received
                        $originalResponse = null;
                        $originalResponseType = null;
                        $originalRespondedAt = null;
                        
                        // Check if this notification is an "Interview reactie" notification
                        if ($notification->title === 'Interview reactie') {
                            $originalNotificationData = json_decode($notification->data, true) ?? [];
                            if (isset($originalNotificationData['response'])) {
                                $originalResponse = true;
                                $originalResponseType = $originalNotificationData['response'];
                                $originalRespondedAt = $originalNotificationData['responded_at'] ?? ($notification->updated_at ? $notification->updated_at->toIso8601String() : now()->toIso8601String());
                            }
                        }
                        
                        $notificationData = [
                            'sender_id' => auth()->id(),
                            'sender_email' => auth()->user()->email,
                            'match_id' => $data['match_id'],
                            'interview_id' => $interview->id,
                            'location_or_type' => $locationOrType,
                            'original_notification_id' => $notificationId,
                        ];
                        
                        // If original notification had a response, include it in the new notification data
                        // This ensures has_response and response_type are set correctly for "Interview ingepland" notifications
                        if ($originalResponse && $originalResponseType) {
                            $notificationData['response'] = $originalResponseType;
                            $notificationData['responded_at'] = $originalRespondedAt;
                        }
                        
                        // Find the original interview notification that was sent to the candidate
                        // This is the notification that the candidate responded to (accept/decline)
                        $originalInterviewNotification = null;
                        if ($notification->title === 'Interview reactie') {
                            // If the notification_id is a "Interview reactie" notification, find the original
                            // The original_notification_id in the "Interview reactie" notification points to the original
                            if ($notification->original_notification_id) {
                                // Use the original_notification_id from the notification itself (database column)
                                $originalInterviewNotification = \App\Models\Notification::find($notification->original_notification_id);
                            } else {
                                // Fallback: check data field
                                $originalNotificationData = json_decode($notification->data, true) ?? [];
                                if (isset($originalNotificationData['original_notification_id'])) {
                                    $originalInterviewNotification = \App\Models\Notification::find($originalNotificationData['original_notification_id']);
                                }
                            }
                        } else {
                            // If notification_id is the original interview notification, use it directly
                            $originalInterviewNotification = $notification;
                        }
                        
                        // Create notification for candidate (no requires_response - this is a confirmation, not a request)
                        \App\Models\Notification::create([
                            'user_id' => $candidateUser->id,
                            'company_id' => $data['company_id'],
                            'type' => 'interview',
                            'category' => 'success',
                            'title' => 'Interview ingepland',
                            'message' => $message,
                            'priority' => 'normal',
                            'scheduled_at' => $data['scheduled_at'],
                            'location_id' => $data['company_location_id'] ?? null,
                            'requires_response' => false, // No action buttons needed - this is a confirmation
                            'data' => json_encode($notificationData),
                            'original_notification_id' => $originalInterviewNotification ? $originalInterviewNotification->id : null, // Link to original interview notification
                        ]);
                        
                        \Log::info('Notification sent to candidate', [
                            'candidate_user_id' => $candidateUser->id,
                            'match_id' => $data['match_id'],
                            'interview_id' => $interview->id,
                        ]);
                    } else {
                        \Log::warning('Candidate user not found for interview notification', [
                            'candidate_email' => $match->candidate->email,
                            'match_id' => $data['match_id'],
                        ]);
                    }
                } else {
                    \Log::warning('Match or candidate not found for interview notification', [
                        'match_id' => $data['match_id'],
                    ]);
                }
            } else {
                \Log::warning('Original notification not found', [
                    'notification_id' => $notificationId,
                ]);
            }
        }
        
        return redirect()->route('admin.skillmatching.interviews.index')->with('success', 'Interview succesvol aangemaakt.');
    }

    public function show(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $interview->load(['match.vacancy.company', 'company']);
        return view('skillmatching::admin.interviews.show', compact('interview'));
    }

    public function edit(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $companyQuery = Company::query();
        $companyQuery = $this->applyTenantFilter($companyQuery);
        $companies = $companyQuery->get();
        $interview->load(['match.vacancy.company', 'company']);
        
        // Get matches for the match dropdown (with tenant filtering)
        $matchQuery = JobMatch::with(['candidate', 'vacancy.company']);
        if (!auth()->user()->hasRole('super-admin') || session('selected_tenant')) {
            $tenantId = $this->getTenantId();
            $matchQuery->whereHas('vacancy', function($q) use ($tenantId) {
                $q->where('company_id', $tenantId);
            });
        }
        $matches = $matchQuery->get();
        
        // Get company users for interviewer dropdown
        $companyUsers = collect();
        $companyLocations = collect();
        $selectedCompany = null;
        $companyId = $interview->company_id;
        if ($companyId) {
            $selectedCompany = \App\Models\Company::with('mainLocation')->find($companyId);
            $companyUsers = \App\Models\User::where('company_id', $companyId)
                ->whereDoesntHave('roles', function($q) {
                    $q->where('name', 'super-admin');
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
            
            if ($selectedCompany) {
                $companyLocations = \App\Models\CompanyLocation::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->orderBy('is_main', 'desc')
                    ->orderBy('name')
                    ->get();
            }
        }
        
        return view('skillmatching::admin.interviews.edit', compact('interview', 'companies', 'matches', 'companyUsers', 'companyLocations', 'selectedCompany'));
    }

    public function update(Request $request, Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        // Store original values before update for change detection
        $originalScheduledAt = $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i') : null;
        $originalLocation = $interview->location;
        $originalDuration = $interview->duration;
        
        $request->validate([
            'match_id' => 'required|exists:matches,id',
            'type' => 'required|in:phone,video,onsite,assessment,final',
            'scheduled_at' => 'required|date',
            'scheduled_time' => 'nullable|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'duration' => 'nullable|integer|min:15|max:480',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,rescheduled',
            'company_location_id' => ['nullable', function ($attribute, $value, $fail) {
                // Allow -1 for "Op afstand" (remote), 0 for main address, null, or valid company_location_id
                if ($value !== null && $value !== '' && $value !== '0' && $value !== 0 && $value !== '-1' && $value !== -1 && $value !== 'remote') {
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
            'location' => 'nullable|string|max:255',
            'interviewer_name' => 'required|string|max:255',
            'interviewer_email' => 'required|email|max:255',
            'interviewer_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'feedback' => 'nullable|string',
        ]);

        $data = $request->all();
        
        // Combine scheduled_at date and time if both are provided
        if ($request->filled('scheduled_at')) {
            $scheduledAt = $request->input('scheduled_at');
            $time = $request->input('scheduled_time');
            
            // Check if scheduled_at already contains time (format: Y-m-d H:i or Y-m-d H:i:s)
            $alreadyHasTime = preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $scheduledAt);
            
            if ($alreadyHasTime) {
                // scheduled_at already has time, use it directly (add seconds if needed)
                if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $scheduledAt)) {
                    $data['scheduled_at'] = $scheduledAt . ':00';
                } else {
                    $data['scheduled_at'] = $scheduledAt;
                }
            } elseif ($time) {
                // scheduled_at is date only, combine with scheduled_time
                $data['scheduled_at'] = $scheduledAt . ' ' . $time . ':00';
            } else {
                // No time provided, use midnight
                $data['scheduled_at'] = $scheduledAt . ' 00:00:00';
            }
        }
        
        // Handle company_location_id - location_id 0 = hoofdadres, "remote" = Op afstand
        // If it's "0" or 0, treat as main address and set company_location_id to null
        // If it's "remote", "-1", or -1, treat as "Op afstand" and set company_location_id to null
        if (isset($data['company_location_id'])) {
            $locationId = $data['company_location_id'];
            
            // Check for remote/Op afstand first
            if ($locationId === 'remote' || $locationId === '-1' || $locationId === -1) {
                // Remote interview (Op afstand) - set company_location_id to null (foreign key constraint)
                $data['location'] = 'Op afstand';
                $data['company_location_id'] = null; // Set to null for remote (interviews table has foreign key constraint)
            } elseif ($locationId === '0' || $locationId === 0 || $locationId === '') {
                // Main company address (location_id 0 = hoofdadres)
                $company = \App\Models\Company::with('mainLocation')->find($interview->company_id);
                if ($company) {
                    if ($company->mainLocation) {
                        $mainLoc = $company->mainLocation;
                        $address = trim(($mainLoc->street ?? '') . ' ' . ($mainLoc->house_number ?? '') . ($mainLoc->house_number_extension ? '-' . $mainLoc->house_number_extension : ''));
                        $address = trim($address . ' ' . ($mainLoc->postal_code ?? '') . ' ' . ($mainLoc->city ?? ''));
                        $data['location'] = $mainLoc->name . ($address ? ' - ' . $address : '');
                    } else {
                        $address = trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : ''));
                        $address = trim($address . ' ' . ($company->postal_code ?? '') . ' ' . ($company->city ?? ''));
                        $data['location'] = $company->name . ($address ? ' - ' . $address : '');
                    }
                    $data['company_location_id'] = null; // Set to null for main address (location_id 0)
                }
            } elseif (!empty($locationId)) {
                // Regular company location
                $location = \App\Models\CompanyLocation::find($data['company_location_id']);
                if ($location) {
                    $locationAddress = trim(($location->street ?? '') . ' ' . ($location->house_number ?? '') . ($location->house_number_extension ? '-' . $location->house_number_extension : ''));
                    $locationAddress = trim($locationAddress . ' ' . ($location->postal_code ?? '') . ' ' . ($location->city ?? ''));
                    $data['location'] = $location->name . ($locationAddress ? ' - ' . $locationAddress : '');
                }
            }
        }
        
        // Remove scheduled_time from data as it's now combined with scheduled_at
        unset($data['scheduled_time']);
        
        // Final check: ensure company_location_id is never -1 (foreign key constraint)
        if (isset($data['company_location_id']) && ($data['company_location_id'] === -1 || $data['company_location_id'] === '-1')) {
            $data['company_location_id'] = null;
            if (empty($data['location'])) {
                $data['location'] = 'Op afstand';
            }
        }
        
        // Ensure interviewer_user_id is also set in user_id for backward compatibility
        if (isset($data['interviewer_user_id']) && !isset($data['user_id'])) {
            $data['user_id'] = $data['interviewer_user_id'];
        }
        
        // Log interviewer data before updating
        \Log::info('Updating interview with final data', [
            'company_location_id' => $data['company_location_id'] ?? null,
            'location' => $data['location'] ?? null,
            'interviewer_name' => $data['interviewer_name'] ?? null,
            'interviewer_email' => $data['interviewer_email'] ?? null,
            'interviewer_user_id' => $data['interviewer_user_id'] ?? null,
        ]);
        
        $interview->update($data);
        
        // Log after update to verify what was saved
        \Log::info('Interview updated', [
            'interview_id' => $interview->id,
            'interviewer_name' => $interview->interviewer_name,
            'interviewer_email' => $interview->interviewer_email,
        ]);
        
        // Refresh the model to get updated values
        $interview->refresh();
        
        // Check if date/time, location or duration changed and send notification to candidate
        $newScheduledAt = $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i') : null;
        $newLocation = $interview->location;
        $newDuration = $interview->duration;
        
        $dateTimeChanged = $originalScheduledAt !== $newScheduledAt;
        $locationChanged = $originalLocation !== $newLocation;
        $durationChanged = $originalDuration != $newDuration; // Use != for type coercion
        
        \Log::info('Interview change detection', [
            'interview_id' => $interview->id,
            'original_scheduled_at' => $originalScheduledAt,
            'new_scheduled_at' => $newScheduledAt,
            'dateTimeChanged' => $dateTimeChanged,
            'original_location' => $originalLocation,
            'new_location' => $newLocation,
            'locationChanged' => $locationChanged,
            'original_duration' => $originalDuration,
            'new_duration' => $newDuration,
            'durationChanged' => $durationChanged,
        ]);
        
        if ($dateTimeChanged || $locationChanged || $durationChanged) {
            // Get the candidate user from the match (lookup by email)
            $match = $interview->match;
            if ($match && $match->candidate) {
                $candidateUser = \App\Models\User::where('email', $match->candidate->email)->first();
                
                \Log::info('Looking for candidate user', [
                    'match_id' => $match->id,
                    'candidate_email' => $match->candidate->email,
                    'candidate_user_found' => $candidateUser ? true : false,
                ]);
                
                if ($candidateUser) {
                    // Format duration nicely
                    $formatDuration = function($mins) {
                        if (!$mins) return 'Niet opgegeven';
                        $hours = floor($mins / 60);
                        $minutes = $mins % 60;
                        if ($hours == 0) return "0:" . str_pad($minutes, 2, '0', STR_PAD_LEFT);
                        if ($minutes == 0) return "{$hours} uur";
                        return "{$hours}:" . str_pad($minutes, 2, '0', STR_PAD_LEFT);
                    };
                    
                    // Build the change message
                    $changes = [];
                    
                    if ($dateTimeChanged) {
                        $oldDateTime = $originalScheduledAt ? \Carbon\Carbon::parse($originalScheduledAt)->format('d-m-Y H:i') : 'Niet gepland';
                        $newDateTime = $newScheduledAt ? \Carbon\Carbon::parse($newScheduledAt)->format('d-m-Y H:i') : 'Niet gepland';
                        $changes[] = "Datum/tijd: {$oldDateTime} → {$newDateTime}";
                    }
                    
                    if ($locationChanged) {
                        $oldLoc = $originalLocation ?: 'Niet opgegeven';
                        $newLoc = $newLocation ?: 'Niet opgegeven';
                        $changes[] = "Locatie: {$oldLoc} → {$newLoc}";
                    }
                    
                    if ($durationChanged) {
                        $changes[] = "Duur: {$formatDuration($originalDuration)} → {$formatDuration($newDuration)}";
                    }
                    
                    $changesText = implode("\n", $changes);
                    
                    // Calculate the new appointment time (from-to)
                    $duration = $interview->duration ?? 60;
                    $startTime = $interview->scheduled_at ? $interview->scheduled_at->format('H:i') : null;
                    $endTime = $interview->scheduled_at ? $interview->scheduled_at->copy()->addMinutes($duration)->format('H:i') : null;
                    $appointmentDate = $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y') : null;
                    
                    // Get location info for notification - use the location text field from interview
                    $locationText = $newLocation ?: '';
                    $locationOrType = $locationText ?: null;
                    
                    // Build the appointment info lines (with extra line break before for separation)
                    $appointmentInfo = '';
                    if ($appointmentDate && $startTime && $endTime) {
                        $appointmentInfo = "\n\nNieuwe afspraak:";
                        $appointmentInfo .= "\nDatum/tijd: {$appointmentDate} van {$startTime} tot {$endTime}";
                        if ($locationText) {
                            $appointmentInfo .= "\nLocatie: {$locationText}";
                        }
                    }
                    
                    // Create notification for candidate with accept/decline capability
                    $notification = \App\Models\Notification::create([
                        'user_id' => $candidateUser->id,
                        'company_id' => $interview->company_id,
                        'type' => 'interview',
                        'category' => 'warning',
                        'title' => 'Interview gewijzigd',
                        'message' => "Er is een wijziging in je interview afspraak.\n\n{$changesText}{$appointmentInfo}",
                        'priority' => 'high',
                        'action_url' => '/agenda',
                        'scheduled_at' => $interview->scheduled_at,
                        'location_id' => $interview->company_location_id,
                        'requires_response' => true, // Enable accept/decline buttons
                        'data' => json_encode([
                            'sender_id' => auth()->id(), // Admin who made the change
                            'interview_id' => $interview->id,
                            'match_id' => $interview->match_id,
                            'location_or_type' => $locationOrType,
                            'is_change_notification' => true,
                        ]),
                    ]);
                    
                    \Log::info('Interview change notification sent to candidate', [
                        'interview_id' => $interview->id,
                        'notification_id' => $notification->id,
                        'candidate_user_id' => $candidateUser->id,
                        'changes' => $changes,
                    ]);
                }
            } else {
                \Log::warning('Could not find match or candidate for interview notification', [
                    'interview_id' => $interview->id,
                    'match_id' => $interview->match_id,
                    'match_found' => $match ? true : false,
                    'candidate_found' => ($match && $match->candidate) ? true : false,
                ]);
            }
        }
        
        return redirect()->route('admin.skillmatching.interviews.index')->with('success', 'Interview succesvol bijgewerkt.');
    }

    public function destroy(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-interviews')) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Je hebt geen rechten om interviews te verwijderen.'], 403);
            }
            abort(403, 'Je hebt geen rechten om interviews te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Je hebt geen toegang tot dit interview.'], 403);
            }
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        // Store interview details before deletion for notification
        $scheduledAt = $interview->scheduled_at;
        $location = $interview->location;
        $companyId = $interview->company_id;
        $matchId = $interview->match_id;
        
        // Try to find the candidate to send notification
        $candidateUser = null;
        if ($matchId) {
            $match = JobMatch::with('candidate')->find($matchId);
            if ($match && $match->candidate) {
                $candidateUser = \App\Models\User::where('email', $match->candidate->email)->first();
            }
        }
        
        $interviewId = $interview->id;
        
        $interview->delete();
        
        // Update any notifications that reference this interview to mark it as deleted
        // This prevents the "Afspraak verwijderen" button from appearing again
        $notificationsToUpdate = \App\Models\Notification::where('data', 'like', '%"interview_id":' . $interviewId . '%')
            ->orWhere('data', 'like', '%"interview_id":"' . $interviewId . '"%')
            ->get();
        
        foreach ($notificationsToUpdate as $notif) {
            $notifData = json_decode($notif->data, true) ?? [];
            $notifData['interview_deleted'] = true;
            $notif->update(['data' => json_encode($notifData)]);
        }
        
        // Send notification to candidate that their interview has been cancelled
        if ($candidateUser) {
            $message = "Je interview afspraak is geannuleerd.";
            
            // Add details if available
            $details = [];
            if ($scheduledAt) {
                $details[] = "Datum/tijd: " . $scheduledAt->format('d-m-Y H:i');
            }
            if ($location) {
                $details[] = "Locatie: " . $location;
            }
            
            if (!empty($details)) {
                $message .= "\n\n" . implode("\n", $details);
            }
            
            \App\Models\Notification::create([
                'user_id' => $candidateUser->id,
                'company_id' => $companyId,
                'type' => 'interview',
                'category' => 'danger',
                'title' => 'Interview geannuleerd',
                'message' => $message,
                'priority' => 'high',
                'action_url' => '/agenda',
                'data' => json_encode([
                    'sender_id' => auth()->id(),
                    'cancelled_by' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                ]),
            ]);
            
            \Log::info('Interview cancellation notification sent to candidate', [
                'candidate_user_id' => $candidateUser->id,
                'cancelled_by' => auth()->id(),
            ]);
        }
        
        // Return JSON response for AJAX requests
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Interview succesvol verwijderd.']);
        }
        
        return redirect()->route('admin.skillmatching.interviews.index')->with('success', 'Interview succesvol verwijderd.');
    }
}
