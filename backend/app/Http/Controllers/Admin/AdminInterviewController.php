<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Interview;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminInterviewController extends Controller
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
        
        return view('admin.interviews.index', compact('interviews', 'stats', 'companies'));
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
        
        $matchQuery = \App\Models\JobMatch::with(['candidate', 'vacancy.company']);
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
            $match = \App\Models\JobMatch::with('vacancy')->find($prefilledData['match_id']);
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
        
        return view('admin.interviews.create', compact('companies', 'matches', 'prefilledData', 'companyLocations', 'selectedCompany', 'companyUsers'));
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
            'notes' => 'nullable|string',
            'feedback' => 'nullable|string',
            'notification_id' => 'nullable|exists:notifications,id',
        ]);
        
        // Combine scheduled_at date and time if both are provided
        $data = $request->all();
        if ($request->filled('scheduled_at') && $request->filled('scheduled_time')) {
            $date = $request->input('scheduled_at');
            $time = $request->input('scheduled_time');
            // Combine date and time: Y-m-d H:i:s
            $data['scheduled_at'] = $date . ' ' . $time . ':00';
        } elseif ($request->filled('scheduled_at')) {
            // If only date is provided, set time to 00:00:00
            $data['scheduled_at'] = $request->input('scheduled_at') . ' 00:00:00';
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
            
            $match = \App\Models\JobMatch::findOrFail($request->match_id);
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
        
        // Log interviewer data before saving
        \Log::info('Saving interview with final data', [
            'company_location_id' => $data['company_location_id'] ?? null,
            'location' => $data['location'] ?? null,
            'interviewer_name' => $data['interviewer_name'] ?? null,
            'interviewer_email' => $data['interviewer_email'] ?? null,
        ]);
        
        $interview = Interview::create($data);
        
        // Log after creation to verify what was saved
        \Log::info('Interview created', [
            'interview_id' => $interview->id,
            'interviewer_name' => $interview->interviewer_name,
            'interviewer_email' => $interview->interviewer_email,
        ]);
        
        // Send email to candidate when interview is scheduled
        $match = \App\Models\JobMatch::with(['candidate', 'vacancy.company'])->find($data['match_id']);
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
                $match = \App\Models\JobMatch::with('candidate')->find($data['match_id']);
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
        
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol aangemaakt.');
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
        return view('admin.interviews.show', compact('interview'));
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
        
        return view('admin.interviews.edit', compact('interview', 'companies', 'companyUsers', 'companyLocations', 'selectedCompany'));
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
            'notes' => 'nullable|string',
            'feedback' => 'nullable|string',
        ]);

        $data = $request->all();
        
        // Combine scheduled_at date and time if both are provided
        if ($request->filled('scheduled_at') && $request->filled('scheduled_time')) {
            $date = $request->input('scheduled_at');
            $time = $request->input('scheduled_time');
            $data['scheduled_at'] = $date . ' ' . $time . ':00';
        } elseif ($request->filled('scheduled_at')) {
            $data['scheduled_at'] = $request->input('scheduled_at') . ' 00:00:00';
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
        
        // Log interviewer data before updating
        \Log::info('Updating interview with final data', [
            'company_location_id' => $data['company_location_id'] ?? null,
            'location' => $data['location'] ?? null,
            'interviewer_name' => $data['interviewer_name'] ?? null,
            'interviewer_email' => $data['interviewer_email'] ?? null,
        ]);
        
        $interview->update($data);
        
        // Log after update to verify what was saved
        \Log::info('Interview updated', [
            'interview_id' => $interview->id,
            'interviewer_name' => $interview->interviewer_name,
            'interviewer_email' => $interview->interviewer_email,
        ]);
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol bijgewerkt.');
    }

    public function destroy(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $interview->delete();
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol verwijderd.');
    }
}
