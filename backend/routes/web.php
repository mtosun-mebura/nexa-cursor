<?php

use App\Support\AdminReturnUrl;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminCandidateController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminCompanyDomainController;
use App\Http\Controllers\Admin\AdminCompanyWizardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminFormFieldController;
use App\Http\Controllers\Admin\AdminHandleidingController;
use App\Http\Controllers\Admin\AdminInvoiceController;
// AdminVacancyController moved to Skillmatching module
// AdminMatchController and AdminInterviewController moved to Skillmatching module
use App\Http\Controllers\Admin\AdminModuleController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPaymentProviderController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Frontend\CompanyBrandLogoController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\FrontendAuthController;
use App\Http\Controllers\Frontend\InfoRequestController;
use App\Http\Controllers\Frontend\MatchController;
use App\Modules\NexaTaxi\Controllers\TaxiPortalApiController;
use App\Modules\NexaTaxi\Controllers\TaxiPortalController;
use App\Http\Controllers\Frontend\NexaTaxiBookingController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\WebsitePageController;
use App\Http\Controllers\PublicVacancyController;
use App\Models\Vacancy;
use App\Services\WebsiteBuilderService;
use App\Support\ModuleSchemaAvailability;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::bind('job', function (string $value) {
    if (! ModuleSchemaAvailability::vacanciesTableExists()) {
        abort(404);
    }

    return Vacancy::whereKey($value)->firstOrFail();
});

// Debug route for upload limits (publiek)
Route::get('/debug-upload-limits', function () {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_execution_time' => ini_get('max_execution_time'),
        'max_input_time' => ini_get('max_input_time'),
        'memory_limit' => ini_get('memory_limit'),
        'max_file_uploads' => ini_get('max_file_uploads'),
    ]);
});

// Direct file serving route (before any middleware)
Route::get('/file/{path}', function ($path) {
    $filePath = str_replace('--', '/', $path);
    $file = storage_path('app/public/'.$filePath);

    if (! file_exists($file) || ! is_file($file)) {
        abort(404);
    }

    $mimeType = mime_content_type($file);
    $content = file_get_contents($file);

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');

Route::get('/email-logo/{company}', App\Http\Controllers\EmailCompanyLogoController::class)
    ->name('email.company-logo');

// Browsers vragen vaak /favicon.ico aan (vóór <link rel="icon">). Geen leeg bestand in public/ gebruiken.
Route::get('/favicon.ico', function () {
    $meta = app(\App\Services\WebsiteBuilderService::class)->publicFaviconMeta();
    $path = parse_url($meta['url'], PHP_URL_PATH);
    if (is_string($path) && str_starts_with($path, '/file/')) {
        $storagePath = str_replace('--', '/', ltrim(substr($path, strlen('/file/')), '/'));
        $file = storage_path('app/public/'.$storagePath);
        if (is_file($file)) {
            return response(file_get_contents($file), 200, [
                'Content-Type' => $meta['type'],
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    $fallback = public_path('images/nexa-x-logo.png');
    if (is_file($fallback)) {
        return response(file_get_contents($fallback), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    abort(404);
});

// BLOB photo serving route (authenticated users only)
Route::get('/user-photo/{id}', function ($id) {
    // Check if user is authenticated
    if (! Auth::check()) {
        abort(404);
    }

    $user = \App\Models\User::find($id);

    if (! $user || ! $user->photo_blob) {
        abort(404);
    }

    // Only allow users to view their own photo
    if (Auth::id() !== $user->id) {
        abort(404);
    }

    $content = base64_decode($user->photo_blob);
    $mimeType = $user->photo_mime_type ?: 'image/jpeg';

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'private, max-age=3600', // Private cache, shorter duration
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
    ]);
})->name('user.photo');

// Alternative secure photo route with token
Route::get('/secure-photo/{token}', function ($token) {
    // Decode and validate token
    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);

    if (count($parts) !== 2) {
        abort(404);
    }

    $userId = $parts[0];
    $hash = $parts[1];

    // Verify token integrity
    $user = \App\Models\User::find($userId);
    if (! $user) {
        abort(404);
    }

    $expectedHash = hash('sha256', $userId.$user->updated_at.config('app.key'));
    if (! hash_equals($expectedHash, $hash)) {
        abort(404);
    }

    if (! $user->photo_blob) {
        abort(404);
    }

    $content = base64_decode($user->photo_blob);
    $mimeType = $user->photo_mime_type ?: 'image/jpeg';

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'private, max-age=1800', // Even shorter cache for tokens
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
    ]);
})->name('secure.photo');

// Company photo access route (for companies to view candidate photos)
Route::get('/candidate-photo/{token}', function ($token) {
    // Decode and validate company token
    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);

    if (count($parts) !== 3) {
        abort(404);
    }

    $userId = $parts[0];
    $companyId = $parts[1];
    $hash = $parts[2];

    // Verify token integrity with company context
    $expectedHash = hash('sha256', $userId.$companyId.config('app.key'));
    if (! hash_equals($expectedHash, $hash)) {
        abort(404);
    }

    $user = \App\Models\User::find($userId);

    if (! $user || ! $user->photo_blob) {
        abort(404);
    }

    // Verify company exists and is active
    $company = \App\Models\Company::find($companyId);
    if (! $company || ! $company->is_active) {
        abort(404);
    }

    $content = base64_decode($user->photo_blob);
    $mimeType = $user->photo_mime_type ?: 'image/jpeg';

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'private, max-age=3600', // 1 hour cache for companies
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
    ]);
})->name('candidate.photo');

// Company logo serving route (authenticated admin users only)
Route::get('/company-logo/{company}', function ($companyId) {
    // Check if user is authenticated
    if (! Auth::check()) {
        abort(404);
    }

    $company = \App\Models\Company::find($companyId);

    if (! $company || ! $company->logo_blob) {
        abort(404);
    }

    // Check if user has permission to view companies
    if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('view-companies')) {
        abort(403);
    }

    $content = base64_decode($company->logo_blob);
    $mimeType = $company->logo_mime_type ?: 'image/png';

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'private, max-age=3600',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
    ]);
})->name('admin.companies.logo');

// Company logo donkere modus (optioneel; fallback in sidebar naar gewoon logo)
Route::get('/company-logo/{company}/dark', function ($companyId) {
    if (! Auth::check()) {
        abort(404);
    }

    $company = \App\Models\Company::find($companyId);

    if (! $company || ! $company->logo_dark_blob) {
        abort(404);
    }

    if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('view-companies')) {
        abort(403);
    }

    $content = base64_decode($company->logo_dark_blob);
    $mimeType = $company->logo_dark_mime_type ?: 'image/png';

    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'private, max-age=3600',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
    ]);
})->name('admin.companies.logo.dark');

