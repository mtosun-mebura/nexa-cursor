<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Vacancy;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Branch;
use App\Models\User;
use App\Models\JobConfiguration;
use App\Models\JobConfigurationType;
use App\Models\Candidate;
use App\Models\JobMatch;
use App\Models\Application;
use App\Models\Interview;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use App\Models\StageInstance;
use Illuminate\Support\Facades\DB;

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
        
        // Get users for contact person dropdown (only if user has create-users permission or is super-admin)
        $users = collect();
        $currentUser = auth()->user();
        $selectedCompanyId = session('selected_tenant') ?: ($companies->first()?->id);
        
        if ($currentUser->hasRole('super-admin')) {
            // Super admin: show users from selected company only (exclude super-admin users)
            // If no company is selected, show no users (they will be loaded dynamically when company is selected)
            if ($selectedCompanyId) {
                $users = User::where('company_id', $selectedCompanyId)
                    ->whereDoesntHave('roles', function($q) {
                        $q->where('name', 'super-admin');
                    })
                    ->orderBy('first_name')->orderBy('last_name')->get();
            }
            // If no company selected, $users remains empty - will be populated via JavaScript when company is selected
        } elseif ($currentUser->can('create-users') && $currentUser->company_id) {
            // Company admin with create-users permission: show users from their company (exclude super-admin users)
            $users = User::where('company_id', $currentUser->company_id)
                ->whereDoesntHave('roles', function($q) {
                    $q->where('name', 'super-admin');
                })
                ->orderBy('first_name')->orderBy('last_name')->get();
        }
        
        // Get job configurations dynamically based on active types
        $companyId = session('selected_tenant') ?: $currentUser->company_id;
        $types = JobConfigurationType::active()->ordered()->get();
        
        $configurationsByType = [];
        foreach ($types as $type) {
            $values = JobConfiguration::where(function($q) use ($type) {
                $q->where('type_id', $type->id)->orWhere('type', $type->name);
            })
            ->where(function($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->pluck('value')
            ->unique()
            ->values();
            
            // Special sorting for working_hours: numeric values first (low to high), then non-numeric
            if ($type->name === 'working_hours') {
                $values = $values->sort(function($a, $b) {
                    $aNum = is_numeric($a) ? (int)$a : PHP_INT_MAX;
                    $bNum = is_numeric($b) ? (int)$b : PHP_INT_MAX;
                    
                    if ($aNum !== PHP_INT_MAX && $bNum !== PHP_INT_MAX) {
                        return $aNum <=> $bNum;
                    } elseif ($aNum !== PHP_INT_MAX) {
                        return -1;
                    } elseif ($bNum !== PHP_INT_MAX) {
                        return 1;
                    } else {
                        return strcmp($a, $b);
                    }
                })->values();
            } elseif (in_array($type->name, ['salary_bruto_per_maand', 'salary_zzp_uurtarief', 'salary_bruto_per_uur', 'salary_bruto_per_jaar'])) {
                // Special sorting for salary ranges: extract min value and sort low to high
                $values = $values->sort(function($a, $b) {
                    // Extract minimum value from range (e.g., "0–50" -> 0, "50–75" -> 50, "150+" -> 150)
                    $extractMin = function($str) {
                        // Remove any non-numeric characters except digits, dash, and plus
                        $str = trim($str);
                        // Match first number in the string
                        if (preg_match('/^(\d+)/', $str, $matches)) {
                            return (int)$matches[1];
                        }
                        // If it ends with +, try to extract the number before it
                        if (preg_match('/(\d+)\+$/', $str, $matches)) {
                            return (int)$matches[1];
                        }
                        return PHP_INT_MAX;
                    };
                    
                    $aMin = $extractMin($a);
                    $bMin = $extractMin($b);
                    
                    if ($aMin !== PHP_INT_MAX && $bMin !== PHP_INT_MAX) {
                        return $aMin <=> $bMin;
                    } elseif ($aMin !== PHP_INT_MAX) {
                        return -1;
                    } elseif ($bMin !== PHP_INT_MAX) {
                        return 1;
                    } else {
                        return strcmp($a, $b);
                    }
                })->values();
            } else {
                $values = $values->sort()->values();
            }
            
            $configurationsByType[$type->name] = $values;
        }
        
        // For backward compatibility, also set individual variables
        $employmentTypes = $configurationsByType['employment_type'] ?? collect();
        $workingHours = $configurationsByType['working_hours'] ?? collect();
        $statuses = $configurationsByType['status'] ?? collect();
        $salaryBrutoPerMaand = $configurationsByType['salary_bruto_per_maand'] ?? collect();
        $salaryZzpUurtarief = $configurationsByType['salary_zzp_uurtarief'] ?? collect();
        
        // Get company locations and main address
        $companyLocations = collect();
        $selectedCompany = null;
        if ($currentUser->hasRole('super-admin')) {
            // For super admin, get locations for selected company or first company
            $selectedCompanyId = session('selected_tenant') ?: ($companies->first()?->id);
            if ($selectedCompanyId) {
                $selectedCompany = Company::find($selectedCompanyId);
                $companyLocations = CompanyLocation::where('company_id', $selectedCompanyId)
                    ->where('is_active', true)
                    ->orderBy('is_main', 'desc')
                    ->orderBy('name')
                    ->get();
            }
        } else {
            // For company user, get locations for their company
            if ($currentUser->company_id) {
                $selectedCompany = Company::find($currentUser->company_id);
                $companyLocations = CompanyLocation::where('company_id', $currentUser->company_id)
                    ->where('is_active', true)
                    ->orderBy('is_main', 'desc')
                    ->orderBy('name')
                    ->get();
            }
        }
        
        return view('admin.vacancies.create', compact('companies', 'branches', 'users', 'employmentTypes', 'workingHours', 'statuses', 'salaryBrutoPerMaand', 'salaryZzpUurtarief', 'companyLocations', 'selectedCompany'));
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
            'contact_user_id' => 'nullable|exists:users,id',
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

        $vacancyData = $request->except(['contact_photo', '_token', '_method']);

        // Handle contact person: set from contact_user_id or default to current user
        $contactUser = null;
        if ($request->filled('contact_user_id')) {
            $contactUser = User::find($request->input('contact_user_id'));
        }
        
        // If no contact user selected and user has no create-users permission, use current user
        if (!$contactUser && !auth()->user()->can('create-users') && !auth()->user()->hasRole('super-admin')) {
            $contactUser = auth()->user();
        }
        
        // Set contact person data from user
        if ($contactUser) {
            $vacancyData['contact_user_id'] = $contactUser->id;
            $vacancyData['contact_name'] = trim(($contactUser->first_name ?? '') . ' ' . ($contactUser->middle_name ?? '') . ' ' . ($contactUser->last_name ?? ''));
            $vacancyData['contact_email'] = $contactUser->email;
            $vacancyData['contact_phone'] = $contactUser->phone;
            $vacancyData['contact_photo_blob'] = $contactUser->photo_blob;
            $vacancyData['contact_photo_mime_type'] = $contactUser->photo_mime_type;
        } else {
            // Fallback: use current user if no contact user selected
            $currentUser = auth()->user();
            $vacancyData['contact_user_id'] = $currentUser->id;
            $vacancyData['contact_name'] = trim(($currentUser->first_name ?? '') . ' ' . ($currentUser->middle_name ?? '') . ' ' . ($currentUser->last_name ?? ''));
            $vacancyData['contact_email'] = $currentUser->email;
            $vacancyData['contact_phone'] = $currentUser->phone;
            $vacancyData['contact_photo_blob'] = $currentUser->photo_blob;
            $vacancyData['contact_photo_mime_type'] = $currentUser->photo_mime_type;
        }

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
        
        // Parse dates from dd-MM-yyyy format
        if (isset($vacancyData['publication_date']) && !empty($vacancyData['publication_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $vacancyData['publication_date'])) {
                    $vacancyData['publication_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $vacancyData['publication_date'])->format('Y-m-d');
                } else {
                    $vacancyData['publication_date'] = \Carbon\Carbon::parse($vacancyData['publication_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse publication_date', ['input' => $vacancyData['publication_date'], 'error' => $e->getMessage()]);
            }
        }
        
        if (isset($vacancyData['closing_date']) && !empty($vacancyData['closing_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $vacancyData['closing_date'])) {
                    $vacancyData['closing_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $vacancyData['closing_date'])->format('Y-m-d');
                } else {
                    $vacancyData['closing_date'] = \Carbon\Carbon::parse($vacancyData['closing_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse closing_date', ['input' => $vacancyData['closing_date'], 'error' => $e->getMessage()]);
            }
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
        
        // Load matches with candidates and match scores
        $matches = \App\Models\JobMatch::where('vacancy_id', $vacancy->id)
            ->with(['candidate'])
            ->orderBy('match_score', 'desc')
            ->get();
        
        // Load applications (candidates who responded directly)
        $applications = \App\Models\Application::where('vacancy_id', $vacancy->id)
            ->with(['candidate'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $vacancy->load(['company', 'branch', 'contactUser']);
        
        return view('admin.vacancies.show', compact('vacancy', 'matches', 'applications'));
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
        
        // Get users for contact person dropdown (only if user has create-users permission or is super-admin)
        $users = collect();
        $currentUser = auth()->user();
        $selectedCompanyId = $vacancy->company_id ?: (session('selected_tenant') ?: ($companies->first()?->id));
        
        if ($currentUser->hasRole('super-admin')) {
            // Super admin: show users from selected company (exclude super-admin users)
            if ($selectedCompanyId) {
                $users = User::where('company_id', $selectedCompanyId)
                    ->whereDoesntHave('roles', function($q) {
                        $q->where('name', 'super-admin');
                    })
                    ->orderBy('first_name')->orderBy('last_name')->get();
            }
        } elseif ($currentUser->can('create-users') && $currentUser->company_id) {
            // Company admin with create-users permission: show users from their company (exclude super-admin users)
            $users = User::where('company_id', $currentUser->company_id)
                ->whereDoesntHave('roles', function($q) {
                    $q->where('name', 'super-admin');
                })
                ->orderBy('first_name')->orderBy('last_name')->get();
        }
        
        // Get job configurations dynamically based on active types
        $companyId = $selectedCompanyId;
        $types = JobConfigurationType::active()->ordered()->get();
        
        $configurationsByType = [];
        foreach ($types as $type) {
            $values = JobConfiguration::where(function($q) use ($type) {
                $q->where('type_id', $type->id)->orWhere('type', $type->name);
            })
            ->where(function($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->pluck('value')
            ->unique()
            ->values();
            
            // Special sorting for working_hours: numeric values first (low to high), then non-numeric
            if ($type->name === 'working_hours') {
                $values = $values->sort(function($a, $b) {
                    $aNum = is_numeric($a) ? (int)$a : PHP_INT_MAX;
                    $bNum = is_numeric($b) ? (int)$b : PHP_INT_MAX;
                    
                    if ($aNum !== PHP_INT_MAX && $bNum !== PHP_INT_MAX) {
                        return $aNum <=> $bNum;
                    } elseif ($aNum !== PHP_INT_MAX) {
                        return -1;
                    } elseif ($bNum !== PHP_INT_MAX) {
                        return 1;
                    } else {
                        return strcmp($a, $b);
                    }
                })->values();
            } elseif (in_array($type->name, ['salary_bruto_per_maand', 'salary_zzp_uurtarief', 'salary_bruto_per_uur', 'salary_bruto_per_jaar'])) {
                // Special sorting for salary ranges: extract min value and sort low to high
                $values = $values->sort(function($a, $b) {
                    // Extract minimum value from range (e.g., "0–50" -> 0, "50–75" -> 50, "150+" -> 150)
                    $extractMin = function($str) {
                        // Remove any non-numeric characters except digits, dash, and plus
                        $str = trim($str);
                        // Match first number in the string
                        if (preg_match('/^(\d+)/', $str, $matches)) {
                            return (int)$matches[1];
                        }
                        // If it ends with +, try to extract the number before it
                        if (preg_match('/(\d+)\+$/', $str, $matches)) {
                            return (int)$matches[1];
                        }
                        return PHP_INT_MAX;
                    };
                    
                    $aMin = $extractMin($a);
                    $bMin = $extractMin($b);
                    
                    if ($aMin !== PHP_INT_MAX && $bMin !== PHP_INT_MAX) {
                        return $aMin <=> $bMin;
                    } elseif ($aMin !== PHP_INT_MAX) {
                        return -1;
                    } elseif ($bMin !== PHP_INT_MAX) {
                        return 1;
                    } else {
                        return strcmp($a, $b);
                    }
                })->values();
            } else {
                $values = $values->sort()->values();
            }
            
            $configurationsByType[$type->name] = $values;
        }
        
        // For backward compatibility, also set individual variables
        $employmentTypes = $configurationsByType['employment_type'] ?? collect();
        $workingHours = $configurationsByType['working_hours'] ?? collect();
        $statuses = $configurationsByType['status'] ?? collect();
        $salaryBrutoPerMaand = $configurationsByType['salary_bruto_per_maand'] ?? collect();
        $salaryZzpUurtarief = $configurationsByType['salary_zzp_uurtarief'] ?? collect();
        
        return view('admin.vacancies.edit', compact('vacancy', 'companies', 'branches', 'users', 'employmentTypes', 'workingHours', 'statuses', 'salaryBrutoPerMaand', 'salaryZzpUurtarief'));
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
                'contact_user_id' => 'nullable|exists:users,id',
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

        $vacancyData = $request->except(['contact_photo', '_token', '_method']);

        // Handle contact person: set from contact_user_id or default to current user
        $contactUser = null;
        if ($request->filled('contact_user_id')) {
            $contactUser = User::find($request->input('contact_user_id'));
        }
        
        // If no contact user selected and user has no create-users permission, use current user
        if (!$contactUser && !auth()->user()->can('create-users') && !auth()->user()->hasRole('super-admin')) {
            $contactUser = auth()->user();
        }
        
        // Set contact person data from user
        if ($contactUser) {
            $vacancyData['contact_user_id'] = $contactUser->id;
            $vacancyData['contact_name'] = trim(($contactUser->first_name ?? '') . ' ' . ($contactUser->middle_name ?? '') . ' ' . ($contactUser->last_name ?? ''));
            $vacancyData['contact_email'] = $contactUser->email;
            $vacancyData['contact_phone'] = $contactUser->phone;
            $vacancyData['contact_photo_blob'] = $contactUser->photo_blob;
            $vacancyData['contact_photo_mime_type'] = $contactUser->photo_mime_type;
        } else {
            // Fallback: use current user if no contact user selected
            $currentUser = auth()->user();
            $vacancyData['contact_user_id'] = $currentUser->id;
            $vacancyData['contact_name'] = trim(($currentUser->first_name ?? '') . ' ' . ($currentUser->middle_name ?? '') . ' ' . ($currentUser->last_name ?? ''));
            $vacancyData['contact_email'] = $currentUser->email;
            $vacancyData['contact_phone'] = $currentUser->phone;
            $vacancyData['contact_photo_blob'] = $currentUser->photo_blob;
            $vacancyData['contact_photo_mime_type'] = $currentUser->photo_mime_type;
        }

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
        
        // Parse dates from dd-MM-yyyy format
        if (isset($vacancyData['publication_date']) && !empty($vacancyData['publication_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $vacancyData['publication_date'])) {
                    $vacancyData['publication_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $vacancyData['publication_date'])->format('Y-m-d');
                } else {
                    $vacancyData['publication_date'] = \Carbon\Carbon::parse($vacancyData['publication_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse publication_date', ['input' => $vacancyData['publication_date'], 'error' => $e->getMessage()]);
            }
        }
        
        if (isset($vacancyData['closing_date']) && !empty($vacancyData['closing_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $vacancyData['closing_date'])) {
                    $vacancyData['closing_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $vacancyData['closing_date'])->format('Y-m-d');
                } else {
                    $vacancyData['closing_date'] = \Carbon\Carbon::parse($vacancyData['closing_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse closing_date', ['input' => $vacancyData['closing_date'], 'error' => $e->getMessage()]);
            }
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

    public function getContactPhoto(Vacancy $vacancy)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }
        
        if (!$vacancy->contact_photo_blob) {
            abort(404);
        }
        
        $content = base64_decode($vacancy->contact_photo_blob);
        $mimeType = $vacancy->contact_photo_mime_type ?: 'image/jpeg';
        
        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ]);
    }

    public function showCandidate(Vacancy $vacancy, Candidate $candidate, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-vacancies')) {
            abort(403, 'Je hebt geen rechten om vacatures te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        // Get match or application based on type
        $match = null;
        $application = null;
        $type = $request->get('type', 'match');
        
        if ($type === 'match' && $request->has('match_id')) {
            $match = JobMatch::where('id', $request->match_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->with(['interviews'])
                ->firstOrFail();
        } elseif ($type === 'application' && $request->has('application_id')) {
            $application = Application::where('id', $request->application_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();
        } else {
            // Try to find match or application
            $match = JobMatch::where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->with(['interviews'])
                ->first();
            
            if (!$match) {
                $application = Application::where('vacancy_id', $vacancy->id)
                    ->where('candidate_id', $candidate->id)
                    ->first();
            }
        }

        // Get timeline/activity history
        $timeline = $this->getCandidateTimeline($candidate, $vacancy, $match, $application);

        $vacancy->load(['company', 'branch']);
        
        // Get interviews for this match/application
        $interviews = collect();
        if ($match) {
            $interviews = \App\Models\Interview::where('match_id', $match->id)
                ->orderBy('scheduled_at', 'desc')
                ->get();
        }
        
        // Get the most recent scheduled interview
        $latestInterview = $interviews->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->sortByDesc('scheduled_at')
            ->first();
        
        // Get company locations for location dropdown
        $companyLocations = [];
        $company = null;
        if ($vacancy->company) {
            $companyLocations = \App\Models\CompanyLocation::where('company_id', $vacancy->company_id)
                ->orderBy('name')
                ->get();
            $company = $vacancy->company;
        }
        
        // Get company users for interviewer dropdown
        $companyUsers = [];
        if ($vacancy->company) {
            $companyUsers = \App\Models\User::where('company_id', $vacancy->company_id)
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }
        
        // Get stage instances for this match/application
        $stageInstances = collect();
        $pipelineTemplate = null;
        if ($match) {
            $stageInstances = \App\Models\StageInstance::where('match_id', $match->id)
                ->orderBy('sequence')
                ->get();
            if ($stageInstances->isNotEmpty()) {
                $pipelineTemplate = $stageInstances->first()->pipelineTemplate;
            }
        } elseif ($application) {
            $stageInstances = \App\Models\StageInstance::where('application_id', $application->id)
                ->orderBy('sequence')
                ->get();
            if ($stageInstances->isNotEmpty()) {
                $pipelineTemplate = $stageInstances->first()->pipelineTemplate;
            }
        }
        
        // Get available pipeline templates for initialization
        $availableTemplates = \App\Models\PipelineTemplate::where(function($query) use ($vacancy) {
            if ($vacancy->company_id) {
                $query->where('company_id', $vacancy->company_id)
                      ->orWhere('company_id', null);
            } else {
                $query->where('company_id', null);
            }
        })
        ->where('is_active', true)
        ->orderBy('is_default', 'desc')
        ->orderBy('name')
        ->get();
        
        return view('admin.vacancies.candidate', compact('vacancy', 'candidate', 'match', 'application', 'type', 'timeline', 'companyLocations', 'companyUsers', 'interviews', 'latestInterview', 'stageInstances', 'pipelineTemplate', 'availableTemplates', 'company'));
    }

    public function scheduleInterview(Vacancy $vacancy, Candidate $candidate, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews aan te maken.');
        }

        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        // Log all request data for debugging
        \Log::info('Interview request data', [
            'all_input' => $request->all(),
            'scheduled_at' => $request->input('scheduled_at'),
            'scheduled_at_hidden' => $request->input('scheduled_at_hidden'),
            'scheduled_time' => $request->input('scheduled_time'),
        ]);
        
        $request->validate([
            'scheduled_at' => 'nullable|string', // Changed from required|date to allow custom parsing
            'scheduled_time' => 'required|string',
            'type' => 'required|in:phone,video,onsite,assessment,final',
            'duration' => 'required|integer|min:15|max:480',
            'location_type' => 'required|string',
            'location' => 'nullable|string|max:255',
            'interviewer_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Allow "other" value - skip database validation
                    if ($value === 'other') {
                        return;
                    }
                    // Value is required, so it cannot be empty
                    if (empty($value)) {
                        $fail('Interviewer is verplicht.');
                        return;
                    }
                    // Only validate if it's a numeric ID
                    if (!is_numeric($value)) {
                        $fail('De geselecteerde interviewer is ongeldig.');
                        return;
                    }
                    // Check if user exists - only for numeric values
                    $userId = (int)$value;
                    if (!\App\Models\User::find($userId)) {
                        $fail('De geselecteerde interviewer is ongeldig.');
                    }
                },
            ],
            'interviewer_name' => 'nullable|string|max:255',
            'interviewer_name_custom' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u', // Alleen letters, spaties, streepjes, apostrofs en punten
                'required_if:interviewer_id,other',
            ],
            'interviewer_email' => [
                'nullable',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                function ($attribute, $value, $fail) use ($request) {
                    // If interviewer_id is "other", email is required
                    if ($request->input('interviewer_id') === 'other' && empty($value)) {
                        $fail('E-mailadres is verplicht wanneer een externe interviewer wordt ingevoerd.');
                    }
                    // If interviewer_id is a user ID, email should be filled automatically
                    // If interviewer_id is "other" and email is provided, validate format
                    if (!empty($value) && !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value)) {
                        $fail('E-mailadres moet een geldig e-mailadres zijn.');
                    }
                },
            ],
            'notes' => 'nullable|string',
            'match_id' => 'nullable|exists:matches,id',
            'application_id' => 'nullable|exists:applications,id',
        ]);

        // Get or create match
        $match = null;
        if ($request->filled('match_id')) {
            $match = JobMatch::where('id', $request->match_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();
        } else {
            // Try to find existing match
            $match = JobMatch::where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->first();
            
            // If no match exists, create one
            if (!$match) {
                $match = JobMatch::create([
                    'vacancy_id' => $vacancy->id,
                    'candidate_id' => $candidate->id,
                    'match_score' => 0,
                    'status' => 'interview',
                ]);
            } else {
                $match->update(['status' => 'interview']);
            }
        }

        // Parse scheduled_at from dd-MM-yyyy format and combine with time
        $scheduledAt = null;
        
        // Get date from request - check both hidden (yyyy-mm-dd) and display (dd-mm-yyyy) inputs
        $dateInput = $request->input('scheduled_at') ?? $request->input('scheduled_at_hidden');
        $timeInput = $request->input('scheduled_time');
        
        if (empty($dateInput)) {
            return redirect()->back()->withErrors(['scheduled_at' => 'Datum is verplicht.']);
        }
        
        try {
            $date = null;
            
            // Try to parse dd-MM-yyyy format (from datepicker display)
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateInput)) {
                $date = \Carbon\Carbon::createFromFormat('d-m-Y', $dateInput);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
                // Also accept yyyy-mm-dd format (database format)
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateInput);
            } else {
                // Fallback to Carbon's flexible parsing
                $date = \Carbon\Carbon::parse($dateInput);
            }
            
            // Add time if provided
            if (!empty($timeInput)) {
                $time = trim($timeInput);
                // Validate time format HH:mm
                if (preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
                    $timeParts = explode(':', $time);
                    $date->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                } else {
                    // Try to parse as HHmm or other formats
                    $timeDigits = preg_replace('/[^\d]/', '', $time);
                    if (strlen($timeDigits) >= 2) {
                        $hours = (int)substr($timeDigits, 0, 2);
                        $minutes = strlen($timeDigits) >= 4 ? (int)substr($timeDigits, 2, 2) : 0;
                        if ($hours <= 23 && $minutes <= 59) {
                            $date->setTime($hours, $minutes, 0);
                        } else {
                            // Invalid time, default to start of day
                            $date->startOfDay();
                        }
                    } else {
                        // Invalid time format, default to start of day
                        $date->startOfDay();
                    }
                }
            } else {
                // Default to start of day if no time provided
                $date->startOfDay();
            }
            
            $scheduledAt = $date;
            
            \Log::info('Parsed scheduled_at', [
                'date_input' => $dateInput,
                'time_input' => $timeInput,
                'parsed_datetime' => $scheduledAt->format('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to parse scheduled_at', [
                'date_input' => $dateInput,
                'time_input' => $timeInput,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['scheduled_at' => 'Ongeldige datum of tijd opgegeven: ' . $e->getMessage()]);
        }

        // Determine location value and company_location_id
        $location = null;
        $companyLocationId = null;
        
        if ($request->filled('location_type')) {
            if ($request->location_type === 'online') {
                // Online/Digital selected
                $location = 'Online / Digitaal';
                $companyLocationId = null;
            } elseif ($request->location_type === 'other') {
                // Custom address entered
                $location = $request->location;
                $companyLocationId = null;
            } else {
                // Company location selected
                $companyLocation = \App\Models\CompanyLocation::find($request->location_type);
                if ($companyLocation) {
                    $companyLocationId = $companyLocation->id;
                    $location = $companyLocation->name . ($companyLocation->address ? ' - ' . $companyLocation->address : '');
                }
            }
        }
        
        // Determine interviewer info and interviewer_user_id
        $interviewerName = null;
        $interviewerEmail = null;
        $interviewerUserId = null;
        
        if ($request->filled('interviewer_id')) {
            if ($request->interviewer_id === 'other') {
                // Custom interviewer name entered
                $interviewerName = $request->interviewer_name_custom;
                $interviewerUserId = null;
            } else {
                // User selected from dropdown
                $interviewer = \App\Models\User::find($request->interviewer_id);
                if ($interviewer) {
                    $interviewerUserId = $interviewer->id;
                    $interviewerName = trim(($interviewer->first_name ?? '') . ' ' . ($interviewer->last_name ?? ''));
                    $interviewerEmail = $interviewer->email;
                }
            }
        }
        
        // Create interview
        try {
            \Log::info('Attempting to create interview', [
                'match_id' => $match->id,
                'company_id' => $vacancy->company_id,
                'type' => $request->type,
                'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : null,
                'duration' => $request->duration ?? 60,
                'location' => $location,
                'company_location_id' => $companyLocationId,
                'interviewer_name' => $interviewerName,
                'interviewer_email' => $interviewerEmail,
                'interviewer_user_id' => $interviewerUserId,
            ]);
            
            $interview = Interview::create([
                'match_id' => $match->id,
                'company_id' => $vacancy->company_id,
                'type' => $request->type,
                'scheduled_at' => $scheduledAt,
                'duration' => $request->duration ?? 60,
                'status' => 'scheduled',
                'location' => $location,
                'company_location_id' => $companyLocationId,
                'interviewer_name' => $interviewerName,
                'interviewer_email' => $interviewerEmail,
                'interviewer_user_id' => $interviewerUserId,
                'user_id' => $interviewerUserId, // Keep for backward compatibility
                'notes' => $request->notes,
            ]);
            
            \Log::info('Interview created successfully', [
                'interview_id' => $interview->id,
                'scheduled_at' => $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i:s') : null,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to create interview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => [
                    'match_id' => $match->id,
                    'company_id' => $vacancy->company_id,
                    'type' => $request->type,
                    'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : null,
                    'location' => $location,
                    'company_location_id' => $companyLocationId,
                    'interviewer_name' => $interviewerName,
                    'interviewer_email' => $interviewerEmail,
                    'interviewer_user_id' => $interviewerUserId,
                ]
            ]);
            return redirect()->back()->withErrors(['error' => 'Er is een fout opgetreden bij het opslaan van het interview: ' . $e->getMessage()]);
        }

        // Log activity
        $this->logActivity($candidate, $vacancy, 'interview_scheduled', [
            'interview_id' => $interview->id,
            'scheduled_at' => $interview->scheduled_at,
            'type' => $interview->type,
            'location' => $interview->location,
        ], [
            'interview_id' => $interview->id,
            'match_id' => $match->id,
            'action_at' => $interview->scheduled_at ?? $interview->created_at,
        ]);

        return redirect()->route('admin.vacancies.candidate', [
            'vacancy' => $vacancy->id,
            'candidate' => $candidate->id,
            'type' => 'match',
            'match_id' => $match->id
        ])->with('success', 'Interview succesvol ingepland.');
    }

    public function updateInterview(Vacancy $vacancy, Candidate $candidate, Interview $interview, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }

        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        // Verify interview belongs to this vacancy/candidate
        if ($interview->match && $interview->match->vacancy_id !== $vacancy->id) {
            abort(403, 'Dit interview hoort niet bij deze vacature.');
        }
        if ($interview->match && $interview->match->candidate_id !== $candidate->id) {
            abort(403, 'Dit interview hoort niet bij deze kandidaat.');
        }

        // Store original values for comparison (excluding notes)
        $originalValues = [
            'scheduled_at' => $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i:s') : null,
            'type' => $interview->type,
            'duration' => $interview->duration,
            'location' => $interview->location,
            'company_location_id' => $interview->company_location_id,
            'interviewer_name' => $interview->interviewer_name,
            'interviewer_email' => $interview->interviewer_email,
            'interviewer_user_id' => $interview->interviewer_user_id,
            'user_id' => $interview->user_id, // Keep for comparison
        ];

        // Same validation as scheduleInterview
        $request->validate([
            'scheduled_at' => 'nullable|string',
            'scheduled_time' => 'required|string',
            'type' => 'required|in:phone,video,onsite,assessment,final',
            'duration' => 'required|integer|min:15|max:480',
            'location_type' => 'required|string',
            'location' => 'nullable|string|max:255',
            'interviewer_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === 'other') {
                        return;
                    }
                    if (empty($value)) {
                        $fail('Interviewer is verplicht.');
                        return;
                    }
                    if (!is_numeric($value)) {
                        $fail('De geselecteerde interviewer is ongeldig.');
                        return;
                    }
                    $userId = (int)$value;
                    if (!\App\Models\User::find($userId)) {
                        $fail('De geselecteerde interviewer is ongeldig.');
                    }
                },
            ],
            'interviewer_name_custom' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u',
                'required_if:interviewer_id,other',
            ],
            'interviewer_email' => [
                'nullable',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('interviewer_id') === 'other' && empty($value)) {
                        $fail('E-mailadres is verplicht wanneer een externe interviewer wordt ingevoerd.');
                    }
                    if (!empty($value) && !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value)) {
                        $fail('E-mailadres moet een geldig e-mailadres zijn.');
                    }
                },
            ],
            'notes' => 'nullable|string',
        ]);

        // Parse scheduled_at (same logic as scheduleInterview)
        $scheduledAt = null;
        $dateInput = $request->input('scheduled_at') ?? $request->input('scheduled_at_hidden');
        $timeInput = $request->input('scheduled_time');
        
        if (empty($dateInput)) {
            return redirect()->back()->withErrors(['scheduled_at' => 'Datum is verplicht.']);
        }
        
        try {
            $date = null;
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateInput)) {
                $date = \Carbon\Carbon::createFromFormat('d-m-Y', $dateInput);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateInput);
            } else {
                $date = \Carbon\Carbon::parse($dateInput);
            }
            
            if (!empty($timeInput)) {
                $time = trim($timeInput);
                if (preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
                    $timeParts = explode(':', $time);
                    $date->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                } else {
                    $timeDigits = preg_replace('/[^\d]/', '', $time);
                    if (strlen($timeDigits) >= 2) {
                        $hours = (int)substr($timeDigits, 0, 2);
                        $minutes = strlen($timeDigits) >= 4 ? (int)substr($timeDigits, 2, 2) : 0;
                        if ($hours <= 23 && $minutes <= 59) {
                            $date->setTime($hours, $minutes, 0);
                        } else {
                            $date->startOfDay();
                        }
                    } else {
                        $date->startOfDay();
                    }
                }
            } else {
                $date->startOfDay();
            }
            
            $scheduledAt = $date;
        } catch (\Exception $e) {
            \Log::error('Failed to parse scheduled_at in updateInterview', [
                'date_input' => $dateInput,
                'time_input' => $timeInput,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors(['scheduled_at' => 'Ongeldige datum of tijd opgegeven: ' . $e->getMessage()]);
        }

        // Determine location value and company_location_id (same logic as scheduleInterview)
        $location = null;
        $companyLocationId = null;
        
        if ($request->filled('location_type')) {
            if ($request->location_type === 'online') {
                $location = 'Online / Digitaal';
                $companyLocationId = null;
            } elseif ($request->location_type === 'other') {
                $location = $request->location;
                $companyLocationId = null;
            } else {
                $companyLocation = \App\Models\CompanyLocation::find($request->location_type);
                if ($companyLocation) {
                    $companyLocationId = $companyLocation->id;
                    $location = $companyLocation->name . ($companyLocation->address ? ' - ' . $companyLocation->address : '');
                }
            }
        }
        
        // Determine interviewer info and interviewer_user_id (same logic as scheduleInterview)
        $interviewerName = null;
        $interviewerEmail = null;
        $interviewerUserId = null;
        
        if ($request->filled('interviewer_id')) {
            if ($request->interviewer_id === 'other') {
                $interviewerName = $request->interviewer_name_custom;
                $interviewerEmail = $request->interviewer_email;
                $interviewerUserId = null;
            } else {
                $interviewer = \App\Models\User::find($request->interviewer_id);
                if ($interviewer) {
                    $interviewerUserId = $interviewer->id;
                    $interviewerName = trim(($interviewer->first_name ?? '') . ' ' . ($interviewer->last_name ?? ''));
                    $interviewerEmail = $interviewer->email;
                }
            }
        }

        // Check if interview is being reactivated
        $isReactivation = $request->has('reactivate') && $interview->status === 'cancelled';
        
        // Update interview
        $updateData = [
            'type' => $request->type,
            'scheduled_at' => $scheduledAt,
            'duration' => $request->duration ?? 60,
            'location' => $location,
            'company_location_id' => $companyLocationId,
            'interviewer_name' => $interviewerName,
            'interviewer_email' => $interviewerEmail,
            'interviewer_user_id' => $interviewerUserId,
            'user_id' => $interviewerUserId, // Keep for backward compatibility
            'notes' => $request->notes,
        ];
        
        // If reactivating, set status to scheduled
        if ($isReactivation) {
            $updateData['status'] = 'scheduled';
        }
        
        $interview->update($updateData);

        // Detect changes (excluding notes)
        $changes = [];
        $newValues = [
            'scheduled_at' => $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i:s') : null,
            'type' => $interview->type,
            'duration' => $interview->duration,
            'location' => $interview->location,
            'company_location_id' => $interview->company_location_id,
            'interviewer_name' => $interviewerName,
            'interviewer_email' => $interviewerEmail,
            'interviewer_user_id' => $interviewerUserId,
            'user_id' => $interviewerUserId, // Keep for comparison
        ];

        $typeMap = [
            'phone' => 'Telefoon',
            'video' => 'Video',
            'onsite' => 'Op locatie',
            'assessment' => 'Assessment',
            'final' => 'Eindgesprek',
        ];

        if ($originalValues['scheduled_at'] !== $newValues['scheduled_at']) {
            $oldDate = $originalValues['scheduled_at'] ? \Carbon\Carbon::parse($originalValues['scheduled_at'])->format('d-m-Y H:i') : 'Niet ingepland';
            $newDate = $newValues['scheduled_at'] ? \Carbon\Carbon::parse($newValues['scheduled_at'])->format('d-m-Y H:i') : 'Niet ingepland';
            $changes[] = "Datum en tijd: van {$oldDate} naar {$newDate}";
        }
        if ($originalValues['type'] !== $newValues['type']) {
            $oldType = $typeMap[$originalValues['type']] ?? $originalValues['type'];
            $newType = $typeMap[$newValues['type']] ?? $newValues['type'];
            $changes[] = "Type: van {$oldType} naar {$newType}";
        }
        if ($originalValues['duration'] !== $newValues['duration']) {
            $changes[] = "Duur: van {$originalValues['duration']} minuten naar {$newValues['duration']} minuten";
        }
        if ($originalValues['location'] !== $newValues['location'] || $originalValues['company_location_id'] !== $newValues['company_location_id']) {
            $oldLocation = $originalValues['location'] ?? 'Niet opgegeven';
            $newLocation = $newValues['location'] ?? 'Niet opgegeven';
            $changes[] = "Locatie: van {$oldLocation} naar {$newLocation}";
        }
        if ($originalValues['interviewer_name'] !== $newValues['interviewer_name'] || $originalValues['user_id'] !== $newValues['user_id']) {
            $oldInterviewer = $originalValues['interviewer_name'] ?? 'Niet opgegeven';
            $newInterviewer = $newValues['interviewer_name'] ?? 'Niet opgegeven';
            $changes[] = "Interviewer: van {$oldInterviewer} naar {$newInterviewer}";
        }

        // Send email if there are changes (excluding notes) or if reactivating
        if (!empty($changes) || $isReactivation) {
            try {
                $emailService = app(\App\Services\EmailTemplateService::class);
                
                if ($isReactivation) {
                    // Send reactivation email
                    $emailService->sendInterviewReactivationEmail($candidate, $vacancy, $interview);
                } else {
                    // Send update email
                    $emailService->sendInterviewUpdateEmail($candidate, $vacancy, $changes);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send interview email', [
                    'error' => $e->getMessage(),
                    'candidate_id' => $candidate->id,
                    'vacancy_id' => $vacancy->id,
                    'is_reactivation' => $isReactivation,
                ]);
                // Don't fail the update if email fails
            }
        }

        // Log activity
        $activityType = $isReactivation ? 'interview_reactivated' : 'interview_updated';
        $this->logActivity($candidate, $vacancy, $activityType, [
            'interview_id' => $interview->id,
            'changes' => $changes,
            'type' => $interview->type,
            'location' => $interview->location,
        ], [
            'interview_id' => $interview->id,
            'match_id' => $interview->match_id,
            'action_at' => now(),
        ]);

        $successMessage = $isReactivation 
            ? 'Interview succesvol gereactiveerd. De kandidaat en interviewer hebben een e-mail ontvangen.'
            : 'Interview succesvol bijgewerkt.';

        return redirect()->route('admin.vacancies.candidate', [
            'vacancy' => $vacancy->id,
            'candidate' => $candidate->id,
            'type' => 'match',
            'match_id' => $interview->match_id
        ])->with('success', $successMessage);
    }

    public function cancelInterview(Vacancy $vacancy, Candidate $candidate, Interview $interview, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-vacancies')) {
            abort(403, 'Je hebt geen rechten om interviews te annuleren.');
        }

        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        // Check if interview belongs to this candidate and vacancy
        $match = $interview->match;
        if (!$match || $match->candidate_id !== $candidate->id || $match->vacancy_id !== $vacancy->id) {
            abort(404, 'Interview niet gevonden.');
        }

        // Update status to cancelled
        $interview->update([
            'status' => 'cancelled'
        ]);

        // Log activity
        $this->logActivity($candidate, $vacancy, 'interview_cancelled', [
            'interview_id' => $interview->id,
            'type' => $interview->type,
            'location' => $interview->location,
        ], [
            'interview_id' => $interview->id,
            'match_id' => $match->id,
            'action_at' => now(),
        ]);

        return redirect()->route('admin.vacancies.candidate', [
            'vacancy' => $vacancy->id,
            'candidate' => $candidate->id,
            'type' => 'match',
            'match_id' => $match->id
        ])->with('success', 'Interview succesvol geannuleerd.');
    }

    public function rejectCandidate(Vacancy $vacancy, Candidate $candidate, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-vacancies')) {
            abort(403, 'Je hebt geen rechten om kandidaten af te wijzen.');
        }

        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
            'match_id' => 'nullable|exists:matches,id',
            'application_id' => 'nullable|exists:applications,id',
        ]);

        // Update match or application status
        if ($request->filled('match_id')) {
            $match = JobMatch::where('id', $request->match_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();
            $match->update(['status' => 'rejected']);
        }

        if ($request->filled('application_id')) {
            $application = Application::where('id', $request->application_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();
            $application->update(['status' => 'rejected']);
        }

        // Log activity
        $this->logActivity($candidate, $vacancy, 'rejected', [
            'reason' => $request->reason,
        ], [
            'match_id' => $match ? $match->id : null,
            'application_id' => $application ? $application->id : null,
            'action_at' => now(),
        ]);

        // Send rejection email
        try {
            $emailService = app(EmailTemplateService::class);
            $emailService->sendRejectionEmail($candidate, $vacancy, $request->reason);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send rejection email: ' . $e->getMessage());
        }

        $redirectParams = [
            'vacancy' => $vacancy->id,
            'candidate' => $candidate->id,
        ];

        if ($request->filled('match_id')) {
            $redirectParams['type'] = 'match';
            $redirectParams['match_id'] = $request->match_id;
        } elseif ($request->filled('application_id')) {
            $redirectParams['type'] = 'application';
            $redirectParams['application_id'] = $request->application_id;
        }

        return redirect()->route('admin.vacancies.candidate', $redirectParams)
            ->with('success', 'Kandidaat afgewezen en e-mail verzonden.');
    }

    public function acceptCandidate(Vacancy $vacancy, Candidate $candidate, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-vacancies')) {
            abort(403, 'Je hebt geen rechten om kandidaten te accepteren.');
        }

        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        // Update match or application status
        if ($request->filled('match_id')) {
            $match = JobMatch::where('id', $request->match_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();
            $match->update(['status' => 'accepted']);
        }

        if ($request->filled('application_id')) {
            $application = Application::where('id', $request->application_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();
            $application->update(['status' => 'accepted']);
        }

        // Log activity
        $this->logActivity($candidate, $vacancy, 'accepted', [], [
            'match_id' => $match->id,
            'action_at' => now(),
        ]);

        $redirectParams = [
            'vacancy' => $vacancy->id,
            'candidate' => $candidate->id,
        ];

        if ($request->filled('match_id')) {
            $redirectParams['type'] = 'match';
            $redirectParams['match_id'] = $request->match_id;
        } elseif ($request->filled('application_id')) {
            $redirectParams['type'] = 'application';
            $redirectParams['application_id'] = $request->application_id;
        }

        return redirect()->route('admin.vacancies.candidate', $redirectParams)
            ->with('success', 'Kandidaat geaccepteerd.');
    }

    /**
     * Get candidate timeline/activity history
     */
    private function getCandidateTimeline(Candidate $candidate, Vacancy $vacancy, $match = null, $application = null)
    {
        // Get all activities from database for this candidate and vacancy
        // Only read from candidate_activities table - no fallback to other sources
        $activities = \App\Models\CandidateActivity::where('candidate_id', $candidate->id)
            ->where('vacancy_id', $vacancy->id)
            ->orderBy('action_at', 'desc')
            ->get();
        
        $timeline = collect();

        foreach ($activities as $activity) {
            $timeline->push([
                'type' => $activity->action,
                'title' => $activity->title,
                'description' => $activity->description,
                'date' => $activity->action_at,
                'icon' => $activity->icon,
                'color' => $activity->color,
            ]);
        }

        $stageStatusColors = [
            'PENDING' => 'secondary',
            'SCHEDULED' => 'info',
            'IN_PROGRESS' => 'warning',
            'COMPLETED' => 'success',
            'SKIPPED' => 'muted',
            'CANCELED' => 'danger',
        ];
        $stageStatusLabels = [
            'PENDING' => 'In afwachting',
            'SCHEDULED' => 'Ingepland',
            'IN_PROGRESS' => 'Bezig',
            'COMPLETED' => 'Voltooid',
            'SKIPPED' => 'Overgeslagen',
            'CANCELED' => 'Geannuleerd',
        ];
        $stageStatusIcons = [
            'PENDING' => 'ki-clock',
            'SCHEDULED' => 'ki-calendar',
            'IN_PROGRESS' => 'ki-time',
            'COMPLETED' => 'ki-check-circle',
            'SKIPPED' => 'ki-arrow-right',
            'CANCELED' => 'ki-cross-circle',
        ];

        $stagesQuery = StageInstance::query();
        if ($match) {
            $stagesQuery->where('match_id', $match->id);
        } elseif ($application) {
            $stagesQuery->where('application_id', $application->id);
        }

        $stageInstances = $stagesQuery->orderBy('sequence')->get();
        foreach ($stageInstances as $stage) {
            $statusKey = $stage->status;
            $statusLabel = $stageStatusLabels[$statusKey] ?? $statusKey;
            $descriptionParts = ['Status: ' . $statusLabel];
            if ($stage->scheduled_at) {
                $descriptionParts[] = 'Gepland: ' . $stage->scheduled_at->format('d-m-Y H:i');
            }
            if ($stage->outcome) {
                $outcomeLabels = [
                    'PASS' => 'Geslaagd',
                    'FAIL' => 'Niet geslaagd',
                    'ON_HOLD' => 'On hold',
                    'ACCEPTED' => 'Geaccepteerd',
                    'DECLINED' => 'Afgewezen',
                ];
                $descriptionParts[] = 'Uitkomst: ' . ($outcomeLabels[$stage->outcome] ?? $stage->outcome);
            }
            if ($statusKey === 'PENDING') {
                continue;
            }

            $artifacts = $stage->artifacts ?? [];
            if (!empty($artifacts['location'])) {
                $descriptionParts[] = 'Locatie: ' . $artifacts['location'];
            } elseif (!empty($artifacts['location_custom'])) {
                $descriptionParts[] = 'Locatie: ' . $artifacts['location_custom'];
            }
            if (!empty($artifacts['interviewer_name'])) {
                $descriptionParts[] = 'Interviewer: ' . $artifacts['interviewer_name'];
            }
            $timeline->push([
                'type' => 'stage_' . $stage->id,
                'title' => $stage->label,
                'description' => implode(' - ', $descriptionParts),
                'date' => $stage->updated_at ?? $stage->created_at ?? now(),
                'icon' => $stageStatusIcons[$statusKey] ?? 'ki-list-check',
                'color' => $stageStatusColors[$statusKey] ?? 'secondary',
            ]);
        }

        return $timeline->sortByDesc('date')->values()->all();
    }

    /**
     * Get timeline data for AJAX refresh
     */
    public function getTimeline(Vacancy $vacancy, Candidate $candidate, Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-vacancies')) {
            abort(403, 'Je hebt geen rechten om deze informatie te bekijken.');
        }

        if (!$this->canAccessResource($vacancy)) {
            abort(403, 'Je hebt geen toegang tot deze vacature.');
        }

        // Get match or application based on request
        $match = null;
        $application = null;

        if ($request->filled('match_id')) {
            $match = JobMatch::where('id', $request->match_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->with(['interviews'])
                ->first();
        } elseif ($request->filled('application_id')) {
            $application = Application::where('id', $request->application_id)
                ->where('vacancy_id', $vacancy->id)
                ->where('candidate_id', $candidate->id)
                ->first();
        }

        // Get timeline
        $timeline = $this->getCandidateTimeline($candidate, $vacancy, $match, $application);

        // Return timeline as JSON
        return response()->json([
            'timeline' => array_map(function($item) {
                return [
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'date' => $item['date']->format('Y-m-d H:i:s'),
                    'date_formatted' => $item['date']->format('d-m-Y H:i'),
                    'date_human' => $item['date']->diffForHumans(),
                    'icon' => $item['icon'],
                    'color' => $item['color'],
                ];
            }, $timeline)
        ]);
    }

    /**
     * Log activity for candidate timeline
     */
    private function logActivity(Candidate $candidate, Vacancy $vacancy, $action, $data = [], $options = [])
    {
        // Define activity metadata
        $activityMap = [
            'application_created' => [
                'title' => 'Sollicitatie ontvangen',
                'description' => 'Kandidaat heeft gereageerd op de vacature',
                'icon' => 'ki-file-added',
                'color' => 'info',
            ],
            'match_created' => [
                'title' => 'Match gevonden',
                'description' => 'AI heeft een match gevonden met ' . ($data['match_score'] ?? 0) . '%',
                'icon' => 'ki-chart-simple',
                'color' => 'success',
            ],
            'interview_scheduled' => [
                'title' => 'Interview ingepland',
                'description' => 'Interview type: ' . ($data['type'] ?? '') . (isset($data['location']) && $data['location'] ? ' - ' . $data['location'] : ''),
                'icon' => 'ki-calendar',
                'color' => 'primary',
            ],
            'interview_updated' => [
                'title' => 'Interview bijgewerkt',
                'description' => isset($data['changes']) && !empty($data['changes']) ? implode(', ', $data['changes']) : 'Interview details zijn aangepast',
                'icon' => 'ki-notepad-edit',
                'color' => 'primary',
            ],
            'interview_cancelled' => [
                'title' => 'Interview geannuleerd',
                'description' => 'Interview type: ' . ($data['type'] ?? '') . (isset($data['location']) && $data['location'] ? ' - ' . $data['location'] : ''),
                'icon' => 'ki-cross-circle',
                'color' => 'danger',
            ],
            'interview_reactivated' => [
                'title' => 'Interview ge-heractiveerd',
                'description' => 'Interview type: ' . ($data['type'] ?? '') . (isset($data['location']) && $data['location'] ? ' - ' . $data['location'] : ''),
                'icon' => 'ki-check-circle',
                'color' => 'success',
            ],
            'rejected' => [
                'title' => 'Afgewezen',
                'description' => 'Kandidaat is afgewezen voor deze vacature' . (isset($data['reason']) ? ' - ' . $data['reason'] : ''),
                'icon' => 'ki-cross-circle',
                'color' => 'danger',
            ],
            'accepted' => [
                'title' => 'Geaccepteerd',
                'description' => 'Kandidaat is geaccepteerd voor deze vacature',
                'icon' => 'ki-check-circle',
                'color' => 'success',
            ],
        ];
        
        $activityInfo = $activityMap[$action] ?? [
            'title' => ucfirst(str_replace('_', ' ', $action)),
            'description' => null,
            'icon' => 'ki-information',
            'color' => 'info',
        ];
        
        // Override with options if provided
        if (isset($options['title'])) {
            $activityInfo['title'] = $options['title'];
        }
        if (isset($options['description'])) {
            $activityInfo['description'] = $options['description'];
        }
        if (isset($options['icon'])) {
            $activityInfo['icon'] = $options['icon'];
        }
        if (isset($options['color'])) {
            $activityInfo['color'] = $options['color'];
        }
        
        // Determine action_at timestamp
        $actionAt = $options['action_at'] ?? now();
        
        // Create activity record
        \App\Models\CandidateActivity::create([
            'candidate_id' => $candidate->id,
            'vacancy_id' => $vacancy->id,
            'action' => $action,
            'title' => $activityInfo['title'],
            'description' => $activityInfo['description'],
            'icon' => $activityInfo['icon'],
            'color' => $activityInfo['color'],
            'match_id' => $options['match_id'] ?? $data['match_id'] ?? null,
            'application_id' => $options['application_id'] ?? $data['application_id'] ?? null,
            'interview_id' => $options['interview_id'] ?? $data['interview_id'] ?? null,
            'metadata' => $data,
            'user_id' => auth()->id(),
            'action_at' => $actionAt,
        ]);
        
        // Also log to Laravel log for debugging
        \Log::info('Candidate activity logged', [
            'candidate_id' => $candidate->id,
            'vacancy_id' => $vacancy->id,
            'action' => $action,
        ]);
    }
}
