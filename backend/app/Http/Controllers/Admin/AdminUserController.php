<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\JobTitle;
use App\Models\User;
use App\Support\ModuleSchemaAvailability;
use App\Services\EnvService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    use TenantFilter;

    public function index(Request $request)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('view-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bekijken.');
        }

        $query = User::with(['company', 'roles']);
        $this->applyTenantFilter($query);

        // Exclude de ingelogde gebruiker uit het overzicht
        $query->where('id', '!=', auth()->id());

        // Filter super-admins: alleen super-admins kunnen andere super-admins zien
        if (! auth()->user()->hasRole('super-admin')) {
            $query->whereNot(fn ($q) => $this->applyWebRoleNameFilter($q, 'super-admin'));
        }

        // Apply filters
        if ($request->filled('status')) {
            if (\Schema::hasColumn('users', 'is_active')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            } else {
                // Fallback to email_verified_at if is_active doesn't exist
                if ($request->status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->status === 'inactive') {
                    $query->whereNull('email_verified_at');
                }
            }
        }

        if ($request->filled('role')) {
            $this->applyWebRoleNameFilter($query, (string) $request->role);
        }

        if ($request->filled('company')) {
            $query->where('company_id', $request->company);
        }

        // Apply sorting
        $sortBy = $request->get('sort');
        $sortDirection = $request->get('direction');

        // Always sort by ID first to maintain position, then by requested sort
        if ($sortBy && in_array($sortBy, ['first_name', 'last_name', 'email', 'created_at', 'email_verified_at'])) {
            // Set default direction based on sort field
            if (! $sortDirection || ! in_array($sortDirection, ['asc', 'desc'])) {
                // For date fields, default to desc (newest first)
                if (in_array($sortBy, ['created_at', 'email_verified_at'])) {
                    $sortDirection = 'desc';
                } else {
                    // For text fields, default to asc (alphabetical)
                    $sortDirection = 'asc';
                }
            }
            // Sort by requested field, then by ID to maintain stable order
            $query->orderBy($sortBy, $sortDirection)->orderBy('id', 'asc');
        } else {
            // Default sort: order by ID to maintain position
            $query->orderBy('id', 'asc');
        }

        // Load all users for client-side pagination (like demo1)
        // The KTDataTable library will handle pagination client-side
        $users = $query->get();

        // Calculate statistics
        $statsQuery = User::query();
        $this->applyTenantFilter($statsQuery);

        $tenantId = $this->getTenantId();

        $stats = [
            'total_companies' => $tenantId ? \App\Models\Company::where('id', $tenantId)->count() : \App\Models\Company::count(),
            'active_companies' => $tenantId ? \App\Models\Company::where('id', $tenantId)->where('is_active', true)->count() : \App\Models\Company::where('is_active', true)->count(),
            'total_users' => (clone $statsQuery)->count(),
            'active_users' => \Schema::hasColumn('users', 'is_active')
                ? (clone $statsQuery)->where('is_active', true)->count()
                : (clone $statsQuery)->whereNotNull('email_verified_at')->count(),
            'total_vacancies' => ModuleSchemaAvailability::vacanciesTableExists()
                ? ($tenantId ? \App\Models\Vacancy::where('company_id', $tenantId)->count() : \App\Models\Vacancy::count())
                : 0,
            'intermediaries' => $tenantId ? \App\Models\Company::where('id', $tenantId)->where('is_intermediary', true)->count() : \App\Models\Company::where('is_intermediary', true)->count(),
        ];

        // Rollen in filter = rollen die daadwerkelijk in dit overzicht voorkomen (zelfde bron als webRoleNames in de tabel)
        $roles = $this->roleNamesForUserIndexFilter();

        // Get companies for filter
        $companies = Company::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'stats', 'roles', 'companies'));
    }

    public function create(\Illuminate\Http\Request $request)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('create-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers aan te maken.');
        }

        $user = auth()->user();

        // Filter bedrijven op basis van gebruiker rechten
        if ($user->hasRole('super-admin')) {
            $companies = Company::all();
        } else {
            $companies = Company::where('id', $user->company_id)->get();
        }

        // Filter rollen op basis van gebruiker rechten (alleen web-guard; anders dubbele api/web rijen)
        if ($user->hasRole('super-admin')) {
            $roles = Role::where('guard_name', 'web')->orderBy('name')->get();
        } else {
            $roles = Role::where('guard_name', 'web')->where('name', '!=', 'super-admin')->orderBy('name')->get();
        }

        $defaultRoleForForm = null;
        if ($user->hasRole('super-admin')) {
            $tenantId = session('selected_tenant');
            if ($tenantId && ! User::where('company_id', $tenantId)->exists()) {
                $defaultRoleForForm = 'company-admin';
            }
        }

        // Terug-link: vanuit company-wizard (hidden wizard_back_url + old() na validatiefout)
        $userCreateBackFallback = route('admin.users.index');
        $wizardContextCompanyId = null;
        $wizardContextStep = null;
        if ($request->boolean('from_wizard') && $request->filled('wizard_company')) {
            $wizardCompany = Company::find((int) $request->query('wizard_company'));
            $wizardStep = max(1, min(7, (int) ($request->query('wizard_step') ?: 5)));
            if ($wizardCompany && $this->canAccessResource($wizardCompany)) {
                $userCreateBackFallback = route('admin.companies.wizard.step', [$wizardCompany, $wizardStep]);
                $wizardContextCompanyId = (int) $wizardCompany->id;
                $wizardContextStep = $wizardStep;
            }
        }
        $userCreateBackUrl = old('wizard_back_url') ?: $userCreateBackFallback;

        return view('admin.users.create', compact('companies', 'roles', 'defaultRoleForForm', 'userCreateBackUrl', 'wizardContextCompanyId', 'wizardContextStep'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->hydrateUserWizardParamsFromSession($request);

        $userData = [
            'first_name' => $request->validated()['first_name'],
            'last_name' => $request->validated()['last_name'],
            'email' => $request->validated()['email'],
            'password' => Hash::make($request->validated()['password']),
            'phone' => $request->validated()['phone'] ?? null,
            'date_of_birth' => $request->validated()['date_of_birth'] ?? null,
            'function' => $request->validated()['function'] ?? null,
        ];

        // Super-admin: wizard_company (hidden/session) wint altijd van het dropdown — onboarding moet vast aan stap-1-bedrijf hangen.
        if (auth()->user()->hasRole('super-admin')) {
            $cid = $request->validated()['company_id'] ?? null;
            if ($request->filled('wizard_company') && is_numeric($request->input('wizard_company'))) {
                $wizId = (int) $request->input('wizard_company');
                $wizCompany = Company::find($wizId);
                if ($wizCompany && $this->canAccessResource($wizCompany)) {
                    $cid = $wizId;
                }
            }
            $userData['company_id'] = $cid !== null && $cid !== '' ? (int) $cid : null;
        } else {
            // Voor niet-super-admins: gebruik altijd het bedrijf van de ingelogde gebruiker
            $userData['company_id'] = auth()->user()->company_id;
        }

        $companyId = $userData['company_id'] ?? null;
        $willBeFirstUserForCompany = $companyId !== null
            && User::where('company_id', $companyId)->count() === 0;
        $roleNames = $willBeFirstUserForCompany
            ? ['company-admin']
            : $request->validated()['roles'];

        // Save or update job title if function is provided
        if (! empty($userData['function'])) {
            $jobTitle = JobTitle::firstOrCreate(['name' => $userData['function']]);
            $jobTitle->increment('usage_count');
            $userData['job_title_id'] = $jobTitle->id;
        }

        $user = User::create($userData);

        app(UserRoleAssignmentService::class)->syncWebRoles($user, $roleNames);

        $wizardBack = $request->validated()['wizard_back_url'] ?? null;
        if (is_string($wizardBack) && $wizardBack !== '') {
            return redirect()->to($wizardBack)->with('success', 'Gebruiker succesvol aangemaakt.');
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'Gebruiker succesvol aangemaakt.');
    }

    /**
     * Zorg dat wizard_company/from_wizard op POST staan als de browser ze niet meestuurt (sessie tenant-onboarding).
     */
    private function hydrateUserWizardParamsFromSession(Request $request): void
    {
        if ($request->filled('wizard_company') && is_numeric($request->input('wizard_company'))) {
            return;
        }
        $sid = session(AdminCompanyWizardController::SESSION_ACTIVE_ONBOARDING_COMPANY_ID);
        if ($sid === null || $sid === '' || ! is_numeric($sid) || ! Company::whereKey((int) $sid)->exists()) {
            return;
        }
        $request->merge([
            'from_wizard' => '1',
            'wizard_company' => (string) (int) $sid,
            'wizard_step' => (string) max(1, min(7, (int) $request->input('wizard_step', 5))),
        ]);
    }

    public function show(User $user)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('view-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bekijken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        $currentUser = auth()->user();

        // Filter bedrijven op basis van gebruiker rechten
        if ($currentUser->hasRole('super-admin')) {
            $companies = Company::all();
        } else {
            $companies = Company::where('id', $currentUser->company_id)->get();
        }

        // Filter rollen op basis van gebruiker rechten (alleen web-guard; anders dubbele api/web rijen)
        if ($currentUser->hasRole('super-admin')) {
            $roles = Role::where('guard_name', 'web')->orderBy('name')->get();
        } else {
            $roles = Role::where('guard_name', 'web')->where('name', '!=', 'super-admin')->orderBy('name')->get();
        }

        return view('admin.users.edit', compact('user', 'companies', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        $validated = $request->validated();

        $userData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'function' => $validated['function'] ?? null,
        ];

        if (auth()->user()->hasRole('super-admin')) {
            $cid = $validated['company_id'] ?? null;
            $userData['company_id'] = $cid !== null && $cid !== '' ? (int) $cid : null;
        } else {
            // Voor niet-super-admins: gebruik altijd het bedrijf van de ingelogde gebruiker
            $userData['company_id'] = auth()->user()->company_id;
        }

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        // Save or update job title if function is provided
        if (! empty($userData['function'])) {
            $jobTitle = JobTitle::firstOrCreate(['name' => $userData['function']]);
            $jobTitle->increment('usage_count');
            $userData['job_title_id'] = $jobTitle->id;
        } else {
            $userData['job_title_id'] = null;
        }

        $user->update($userData);
        $user->refresh();
        app(UserRoleAssignmentService::class)->syncWebRoles($user, $validated['roles']);

        return redirect()->route('admin.users.show', $user)->with('success', 'Gebruiker succesvol bijgewerkt.');
    }

    public function destroy(User $user)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('delete-users')) {
            abort(403, 'Je hebt geen rechten om gebruikers te verwijderen.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Je kunt jezelf niet verwijderen.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Gebruiker succesvol verwijderd.');
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => ['string', 'distinct', \Illuminate\Validation\Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        $roles = $request->input('roles', []);
        if (! auth()->user()->hasRole('super-admin') && in_array('super-admin', $roles, true)) {
            return back()->withErrors(['roles' => 'Je mag geen super-admin rol toewijzen.']);
        }

        app(UserRoleAssignmentService::class)->syncWebRoles($user, $roles);

        return back()->with('success', 'Rollen succesvol toegewezen.');
    }

    public function photo(User $user)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('view-users')) {
            abort(403, 'Je hebt geen rechten om gebruikersfoto\'s te bekijken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        if (! $user->photo_blob) {
            abort(404);
        }

        $content = base64_decode($user->photo_blob);
        $mimeType = $user->photo_mime_type ?: 'image/jpeg';

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ]);
    }

    public function toggleStatus(User $user)
    {
        // Check if AJAX request
        $isAjax = request()->expectsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest';

        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-users')) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Je hebt geen rechten om gebruikers te bewerken.',
                ], 403);
            }
            abort(403, 'Je hebt geen rechten om gebruikers te bewerken.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Je hebt geen toegang tot deze gebruiker.',
                ], 403);
            }
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        // Prevent users from deactivating themselves
        if ($user->id === auth()->id()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Je kunt jezelf niet deactiveren.',
                ], 403);
            }

            return back()->with('error', 'Je kunt jezelf niet deactiveren.');
        }

        // Toggle is_active to activate/deactivate user (email_verified_at remains unchanged)
        try {
            // Check if is_active column exists - use direct DB query to avoid schema cache issues
            $connection = \DB::connection();
            $driverName = $connection->getDriverName();

            // Check column existence with direct query
            $columnExists = false;
            try {
                if ($driverName === 'pgsql') {
                    $result = \DB::selectOne("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'is_active'");
                    $columnExists = $result !== null;
                } elseif ($driverName === 'mysql') {
                    $result = \DB::selectOne("SHOW COLUMNS FROM users LIKE 'is_active'");
                    $columnExists = $result !== null;
                } elseif ($driverName === 'sqlite') {
                    // For SQLite, use PRAGMA table_info
                    $columns = \DB::select('PRAGMA table_info(users)');
                    foreach ($columns as $col) {
                        if (isset($col->name) && $col->name === 'is_active') {
                            $columnExists = true;
                            break;
                        }
                    }
                } else {
                    // For other databases, use Schema facade
                    $columnExists = \Schema::hasColumn('users', 'is_active');
                }
            } catch (\Exception $e) {
                \Log::warning('Error checking is_active column: '.$e->getMessage());
                // Fallback: try to update and see if it works
                try {
                    $user->refresh();
                    $testValue = $user->is_active ?? null;
                    $columnExists = true; // If we can access it, it exists
                } catch (\Exception $e2) {
                    $columnExists = false;
                }
            }

            if (! $columnExists) {
                // Try to add the column automatically
                try {
                    if ($driverName === 'pgsql') {
                        \DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true');
                    } elseif ($driverName === 'mysql') {
                        \DB::statement('ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true');
                    } elseif ($driverName === 'sqlite') {
                        // SQLite doesn't support IF NOT EXISTS in ALTER TABLE, but we check first
                        \DB::statement('ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1');
                    } else {
                        \DB::statement('ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true');
                    }
                    \DB::statement('UPDATE users SET is_active = true WHERE is_active IS NULL');
                    $columnExists = true;
                } catch (\Exception $e) {
                    \Log::error('Failed to add is_active column: '.$e->getMessage());
                    if ($isAjax) {
                        return response()->json([
                            'success' => false,
                            'message' => 'De is_active kolom bestaat niet. Voer handmatig uit: ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true;',
                        ], 500);
                    }

                    return back()->with('error', 'De is_active kolom bestaat niet.');
                }
            }

            // Only update is_active, never touch email_verified_at
            $user->refresh(); // Refresh to get latest state

            // Try to update is_active - if column doesn't exist, this will throw an exception
            try {
                $user->update(['is_active' => ! $user->is_active]);
            } catch (\Exception $e) {
                // If update fails, try to add the column and retry
                \Log::warning('is_active update failed, trying to add column: '.$e->getMessage());
                try {
                    if ($driverName === 'sqlite') {
                        \DB::statement('ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1');
                    } else {
                        \DB::statement('ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true');
                    }
                    \DB::statement('UPDATE users SET is_active = true WHERE is_active IS NULL');
                    $user->refresh();
                    $user->update(['is_active' => ! $user->is_active]);
                } catch (\Exception $e2) {
                    \Log::error('Failed to add is_active column after update failure: '.$e2->getMessage());
                    throw $e2;
                }
            }

            $user->refresh(); // Refresh after update to get new state
            $status = $user->is_active ? 'geactiveerd' : 'gedeactiveerd';
            $isActive = $user->is_active;

            // Always return JSON for AJAX requests
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => "Gebruiker '{$user->first_name} {$user->last_name}' is succesvol {$status}.",
                    'is_active' => $isActive,
                ], 200);
            }
        } catch (\Exception $e) {
            \Log::error('Toggle user status error: '.$e->getMessage());
            \Log::error('Toggle user status error stack: '.$e->getTraceAsString());

            // Always return JSON for AJAX requests
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Er is een fout opgetreden: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Er is een fout opgetreden bij het wijzigen van de status.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Gebruiker '{$user->first_name} {$user->last_name}' is succesvol {$status}.");
    }

    /**
     * Send activation link to user
     */
    public function sendActivationLink(User $user)
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-users')) {
            abort(403, 'Je hebt geen rechten om activatielinks te versturen.');
        }

        // Check if user can access this resource
        if (! $this->canAccessResource($user)) {
            abort(403, 'Je hebt geen toegang tot deze gebruiker.');
        }

        // Check if email is already verified
        if ($user->email_verified_at) {
            return back()->with('error', 'Deze gebruiker is al geverifieerd.');
        }

        try {
            // Apply mail settings (same as ContactController)
            $envService = app(EnvService::class);
            $this->applyMailSettings($envService);

            // Generate a signed verification URL that expires in 7 days
            $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'verify-email',
                now()->addDays(7),
                ['user' => $user->id, 'hash' => sha1($user->email)]
            );

            // Get mail settings
            $fromAddress = $envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
            $fromName = $envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));
            $smtpUsername = $envService->get('MAIL_USERNAME', '');

            // Send email using Laravel's Mail facade
            Mail::send('emails.verification', [
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ], function ($message) use ($user, $fromAddress, $fromName, $smtpUsername) {
                $message->to($user->email, $user->first_name.' '.$user->last_name)
                    ->subject('Verifieer je e-mailadres - Nexa Skillmatching')
                    ->from($fromAddress, $fromName);

                // Add Sender header if SMTP username is available
                // This helps with mail servers that check authorization
                if (! empty($smtpUsername)) {
                    try {
                        $symfonyMessage = $message->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $smtpUsername);
                    } catch (\Exception $e) {
                        \Log::warning('Could not set Sender header', [
                            'error' => $e->getMessage(),
                            'smtp_username' => $smtpUsername,
                        ]);
                    }
                }
            });

            return back()->with('success', 'Activatielink is succesvol verzonden naar '.$user->email.'.');
        } catch (\Exception $e) {
            \Log::error('Error sending activation link: '.$e->getMessage());

            return back()->with('error', 'Er is een fout opgetreden bij het versturen van de activatielink: '.$e->getMessage());
        }
    }

    /**
     * Apply mail settings dynamically (same as ContactController)
     */
    protected function applyMailSettings(EnvService $envService)
    {
        $mailer = $envService->get('MAIL_MAILER', 'log');
        $host = $envService->get('MAIL_HOST', '');
        $port = $envService->get('MAIL_PORT', '587');
        $username = $envService->get('MAIL_USERNAME', '');
        $password = $envService->get('MAIL_PASSWORD', '');
        $encryption = $envService->get('MAIL_ENCRYPTION', 'tls');
        $fromAddress = $envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
        $fromName = $envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption === 'null' ? null : $encryption);

            if (! empty($username) && ! empty($password)) {
                Config::set('mail.mailers.smtp.auth_mode', null);
            }
        }

        app()->forgetInstance('mail.manager');
    }

    /**
     * Verify user email
     */
    public function verifyEmail(Request $request, User $user)
    {
        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return view('auth.email-verification-failed', [
                'message' => 'Deze link is ongeldig of verlopen. Vraag een nieuwe activatielink aan via de beheerder.',
            ]);
        }

        // Verify the hash matches
        if (sha1($user->email) !== $request->hash) {
            return view('auth.email-verification-failed', [
                'message' => 'Deze link is ongeldig. Vraag een nieuwe activatielink aan via de beheerder.',
            ]);
        }

        // Mark email as verified
        $wasAlreadyVerified = (bool) $user->email_verified_at;
        if (! $wasAlreadyVerified) {
            $user->email_verified_at = now();
            $user->save();
        }

        // Show success page
        return view('auth.email-verified', [
            'user' => $user,
            'wasAlreadyVerified' => $wasAlreadyVerified,
        ]);
    }

    /**
     * Get job titles for autocomplete
     */
    public function getJobTitles(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                // Save new job title
                $name = $request->get('name');
                if ($name) {
                    $jobTitle = JobTitle::firstOrCreate(['name' => $name]);
                    $jobTitle->increment('usage_count');

                    return response()->json(['success' => true, 'id' => $jobTitle->id]);
                }

                return response()->json(['success' => false], 400);
            }

            // GET request - return suggestions
            $query = $request->get('q', '');

            $jobTitlesQuery = JobTitle::query();

            // If query is provided, filter by it (case-insensitive)
            if (! empty($query)) {
                $jobTitlesQuery->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($query).'%']);
            }

            // Return all matching results (or all if no query), ordered by usage count then name
            // Limit to top 20 most relevant results for better performance
            $jobTitles = $jobTitlesQuery
                ->orderBy('usage_count', 'desc')
                ->orderBy('name', 'asc')
                ->limit(20)
                ->pluck('name')
                ->toArray();

            return response()->json($jobTitles);
        } catch (\Exception $e) {
            \Log::error('Error in getJobTitles: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Basisquery voor gebruikersoverzicht (tenant, zonder huidige gebruiker, zonder super-admin voor niet-super-admins).
     */
    private function baseUsersIndexQuery(): Builder
    {
        $query = User::query();
        $this->applyTenantFilter($query);
        $query->where('id', '!=', auth()->id());

        if (! auth()->user()->hasRole('super-admin')) {
            $query->whereNot(fn ($q) => $this->applyWebRoleNameFilter($q, 'super-admin'));
        }

        return $query;
    }

    /**
     * Filter op web-rolnaam via model_has_roles (team-onafhankelijk), gelijk aan {@see User::webRoleNames()}.
     */
    private function applyWebRoleNameFilter(Builder $query, string $roleName): Builder
    {
        $pivot = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $morphTypes = $this->userRoleMorphTypes();

        return $query->whereExists(function ($sub) use ($roleName, $pivot, $rolesTable, $rolePivotKey, $morphKey, $morphTypes) {
            $sub->select(DB::raw('1'))
                ->from($pivot)
                ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivot}.{$rolePivotKey}")
                ->whereColumn("{$pivot}.{$morphKey}", 'users.id')
                ->whereIn("{$pivot}.model_type", $morphTypes)
                ->where("{$rolesTable}.guard_name", 'web')
                ->where("{$rolesTable}.name", $roleName);
        });
    }

    /**
     * Unieke web-rolnamen van gebruikers in het huidige overzicht (voor filterdropdown).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function roleNamesForUserIndexFilter(): \Illuminate\Support\Collection
    {
        $pivot = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $morphTypes = $this->userRoleMorphTypes();

        $query = DB::table($pivot)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivot}.{$rolePivotKey}")
            ->whereIn("{$pivot}.model_type", $morphTypes)
            ->where("{$rolesTable}.guard_name", 'web')
            ->whereIn("{$pivot}.{$morphKey}", $this->baseUsersIndexQuery()->select('users.id'));

        if (! auth()->user()->hasRole('super-admin')) {
            $query->where("{$rolesTable}.name", '!=', 'super-admin');
        }

        return $query
            ->distinct()
            ->orderBy("{$rolesTable}.name")
            ->pluck("{$rolesTable}.name")
            ->map(fn ($name) => (string) $name)
            ->values();
    }

    /**
     * @return list<string>
     */
    private function userRoleMorphTypes(): array
    {
        return array_values(array_unique(array_filter([
            (new User)->getMorphClass(),
            User::class,
            'App\\Models\\User',
        ])));
    }
}