// Bedrijfslogo voor frontend (tenant-domein of ingelogde gebruiker van hetzelfde bedrijf)
Route::get('/brand/company/{company}/logo', [CompanyBrandLogoController::class, 'show'])
    ->name('frontend.company-brand.logo');
Route::get('/brand/company/{company}/logo/dark', [CompanyBrandLogoController::class, 'showDark'])
    ->name('frontend.company-brand.logo.dark');

// Publieke vacatures routes - redirect naar /jobs
Route::get('/vacatures', function () {
    return redirect()->route('jobs.index');
})->name('vacatures.index');
Route::get('/vacatures/{company:slug}/{vacancy}', [PublicVacancyController::class, 'show'])->name('vacatures.show');

// Frontend meld: sessie verlopen (toegankelijk zonder login)
Route::get('/meld/sessie-verlopen', function (\Illuminate\Http\Request $request) {
    // Bewaar de bedoelde URL voor na inloggen (alleen frontend-pagina's, geen /admin)
    $intended = $request->query('intended');
    if ($intended && is_string($intended)) {
        $path = parse_url($intended, PHP_URL_PATH) ?? '';
        if ($path !== '' && ! \Illuminate\Support\Str::startsWith($path, '/admin')) {
            session(['url.intended' => $intended]);
        }
    }

    return view('meld.redirect', [
        'title' => 'Sessie verlopen',
        'message' => 'Uw sessie is verlopen. Log opnieuw in om verder te gaan.',
        'redirectUrl' => route('login'),
        'redirectLabel' => 'Naar inlogpagina',
    ]);
})->name('meld.sessie-verlopen');

// Frontend vacancy details (company slug + vacancy id; no model binding to avoid type confusion)
Route::get('/vacature/{companySlug}/{vacancyId}', [PublicVacancyController::class, 'frontendShow'])->name('frontend.vacancy-details')->whereNumber('vacancyId');

// Legacy Skillmatching route(s) uitgefaseerd: centrale NEXA-welkomstpagina is leidend.
Route::get('/jobs', fn () => redirect()->route('home'))->name('jobs.index');
Route::get('/jobs/{job}', fn () => redirect()->route('home'))->name('jobs.show');

// Admin Authentication Routes (without admin middleware)
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
Route::get('/admin/manifest.webmanifest', App\Http\Controllers\Admin\AdminWebManifestController::class)->name('admin.manifest');

/*
| Sessiecheck voor JavaScript in de admin-layout: alleen web + auth (geen AdminMiddleware-rolcheck).
| Anders kan een AJAX-call 403 geven (bijv. edge cases met permissies) terwijl de pagina wél geladen is,
| wat tot een redirect naar login/meld leidt.
*/
Route::middleware(['web', 'auth:web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('api/session-check', function () {
        return response()->noContent();
    })->name('api.session-check');
});

// Admin meld: sessie verlopen (toegankelijk zonder login)
Route::get('/admin/meld/sessie-verlopen', function (\Illuminate\Http\Request $request) {
    // Bewaar de bedoelde URL voor na inloggen (alleen admin-pagina's, nooit login/meld zelf)
    $intended = AdminReturnUrl::resolveIntended($request->query('intended'));
    if ($intended !== null) {
        session(['url.intended' => $intended]);
    }
    $appName = \App\Models\GeneralSetting::get('site_name', config('app.name'));
    $redirectUrl = AdminReturnUrl::loginUrlWithIntended($intended);

    return view('admin.meld.redirect', [
        'title' => 'Sessie verlopen',
        'message' => 'Uw sessie is verlopen. Log opnieuw in om verder te gaan.',
        'redirectUrl' => $redirectUrl,
        'redirectLabel' => 'Naar inlogpagina',
        'appName' => $appName ?: config('app.name'),
    ]);
})->name('admin.meld.sessie-verlopen');

