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
use Illuminate\Http\Request;

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
        
        $vacancy->load(['company', 'branch', 'contactUser']);
        
        return view('admin.vacancies.show', compact('vacancy'));
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
}