// Password Reset Routes
Route::get('/admin/password/reset', [AdminAuthController::class, 'showLinkRequestForm'])->name('admin.password.request');
Route::post('/admin/password/email', [AdminAuthController::class, 'sendResetLinkEmail'])->middleware('throttle:6,1')->name('admin.password.email');
Route::get('/admin/password/reset/{token}', [AdminAuthController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/admin/password/reset', [AdminAuthController::class, 'reset'])->middleware('throttle:6,1')->name('admin.password.update');
Route::get('/admin/password/changed', [AdminAuthController::class, 'showPasswordChanged'])->name('admin.password.changed');

// Admin Protected Routes
Route::middleware(['web', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('ai-chat/message', [App\Http\Controllers\Admin\AdminAiChatController::class, 'sendMessage'])
        ->middleware('throttle:60,1')
        ->name('ai-chat.message');

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/tenant/switch', [AdminDashboardController::class, 'switchTenant'])->name('tenant.switch');

    Route::get('handleiding', [AdminHandleidingController::class, 'index'])->name('handleiding.index');
    Route::get('handleiding/{slug}', [AdminHandleidingController::class, 'show'])
        ->name('handleiding.show')
        ->where('slug', '[a-z0-9\-]+');

    // Companies
    Route::get('companies/wizard', [AdminCompanyWizardController::class, 'start'])->name('companies.wizard.start');
    Route::post('companies/wizard/step-1', [AdminCompanyWizardController::class, 'storeStep1'])->name('companies.wizard.store-step1');
    Route::get('companies/{company}/wizard/step/{step}', [AdminCompanyWizardController::class, 'step'])
        ->whereNumber('step')
        ->name('companies.wizard.step');
    Route::post('companies/{company}/wizard/step/{step}', [AdminCompanyWizardController::class, 'submitStep'])
        ->whereNumber('step')
        ->name('companies.wizard.submit-step');

    Route::resource('companies', AdminCompanyController::class);
    Route::post('companies/{company}/toggle-status', [AdminCompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
    Route::post('companies/{company}/toggle-main-location', [AdminCompanyController::class, 'toggleMainLocation'])->name('companies.toggle-main-location');
    Route::post('companies/{company}/upload-logo', [AdminCompanyController::class, 'uploadLogo'])->name('companies.upload-logo');

    Route::post('companies/{company}/domains', [AdminCompanyDomainController::class, 'store'])->name('companies.domains.store');
    Route::delete('companies/{company}/domains/{domain}', [AdminCompanyDomainController::class, 'destroy'])->name('companies.domains.destroy');
    Route::post('companies/{company}/domains/{domain}/primary', [AdminCompanyDomainController::class, 'setPrimary'])->name('companies.domains.primary');

    Route::middleware('role:super-admin')->group(function () {
        Route::view('playground/metronic-demo1', 'admin.metronic-vue-demo1')->name('playground.metronic-demo1');
        Route::get('companies/{company}/website-bundle/export', [App\Http\Controllers\Admin\AdminTenantWebsiteBundleController::class, 'export'])->name('companies.website-bundle.export');
        Route::post('companies/{company}/website-bundle/import', [App\Http\Controllers\Admin\AdminTenantWebsiteBundleController::class, 'import'])->name('companies.website-bundle.import');
    });

    // Pipeline Templates
    Route::get('companies/{company}/pipeline-templates', [App\Http\Controllers\Admin\PipelineTemplateController::class, 'index'])->name('companies.pipeline-templates.index');
    Route::get('companies/{company}/pipeline-templates/{pipelineTemplate}/edit', [App\Http\Controllers\Admin\PipelineTemplateController::class, 'edit'])->name('companies.pipeline-templates.edit');
    Route::put('companies/{company}/pipeline-templates/{pipelineTemplate}', [App\Http\Controllers\Admin\PipelineTemplateController::class, 'update'])->name('companies.pipeline-templates.update');
    Route::post('companies/{company}/pipeline-templates/create-from-default', [App\Http\Controllers\Admin\PipelineTemplateController::class, 'createFromDefault'])->name('companies.pipeline-templates.create-from-default');

    // Stage Instances
    Route::post('stage-instances/initialize/{type}/{id}', [App\Http\Controllers\Admin\StageInstanceController::class, 'initialize'])->name('stage-instances.initialize');
    Route::get('stage-instances/{stageInstance}', [App\Http\Controllers\Admin\StageInstanceController::class, 'show'])->name('stage-instances.show');
    Route::put('stage-instances/{stageInstance}', [App\Http\Controllers\Admin\StageInstanceController::class, 'update'])->name('stage-instances.update');

    // Company Locations
    Route::get('companies/{company}/users/json', [AdminCompanyController::class, 'getUsersJson'])->name('companies.users.json');
    Route::get('companies/{company}/locations/json', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'getLocationsJson'])->name('companies.locations.json');
    Route::get('companies/{company}/locations/create', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'create'])->name('companies.locations.create');
    Route::post('companies/{company}/locations', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'store'])->name('companies.locations.store');
    Route::get('companies/{company}/locations/{location}', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'show'])->name('companies.locations.show');
    Route::get('companies/{company}/locations/{location}/edit', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'edit'])->name('companies.locations.edit');
    Route::put('companies/{company}/locations/{location}', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'update'])->name('companies.locations.update');
    Route::delete('companies/{company}/locations/{location}', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'destroy'])->name('companies.locations.destroy');
    Route::post('companies/{company}/locations/{location}/set-main', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'setMain'])->name('companies.locations.set-main');
    Route::post('companies/{company}/locations/{location}/toggle-status', [App\Http\Controllers\Admin\AdminCompanyLocationController::class, 'toggleStatus'])->name('companies.locations.toggle-status');

    // Users
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('users.assign-role');
    Route::post('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/send-activation-link', [AdminUserController::class, 'sendActivationLink'])->name('users.send-activation-link');
    Route::get('users/{user}/photo', [AdminUserController::class, 'photo'])->name('users.photo');
    Route::match(['get', 'post'], 'api/job-titles', [AdminUserController::class, 'getJobTitles'])->name('api.job-titles');

    // Branches routes zijn verplaatst naar Skillmatching module (admin/skillmatching/branches)
    // Redirects voor backward compatibility - alleen GET routes redirecten
    Route::get('branches', function () {
        return redirect('/admin/skillmatching/branches');
    });
    Route::get('branches/create', function () {
        return redirect('/admin/skillmatching/branches/create');
    });
    Route::get('branches/functions/all', function () {
        return redirect('/admin/skillmatching/branches/functions/all');
    });
    Route::get('branches/{branch}', function ($branch) {
        // Probeer eerst slug, dan ID
        $branchModel = \App\Models\Branch::where('slug', $branch)->orWhere('id', $branch)->first();
        if ($branchModel) {
            // Genereer slug als die niet bestaat
            if (empty($branchModel->slug)) {
                $branchModel->slug = \Illuminate\Support\Str::slug($branchModel->name);
                $baseSlug = $branchModel->slug;
                $counter = 1;
                while (\App\Models\Branch::where('slug', $branchModel->slug)->where('id', '!=', $branchModel->id)->exists()) {
                    $branchModel->slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $branchModel->save();
            }

            return redirect('/admin/skillmatching/branches/'.$branchModel->slug);
        }
        abort(404);
    });
    Route::get('branches/{branch}/edit', function ($branch) {
        $branchModel = \App\Models\Branch::where('slug', $branch)->orWhere('id', $branch)->first();
        if ($branchModel) {
            if (empty($branchModel->slug)) {
                $branchModel->slug = \Illuminate\Support\Str::slug($branchModel->name);
                $baseSlug = $branchModel->slug;
                $counter = 1;
                while (\App\Models\Branch::where('slug', $branchModel->slug)->where('id', '!=', $branchModel->id)->exists()) {
                    $branchModel->slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $branchModel->save();
            }

            return redirect('/admin/skillmatching/branches/'.$branchModel->slug.'/edit');
        }
        abort(404);
    });
    Route::get('branches/{branch}/data', function ($branch) {
        $branchModel = \App\Models\Branch::where('slug', $branch)->orWhere('id', $branch)->first();
        if ($branchModel) {
            if (empty($branchModel->slug)) {
                $branchModel->slug = \Illuminate\Support\Str::slug($branchModel->name);
                $baseSlug = $branchModel->slug;
                $counter = 1;
                while (\App\Models\Branch::where('slug', $branchModel->slug)->where('id', '!=', $branchModel->id)->exists()) {
                    $branchModel->slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $branchModel->save();
            }

            return redirect('/admin/skillmatching/branches/'.$branchModel->slug.'/data');
        }
        abort(404);
    });
    Route::get('branches/{branch}/functions/{function}/skills', function ($branch, $function) {
        $branchModel = \App\Models\Branch::where('slug', $branch)->orWhere('id', $branch)->first();
        if ($branchModel) {
            if (empty($branchModel->slug)) {
                $branchModel->slug = \Illuminate\Support\Str::slug($branchModel->name);
                $baseSlug = $branchModel->slug;
                $counter = 1;
                while (\App\Models\Branch::where('slug', $branchModel->slug)->where('id', '!=', $branchModel->id)->exists()) {
                    $branchModel->slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $branchModel->save();
            }

            return redirect('/admin/skillmatching/branches/'.$branchModel->slug.'/functions/'.$function.'/skills');
        }
        abort(404);
    });

    // Vacancies - Moved to Skillmatching module

    // Chat routes
    Route::post('chat/start', [ChatController::class, 'startChat'])->name('chat.start');
    Route::get('chat/active', [ChatController::class, 'getActiveChats'])->name('chat.active');
    Route::get('chat/candidates', [ChatController::class, 'getCandidatesWithMatches'])->name('chat.candidates');
    Route::get('chat/unread-count', [ChatController::class, 'getUnreadCount'])->name('chat.unread-count');

    // Notification routes
    Route::get('notifications/unread-count', [AdminNotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('chat/{chat}/messages', [ChatController::class, 'getChatMessages'])->name('chat.messages');
    Route::post('chat/{chat}/message', [ChatController::class, 'sendChatMessage'])->name('chat.message.send');
    Route::post('chat/{chat}/end', [ChatController::class, 'endChat'])->name('chat.end');
    Route::delete('chat/{chat}', [ChatController::class, 'deleteChat'])->name('chat.delete');
    Route::get('chat/history', [ChatController::class, 'getChatHistory'])->name('chat.history');
    Route::post('chat/{chat}/typing', [ChatController::class, 'setChatTyping'])->name('chat.typing');
    Route::get('chat/{chat}/typing', [ChatController::class, 'getChatTyping'])->name('chat.typing.get');
    Route::post('chat/{chat}/presence', [ChatController::class, 'setChatPresence'])->name('chat.presence');
    Route::get('chat/{chat}/presence', [ChatController::class, 'getChatPresence'])->name('chat.presence.get');

    // Matches
    // Matches - Moved to Skillmatching module

    // Interviews
    // Interviews - Moved to Skillmatching module

    // Agenda
    Route::get('agenda', [App\Http\Controllers\Admin\AgendaController::class, 'index'])->name('agenda.index');
    Route::get('agenda/events', [App\Http\Controllers\Admin\AgendaController::class, 'events'])->name('agenda.events');

    // Profile
    Route::get('profile', [AdminProfileController::class, 'index'])->name('profile');
    Route::post('profile/update', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/photo', [AdminProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::post('profile/cv', [AdminProfileController::class, 'uploadCV'])->name('profile.cv');
    Route::post('profile/cv/remove', [AdminProfileController::class, 'removeCV'])->name('profile.cv.remove');
    Route::post('profile/skills', [AdminProfileController::class, 'addSkill'])->name('profile.skills.add');
    Route::delete('profile/skills/{skillId}', [AdminProfileController::class, 'removeSkill'])->name('profile.skills.remove');
    Route::post('profile/experiences', [AdminProfileController::class, 'addExperience'])->name('profile.experiences.add');
    Route::get('profile/experiences/{experienceId}', [AdminProfileController::class, 'showExperience'])->name('profile.experiences.show');
    Route::put('profile/experiences/{experienceId}', [AdminProfileController::class, 'updateExperience'])->name('profile.experiences.update');
    Route::delete('profile/experiences/{experienceId}', [AdminProfileController::class, 'removeExperience'])->name('profile.experiences.remove');

    // Notifications
    // Specific routes must come BEFORE resource route to avoid route conflicts
    Route::get('notifications/list', [AdminNotificationController::class, 'getNotifications'])->name('notifications.list');
    Route::get('notifications/unread-count', [AdminNotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::post('notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('notifications/mark-selected-read', [AdminNotificationController::class, 'markSelectedAsRead'])->name('notifications.mark-selected-read');
    Route::post('notifications/archive-selected', [AdminNotificationController::class, 'archiveSelected'])->name('notifications.archive-selected');
    Route::post('notifications/{notification}/mark-read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/{notification}/respond-interview', [AdminNotificationController::class, 'respondToInterview'])->name('notifications.respond-interview');
    Route::resource('notifications', AdminNotificationController::class);

    // Email Templates — form-fields moet vóór de parent resource, anders matcht
    // GET /email-templates/form-fields op email-templates/{id} (show) met id "form-fields".
    Route::resource('email-templates/form-fields', AdminFormFieldController::class)
        ->parameters(['form-fields' => 'info_request_form_field'])
        ->except(['show'])
        ->names([
            'index' => 'email-templates.form-fields.index',
            'create' => 'email-templates.form-fields.create',
            'store' => 'email-templates.form-fields.store',
            'edit' => 'email-templates.form-fields.edit',
            'update' => 'email-templates.form-fields.update',
            'destroy' => 'email-templates.form-fields.destroy',
        ]);

    Route::resource('email-templates', AdminEmailTemplateController::class);
    Route::post('email-templates/{emailTemplate}/toggle-status', [AdminEmailTemplateController::class, 'toggleStatus'])->name('email-templates.toggle-status');
    Route::post('email-templates/{emailTemplate}/duplicate', [AdminEmailTemplateController::class, 'duplicate'])->name('email-templates.duplicate');
    Route::post('email-templates/{emailTemplate}/send-test', [AdminEmailTemplateController::class, 'sendTest'])->name('email-templates.send-test');

    // Candidates (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('candidates', AdminCandidateController::class);
        Route::post('candidates/{candidate}/toggle-status', [AdminCandidateController::class, 'toggleStatus'])->name('candidates.toggle-status');
        Route::get('candidates/{candidate}/download-cv', [AdminCandidateController::class, 'downloadCV'])->name('candidates.download-cv');
        Route::get('candidates/{candidate}/photo', [AdminCandidateController::class, 'getCandidatePhoto'])->name('candidates.photo');
    });

    // Modules Management (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::get('modules', [AdminModuleController::class, 'index'])->name('modules.index');
        Route::get('modules/{module}/config', [AdminModuleController::class, 'config'])->name('modules.config');
        Route::post('modules/{module}/config', [AdminModuleController::class, 'saveConfig'])->name('modules.config.store');
        Route::post('modules/{module}/install', [AdminModuleController::class, 'install'])->name('modules.install');
        Route::post('modules/{module}/activate', [AdminModuleController::class, 'activate'])->name('modules.activate');
        Route::post('modules/{module}/deactivate', [AdminModuleController::class, 'deactivate'])->name('modules.deactivate');
        Route::post('modules/{module}/uninstall', [AdminModuleController::class, 'uninstall'])->name('modules.uninstall');
        Route::post('modules/database-reset', [AdminModuleController::class, 'databaseReset'])->name('modules.database-reset');
        Route::post('modules/{module}/database-dummydata', [AdminModuleController::class, 'databaseDummydata'])->name('modules.database-dummydata');
        Route::post('modules/{module}/run-migrations', [AdminModuleController::class, 'runModuleMigrations'])->name('modules.run-migrations');
    });

    // Roles & Permissions (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('roles', AdminRoleController::class);
        Route::post('roles/{role}/toggle-status', [AdminRoleController::class, 'toggleStatus'])->name('roles.toggle-status');

        // Bulk permission routes must come BEFORE the resource route to avoid route conflicts
        Route::get('permissions/bulk/create', [AdminPermissionController::class, 'bulkCreate'])->name('permissions.bulk-create');
        Route::post('permissions/bulk/store', [AdminPermissionController::class, 'bulkStore'])->name('permissions.bulk-store');
        Route::get('permissions/bulk/edit', [AdminPermissionController::class, 'bulkEdit'])->name('permissions.bulk-edit');
        Route::post('permissions/bulk/update', [AdminPermissionController::class, 'bulkUpdate'])->name('permissions.bulk-update');
        Route::delete('permissions/bulk/delete', [AdminPermissionController::class, 'bulkDelete'])->name('permissions.bulk-delete');

        // Resource route for individual permissions (must come after bulk routes)
        Route::resource('permissions', AdminPermissionController::class);
        Route::post('permissions/{permission}/assign-to-role', [AdminPermissionController::class, 'assignToRole'])->name('permissions.assign-to-role');

        // Payment Providers (Super Admin only)
        Route::resource('payment-providers', AdminPaymentProviderController::class);
        Route::post('payment-providers/{paymentProvider}/toggle-status', [AdminPaymentProviderController::class, 'toggleStatus'])->name('payment-providers.toggle-status');
        Route::post('payment-providers/{paymentProvider}/test-connection', [AdminPaymentProviderController::class, 'testConnection'])->name('payment-providers.test-connection');

        // Payments (Super Admin only)
        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/openstaand', [AdminPaymentController::class, 'openstaand'])->name('payments.openstaand');
        Route::get('payments/voldaan', [AdminPaymentController::class, 'voldaan'])->name('payments.voldaan');

        // Invoices (Super Admin only)
        // Settings routes moeten vóór resource route staan om route conflict te voorkomen
        Route::get('invoices/settings', [AdminInvoiceController::class, 'settings'])->name('invoices.settings');
        Route::post('invoices/settings', [AdminInvoiceController::class, 'updateSettings'])->name('invoices.settings.update');
        Route::get('invoices/matches-for-company', [AdminInvoiceController::class, 'getMatchesForCompany'])->name('invoices.matches-for-company');
        Route::resource('invoices', AdminInvoiceController::class);
        Route::post('invoices/{invoice}/send-reminder', [AdminInvoiceController::class, 'sendReminder'])->name('invoices.send-reminder');
        Route::get('invoices/{invoice}/pdf', [AdminInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/payment-links', [AdminInvoiceController::class, 'paymentLinks'])->name('invoices.payment-links');

        // Job Configurations (Super Admin only)
        Route::delete('job-configurations/bulk/delete', [App\Http\Controllers\Admin\AdminJobConfigurationController::class, 'bulkDelete'])->name('job-configurations.bulk-delete');
        Route::resource('job-configurations', App\Http\Controllers\Admin\AdminJobConfigurationController::class);

        // Job Configuration Types (Super Admin only)
        Route::resource('job-configuration-types', App\Http\Controllers\Admin\AdminJobConfigurationTypeController::class);
        Route::post('job-configuration-types/{jobConfigurationType}/toggle-status', [App\Http\Controllers\Admin\AdminJobConfigurationTypeController::class, 'toggleStatus'])->name('job-configuration-types.toggle-status');
        Route::match(['get', 'post'], 'job-configuration-types/import', [App\Http\Controllers\Admin\AdminJobConfigurationTypeController::class, 'import'])->name('job-configuration-types.import');

        // Settings (Super Admin only)
        Route::get('settings', [App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/mail', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateMail'])->name('settings.mail.update');
        Route::post('settings/mail/test', [App\Http\Controllers\Admin\AdminSettingsController::class, 'testEmail'])->name('settings.mail.test');
        Route::post('settings/seo', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateSeo'])->name('settings.seo.update');
        Route::post('settings/seo/test', [App\Http\Controllers\Admin\AdminSettingsController::class, 'testSeoConnection'])->name('settings.seo.test');
        Route::post('settings/seo/submit-sitemap', [App\Http\Controllers\Admin\AdminSettingsController::class, 'submitSeoSitemap'])->name('settings.seo.submit-sitemap');
        Route::post('settings/maps', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateMaps'])->name('settings.maps.update');
        Route::post('settings/google-reviews', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateGoogleReviews'])->name('settings.google-reviews.update');
        Route::post('settings/whatsapp', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
        Route::post('settings/coming-soon', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateComingSoon'])->name('settings.coming-soon.update');
        Route::post('settings/tenant-sync', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateTenantSync'])->name('settings.tenant-sync.update');
        Route::post('settings/tenant-sync/target/create', [App\Http\Controllers\Admin\AdminSettingsController::class, 'createTenantSyncTarget'])->name('settings.tenant-sync.target.create');
        Route::post('settings/tenant-sync/target/activate', [App\Http\Controllers\Admin\AdminSettingsController::class, 'activateTenantSyncTarget'])->name('settings.tenant-sync.target.activate');
        Route::post('settings/tenant-sync/target/delete', [App\Http\Controllers\Admin\AdminSettingsController::class, 'deleteTenantSyncTarget'])->name('settings.tenant-sync.target.delete');
        Route::post('settings/tenant-sync/test', [App\Http\Controllers\Admin\AdminSettingsController::class, 'testTenantSync'])->name('settings.tenant-sync.test');
        Route::post('settings/tenant-sync/run', [App\Http\Controllers\Admin\AdminSettingsController::class, 'runTenantSync'])->name('settings.tenant-sync.run');
        Route::get('settings/tenant-storage-bundle/export', [App\Http\Controllers\Admin\AdminSettingsController::class, 'exportTenantStorageBundle'])->name('settings.tenant-storage-bundle.export');
        Route::post('settings/tenant-storage-bundle/import', [App\Http\Controllers\Admin\AdminSettingsController::class, 'importTenantStorageBundle'])->name('settings.tenant-storage-bundle.import');
        Route::get('settings/tenant-website-bundle/export', [App\Http\Controllers\Admin\AdminSettingsController::class, 'exportTenantWebsiteBundle'])->name('settings.tenant-website-bundle.export');
        Route::post('settings/tenant-website-bundle/import', [App\Http\Controllers\Admin\AdminSettingsController::class, 'importTenantWebsiteBundle'])->name('settings.tenant-website-bundle.import');

        // General Settings (Super Admin only)
        Route::get('settings/frontend', [App\Http\Controllers\Admin\AdminSettingsController::class, 'frontendIndex'])->name('settings.frontend.index');
        Route::get('settings/frontend/preview', [App\Http\Controllers\Admin\AdminSettingsController::class, 'frontendComingSoonPreview'])->name('settings.frontend.preview');
        Route::get('settings/general', [App\Http\Controllers\Admin\AdminSettingsController::class, 'generalIndex'])->name('settings.general.index');
        Route::post('settings/general', [App\Http\Controllers\Admin\AdminSettingsController::class, 'generalUpdate'])->name('settings.general.update');
        Route::get('settings/upgrade', [App\Http\Controllers\Admin\AdminSystemUpgradeController::class, 'index'])->name('settings.upgrade.index');
        Route::get('settings/upgrade/preview', [App\Http\Controllers\Admin\AdminSystemUpgradeController::class, 'preview'])->name('settings.upgrade.preview');
        Route::post('settings/upgrade/run', [App\Http\Controllers\Admin\AdminSystemUpgradeController::class, 'run'])->name('settings.upgrade.run');
        Route::post('settings/upload-logo', [App\Http\Controllers\Admin\AdminSettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
        Route::post('settings/remove-logo-light', [App\Http\Controllers\Admin\AdminSettingsController::class, 'removeLogoLight'])->name('settings.remove-logo-light');
        Route::post('settings/remove-logo-dark', [App\Http\Controllers\Admin\AdminSettingsController::class, 'removeLogoDark'])->name('settings.remove-logo-dark');
        Route::post('settings/upload-favicon', [App\Http\Controllers\Admin\AdminSettingsController::class, 'uploadFavicon'])->name('settings.upload-favicon');
        Route::post('settings/logo-size', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateLogoSize'])->name('settings.logo-size.update');
        Route::get('settings/logo', [App\Http\Controllers\Admin\AdminSettingsController::class, 'getLogo'])->name('settings.logo');
        Route::get('settings/logo-dark', [App\Http\Controllers\Admin\AdminSettingsController::class, 'getLogoDark'])->name('settings.logo-dark');
        Route::get('settings/favicon', [App\Http\Controllers\Admin\AdminSettingsController::class, 'getFavicon'])->name('settings.favicon');
        Route::post('settings/upload-success-image', [App\Http\Controllers\Admin\AdminSettingsController::class, 'uploadSuccessImage'])->name('settings.upload-success-image');
        Route::post('settings/remove-success-image', [App\Http\Controllers\Admin\AdminSettingsController::class, 'removeSuccessImage'])->name('settings.remove-success-image');
        Route::get('settings/success-image', [App\Http\Controllers\Admin\AdminSettingsController::class, 'getSuccessImage'])->name('settings.success-image');
        Route::post('settings/upload-coming-soon-image', [App\Http\Controllers\Admin\AdminSettingsController::class, 'uploadComingSoonImage'])->name('settings.upload-coming-soon-image');
        Route::post('settings/remove-coming-soon-image', [App\Http\Controllers\Admin\AdminSettingsController::class, 'removeComingSoonImage'])->name('settings.remove-coming-soon-image');
        Route::get('settings/coming-soon-image', [App\Http\Controllers\Admin\AdminSettingsController::class, 'getComingSoonImage'])->name('settings.coming-soon-image');

        // Welkom-pagina editor (Super Admin only)
        Route::get('welcome-page', [App\Http\Controllers\Admin\AdminWelcomePageController::class, 'edit'])->name('welcome-page.edit');

        // Website builder (Super Admin only)
        Route::get('website-pages/theme-blocks', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'themeBlocks'])->name('website-pages.theme-blocks');
        Route::get('website-pages/section-card-html', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'sectionCardHtml'])->name('website-pages.section-card-html');
        Route::get('website-pages/component-section-html', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'componentSectionCardHtml'])->name('website-pages.component-section-html');
        Route::post('website-pages/upload-footer-logo', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'uploadFooterLogo'])->name('website-pages.upload-footer-logo');
        Route::post('website-pages/upload-hero-image', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'uploadHeroImage'])->name('website-pages.upload-hero-image');
        Route::post('website-pages/upload-wysiwyg-document', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'uploadWysiwygDocument'])->name('website-pages.upload-wysiwyg-document');
        Route::post('website-pages/generate-seo', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'generateSeoContent'])->name('website-pages.generate-seo');
        Route::get('website-pages/{website_page}/preview', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'preview'])->name('website-pages.preview');
        Route::get('website-pages/{website_page}/builder-v2', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'editV2'])->name('website-pages.builder-v2.edit');
        Route::put('website-pages/{website_page}/builder-v2', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'updateV2'])->name('website-pages.builder-v2.update');
        Route::patch('website-pages/{website_page}/builder-v2/meta', [App\Http\Controllers\Admin\AdminWebsitePageController::class, 'updatePageMetaV2'])->name('website-pages.builder-v2.update-meta');
        Route::resource('website-pages', App\Http\Controllers\Admin\AdminWebsitePageController::class)->names('website-pages');
        Route::post('website-media/upload', [App\Http\Controllers\Admin\AdminWebsiteMediaController::class, 'upload'])->name('website-media.upload');
        Route::delete('website-media/{uuid}', [App\Http\Controllers\Admin\AdminWebsiteMediaController::class, 'destroy'])->name('website-media.destroy')->where('uuid', '[\w\-]+');
        Route::get('frontend-themes', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'index'])->name('frontend-themes.index');
        Route::get('frontend-themes/preview', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'servePreview'])->name('frontend-themes.preview');
        Route::get('frontend-themes/staging', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'staging'])->name('frontend-themes.staging');
        Route::post('frontend-themes/publish', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'publish'])->name('frontend-themes.publish');
        Route::post('frontend-themes/unpublish', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'unpublish'])->name('frontend-themes.unpublish');
        Route::get('frontend-themes/setup', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'showSetup'])->name('frontend-themes.setup');
        Route::post('frontend-themes/module-theme', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'updateModuleTheme'])->name('frontend-themes.update-module-theme');
        Route::post('frontend-themes/{frontend_theme}/set-active', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'setActive'])->name('frontend-themes.set-active');
        Route::get('frontend-themes/{frontend_theme}/edit', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'edit'])->name('frontend-themes.edit');
        Route::put('frontend-themes/{frontend_theme}', [App\Http\Controllers\Admin\AdminFrontendThemeController::class, 'update'])->name('frontend-themes.update');
        Route::get('frontend-components', [App\Http\Controllers\Admin\AdminFrontendComponentController::class, 'index'])->name('frontend-components.index');
        Route::get('frontend-components/{componentId}/demo', [App\Http\Controllers\Admin\AdminFrontendComponentController::class, 'demo'])->name('frontend-components.demo');

        // Postcode lookup (for address autocomplete)
        Route::post('postcode/lookup', [App\Http\Controllers\PostcodeController::class, 'lookup'])->name('postcode.lookup');
    });
});

// Frontend home page
// Centraal domein (localhost, nexa.tosun.nl): altijd NEXA welkomstpagina.
// Tenant-domein (bedrijf.nl, demo.nexasuite.nl): bedrijfsspecifieke pagina via website-builder.
Route::get('/', function (\Illuminate\Http\Request $request) {
    $isTenant = app()->bound('resolved_tenant') && app('resolved_tenant') !== null;

    if (! $isTenant) {
        $central = app(\App\Services\WebsiteBuilderService::class)->getCentralMarketingWelcomePage();
        if ($central) {
            return app(\App\Http\Controllers\Frontend\WebsitePageController::class)->showCentralWelcome($central);
        }
        $w = \App\Http\Controllers\Admin\AdminWelcomePageController::getWelcomeContent();

        return view('frontend.welcome', compact('w'));
    }

    $websiteBuilder = app(WebsiteBuilderService::class);
    $homePage = $websiteBuilder->getHomePage();
    if ($homePage) {
        return app(WebsitePageController::class)->showHome($request);
    }

    $moduleManager = app(\App\Services\ModuleManager::class);
    if (! $moduleManager->hasAnyActiveModule()) {
        return app(\App\Http\Controllers\Frontend\ComingSoonController::class)->index();
    }

    return app(\App\Http\Controllers\Frontend\HomeController::class)->index($request);
})->name('home');

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Website media: encrypted afbeeldingen (decrypt on serve, publiek voor frontend)
Route::get('website-media/{uuid}', [App\Http\Controllers\WebsiteMediaController::class, 'serve'])->name('website-media.serve')->where('uuid', '[\w\-]+');

// Vacature matching demo page
Route::get('/vacature-matching', [MatchController::class, 'demo'])->name('vacature-matching');

// Demo routes (demo1-demo10)
Route::get('/demo{demoNumber}', [App\Http\Controllers\DemoController::class, 'show'])->where('demoNumber', '[1-9]|10')->name('demo.show');
Route::get('/demo{demoNumber}/{path}', [App\Http\Controllers\DemoController::class, 'showSubpage'])->where('demoNumber', '[1-9]|10')->where('path', '.*')->name('demo.subpage');

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

// Favorite routes
Route::middleware('auth')->group(function () {
    Route::post('/favorites/{vacancy}/toggle', [App\Http\Controllers\Frontend\FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::get('/favorites/{vacancy}/check', [App\Http\Controllers\Frontend\FavoriteController::class, 'check'])->name('favorites.check');
    Route::get('/favorites', [App\Http\Controllers\Frontend\FavoriteController::class, 'index'])->name('favorites.index');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');

    // Debug route for upload limits
    Route::get('/debug-upload-limits', function () {
        return response()->json([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'memory_limit' => ini_get('memory_limit'),
        ]);
    });
    Route::post('/profile/skills', [ProfileController::class, 'addSkill'])->name('profile.skills.add');
    Route::delete('/profile/skills/{skill}', [ProfileController::class, 'removeSkill'])->name('profile.skills.remove');
    Route::post('/profile/experiences', [ProfileController::class, 'addExperience'])->name('profile.experiences.add');
    Route::get('/profile/experiences/{experience}', [ProfileController::class, 'showExperience'])->name('profile.experiences.show');
    Route::put('/profile/experiences/{experience}', [ProfileController::class, 'updateExperience'])->name('profile.experiences.update');
    Route::delete('/profile/experiences/{experience}', [ProfileController::class, 'removeExperience'])->name('profile.experiences.remove');
});

// Email verification route (public, no auth required)
Route::get('/verify-email/{user}', [App\Http\Controllers\Admin\AdminUserController::class, 'verifyEmail'])->name('verify-email');

// Frontend login (kandidaten / portaal)
Route::get('/login', [FrontendAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [FrontendAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.post');
Route::post('/login/code', [FrontendAuthController::class, 'loginWithCode'])->middleware('throttle:12,1')->name('login.code');
Route::post('/login/code/aanvragen', [FrontendAuthController::class, 'requestLoginCode'])->middleware('throttle:6,1')->name('login.code.request');
Route::get('/wachtwoord-instellen', [FrontendAuthController::class, 'showSetPasswordForm'])->middleware('auth')->name('frontend.set-password');
Route::post('/wachtwoord-instellen', [FrontendAuthController::class, 'setPassword'])->middleware('auth')->name('frontend.set-password.post');
Route::get('/register', fn () => redirect()->route('home'))->name('register');
Route::post('/register', fn () => redirect()->route('home'))->name('register.post');
Route::post('/logout', [FrontendAuthController::class, 'logout'])->name('logout');
Route::get('/logout', [FrontendAuthController::class, 'logout'])->name('logout.get');

Route::post('/ai-chat/message', [App\Http\Controllers\Frontend\AiChatController::class, 'sendMessage'])
    ->middleware('throttle:30,1')
    ->name('frontend.ai-chat.message');

Route::post('/info-request', [InfoRequestController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('frontend.send-info-request');

// Test routes voor error pagina's (alleen in development)
if (app()->environment('local', 'development')) {
    Route::get('/test-502', function () {
        return response()->view('errors.502', [], 502);
    });

    Route::get('/test-403', function () {
        return response()->view('errors.403', [], 403);
    });
}

// User dashboard routes
Route::middleware(['auth:web'])->group(function () {
    // Frontend chat routes
    Route::get('/chat/active', [App\Http\Controllers\Frontend\ChatController::class, 'getActiveChats'])->name('frontend.chat.active');
    Route::get('/chat/{chat}/messages', [App\Http\Controllers\Frontend\ChatController::class, 'getChatMessages'])->name('frontend.chat.messages');
    Route::post('/chat/{chat}/message', [App\Http\Controllers\Frontend\ChatController::class, 'sendChatMessage'])->name('frontend.chat.message.send');
    Route::post('/chat/{chat}/end', [App\Http\Controllers\Frontend\ChatController::class, 'endChat'])->name('frontend.chat.end');
    Route::delete('/chat/{chat}', [App\Http\Controllers\Frontend\ChatController::class, 'deleteChat'])->name('frontend.chat.delete');
    Route::get('/chat/unread-count', [App\Http\Controllers\Frontend\ChatController::class, 'getUnreadCount'])->name('frontend.chat.unread-count');
    Route::post('/chat/{chat}/presence', [App\Http\Controllers\Frontend\ChatController::class, 'setChatPresence'])->name('frontend.chat.presence');
    Route::get('/chat/{chat}/presence', [App\Http\Controllers\Frontend\ChatController::class, 'getChatPresence'])->name('frontend.chat.presence.get');
    Route::get('/notifications/unread-count', function () {
        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->whereNull('archived_at')->count();

        // Get highest priority of unread notifications
        $highestPriority = \App\Models\Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->orderByRaw("CASE priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END")
            ->value('priority');

        return response()->json([
            'unread_count' => $unreadCount,
            'highest_priority' => $highestPriority ?? 'normal',
        ]);
    })->name('frontend.notifications.unread-count');

    // Frontend notification routes
    // Specific routes must come first to avoid route conflicts
    Route::get('/notifications/list', [App\Http\Controllers\Admin\AdminNotificationController::class, 'getNotifications'])->name('frontend.notifications.list');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllAsRead'])->name('frontend.notifications.mark-all-read');
    Route::post('/notifications/mark-selected-read', [App\Http\Controllers\Admin\AdminNotificationController::class, 'markSelectedAsRead'])->name('frontend.notifications.mark-selected-read');
    Route::post('/notifications/archive-selected', [App\Http\Controllers\Admin\AdminNotificationController::class, 'archiveSelected'])->name('frontend.notifications.archive-selected');
    Route::post('/notifications/{notification}/mark-read', [App\Http\Controllers\Admin\AdminNotificationController::class, 'markAsRead'])->name('frontend.notifications.mark-read');
    Route::post('/notifications/{notification}/respond-interview', [App\Http\Controllers\Admin\AdminNotificationController::class, 'respondToInterview'])->name('frontend.notifications.respond-interview');

    // Nexa Skillmatching frontend-portaal (niet voor Nexa Taxi)
    Route::middleware(['skillmatching.portal'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/matches', [MatchController::class, 'index'])->name('matches');

        Route::get('/agenda', [App\Http\Controllers\Frontend\AgendaController::class, 'index'])->name('agenda');
        Route::get('/agenda/events', [App\Http\Controllers\Frontend\AgendaController::class, 'events'])->name('agenda.events');

        Route::get('/test-agenda', function () {
            return view('frontend.pages.agenda');
        });

        Route::get('/applications', [App\Http\Controllers\Frontend\ApplicationController::class, 'index'])->name('applications');
        Route::get('/applications/{id}', [App\Http\Controllers\Frontend\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{id}/status', [App\Http\Controllers\Frontend\ApplicationController::class, 'status'])->name('applications.status');

        Route::get('/settings', [App\Http\Controllers\Frontend\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/password', [App\Http\Controllers\Frontend\SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::post('/settings/email', [App\Http\Controllers\Frontend\SettingsController::class, 'updateEmail'])->name('settings.email');
        Route::post('/settings/job-preferences', [App\Http\Controllers\Frontend\SettingsController::class, 'updateJobPreferences'])->name('settings.job-preferences');
        Route::post('/settings/notifications', [App\Http\Controllers\Frontend\SettingsController::class, 'updateNotificationPreferences'])->name('settings.notifications');
        Route::post('/settings/privacy', [App\Http\Controllers\Frontend\SettingsController::class, 'updatePrivacyPreferences'])->name('settings.privacy');
        Route::post('/settings/export-data', [App\Http\Controllers\Frontend\SettingsController::class, 'exportData'])->name('settings.export-data');
        Route::delete('/settings/delete-account', [App\Http\Controllers\Frontend\SettingsController::class, 'deleteAccount'])->name('settings.delete-account');

        Route::post('/profile/cv', [App\Http\Controllers\Frontend\ProfileController::class, 'uploadCV'])->name('profile.cv');
        Route::delete('/profile/cv', [App\Http\Controllers\Frontend\ProfileController::class, 'removeCV'])->name('profile.cv.remove');
    });

});

// Nexa Taxi frontend-portaal (Mijn Taxi)
Route::middleware(['auth', 'taxi.portal', 'taxi.portal.password'])->group(function () {
    Route::get('/mijn-taxi', [TaxiPortalController::class, 'index'])->name('taxi.portal.dashboard');

    Route::prefix('mijn-taxi/api')->name('taxi.portal.api.')->group(function () {
        Route::post('ai-chat/message', [\App\Modules\NexaTaxi\Controllers\TaxiPortalAiChatController::class, 'sendMessage'])
            ->name('ai-chat.message');
        Route::get('dashboard', [TaxiPortalApiController::class, 'dashboard'])->name('dashboard');
        Route::get('rides', [TaxiPortalApiController::class, 'rides'])->name('rides');
        Route::get('rides/{ride}', [TaxiPortalApiController::class, 'showRide'])
            ->name('rides.show')
            ->whereNumber('ride');
        Route::get('invoices', [TaxiPortalApiController::class, 'invoices'])->name('invoices');
        Route::get('profile', [TaxiPortalApiController::class, 'profile'])->name('profile');
        Route::put('profile', [TaxiPortalApiController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [TaxiPortalApiController::class, 'updatePassword'])->name('profile.password');
        Route::get('invoices/{invoice}/pdf', [TaxiPortalApiController::class, 'downloadInvoicePdf'])
            ->name('invoices.pdf')
            ->whereNumber('invoice');
    });
});

// Language switching
Route::post('/language/switch', function () {
    $language = request()->input('language');

    if (in_array($language, ['nl', 'en'])) {
        session(['locale' => $language]);
        app()->setLocale($language);
    }

    return response()->json(['success' => true, 'language' => $language]);
})->name('language.switch');

// About/contact: website builder wanneer geconfigureerd, anders doorverwijzen naar home (legacy).
Route::get('/about', function () {
    if (app(WebsiteBuilderService::class)->getAboutPage()) {
        return app(WebsitePageController::class)->showAbout();
    }

    return redirect()->route('home');
})->name('about');

Route::get('/help', function () {
    return view('frontend.pages.help');
})->name('help');

Route::get('/contact', function () {
    if (app(WebsiteBuilderService::class)->getContactPage()) {
        return app(WebsitePageController::class)->showContact();
    }

    return redirect()->route('home');
})->name('contact');
Route::post('/contact', fn () => redirect()->route('home'))->name('contact.submit');

Route::get('/privacy', function () {
    return view('frontend.pages.privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('frontend.pages.terms');
})->name('terms');

// Nexa Taxi website booking (JSON; CSRF via meta op frontend-pagina's)
Route::prefix('nexa-taxi/booking')->group(function () {
    Route::get('address-search', [NexaTaxiBookingController::class, 'addressSearch'])->name('nexataxi.booking.address-search');
    Route::post('quote', [NexaTaxiBookingController::class, 'quote'])->name('nexataxi.booking.quote');
    Route::get('pending', [NexaTaxiBookingController::class, 'pending'])->name('nexataxi.booking.pending');
    Route::post('submit', [NexaTaxiBookingController::class, 'submit'])->name('nexataxi.booking.submit');
    Route::get('betaling/terug', [\App\Modules\NexaTaxi\Controllers\TaxiBookingPaymentController::class, 'returnPage'])
        ->name('nexataxi.booking.payment.return');
});

// Website-builder: custom/module pagina's op slug (moet na vaste paden staan)
Route::get('/{slug}', [WebsitePageController::class, 'showBySlug'])->name('website.page')->where('slug', '[a-z0-9\-]+');

// Fallback route for storage files (MUST be last)
Route::fallback(function () {
    $path = request()->path();

    if (strpos($path, 'storage/') === 0) {
        $filePath = str_replace('storage/', '', $path);
        $file = storage_path('app/public/'.$filePath);

        if (file_exists($file) && is_file($file)) {
            $mimeType = mime_content_type($file);
            $content = file_get_contents($file);

            return response($content, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }
    }

    abort(404);
});
Route::get('/test-agenda-public', function () {
    return view('frontend.pages.agenda');
});
