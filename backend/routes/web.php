<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVacancyController;
use App\Http\Controllers\Admin\AdminBranchController;
use App\Http\Controllers\Admin\AdminMatchController;
use App\Http\Controllers\Admin\AdminInterviewController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminCandidateController;

use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminPaymentProviderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\PublicVacancyController;
use App\Http\Controllers\Frontend\MatchController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/



// Debug route for upload limits (publiek)
Route::get('/debug-upload-limits', function() {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_execution_time' => ini_get('max_execution_time'),
        'max_input_time' => ini_get('max_input_time'),
        'memory_limit' => ini_get('memory_limit'),
        'max_file_uploads' => ini_get('max_file_uploads')
    ]);
});

// Direct file serving route (before any middleware)
Route::get('/file/{path}', function ($path) {
    $filePath = str_replace('--', '/', $path);
    $file = storage_path('app/public/' . $filePath);
    
    if (!file_exists($file) || !is_file($file)) {
        abort(404);
    }
    
    $mimeType = mime_content_type($file);
    $content = file_get_contents($file);
    
    return response($content, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');

// BLOB photo serving route (authenticated users only)
Route::get('/user-photo/{id}', function ($id) {
    // Check if user is authenticated
    if (!Auth::check()) {
        abort(404);
    }
    
    $user = \App\Models\User::find($id);
    
    if (!$user || !$user->photo_blob) {
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
    if (!$user) {
        abort(404);
    }
    
    $expectedHash = hash('sha256', $userId . $user->updated_at . config('app.key'));
    if (!hash_equals($expectedHash, $hash)) {
        abort(404);
    }
    
    if (!$user->photo_blob) {
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
    $expectedHash = hash('sha256', $userId . $companyId . config('app.key'));
    if (!hash_equals($expectedHash, $hash)) {
        abort(404);
    }
    
    $user = \App\Models\User::find($userId);
    
    if (!$user || !$user->photo_blob) {
        abort(404);
    }
    
    // Verify company exists and is active
    $company = \App\Models\Company::find($companyId);
    if (!$company || !$company->is_active) {
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
    if (!Auth::check()) {
        abort(404);
    }
    
    $company = \App\Models\Company::find($companyId);
    
    if (!$company || !$company->logo_blob) {
        abort(404);
    }
    
    // Check if user has permission to view companies
    if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-companies')) {
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

// Publieke vacatures routes - redirect naar /jobs
Route::get('/vacatures', function() {
    return redirect()->route('jobs.index');
})->name('vacatures.index');
Route::get('/vacatures/{company:slug}/{vacancy}', [PublicVacancyController::class, 'show'])->name('vacatures.show');

// Frontend vacancy details
Route::get('/vacature/{company:slug}/{vacancy}', [PublicVacancyController::class, 'frontendShow'])->name('frontend.vacancy-details');

// Frontend job routes (publiek inzien)
Route::get('/jobs', [App\Http\Controllers\Frontend\JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [App\Http\Controllers\Frontend\JobController::class, 'show'])->name('jobs.show');

// Admin Authentication Routes (without admin middleware)
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Protected Routes
Route::middleware(['web', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/tenant/switch', [AdminDashboardController::class, 'switchTenant'])->name('tenant.switch');
    
    // Companies
    Route::resource('companies', AdminCompanyController::class);
    Route::post('companies/{company}/toggle-status', [AdminCompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
    Route::post('companies/{company}/toggle-main-location', [AdminCompanyController::class, 'toggleMainLocation'])->name('companies.toggle-main-location');
    Route::post('companies/{company}/upload-logo', [AdminCompanyController::class, 'uploadLogo'])->name('companies.upload-logo');
    
    // Company Locations
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
    Route::get('users/{user}/photo', [AdminUserController::class, 'photo'])->name('users.photo');
    
    // Branches (voorheen Categories)
    Route::resource('branches', AdminBranchController::class);
    Route::get('branches/{branch}/data', [AdminBranchController::class, 'getData'])->name('branches.data');
    
    // Vacancies
    Route::resource('vacancies', AdminVacancyController::class);
    
    // Matches
    Route::resource('matches', AdminMatchController::class);
    
    // Interviews
    Route::resource('interviews', AdminInterviewController::class);
    
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
    Route::resource('notifications', AdminNotificationController::class);
    Route::post('notifications/{notification}/mark-read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    
    // Email Templates
    Route::resource('email-templates', AdminEmailTemplateController::class);
    
    // Candidates (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('candidates', AdminCandidateController::class);
        Route::post('candidates/{candidate}/toggle-status', [AdminCandidateController::class, 'toggleStatus'])->name('candidates.toggle-status');
        Route::get('candidates/{candidate}/download-cv', [AdminCandidateController::class, 'downloadCV'])->name('candidates.download-cv');
        Route::get('candidates/{candidate}/photo', [AdminCandidateController::class, 'getCandidatePhoto'])->name('candidates.photo');
    });
    

    
    // Roles & Permissions (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('roles', AdminRoleController::class);
        Route::resource('permissions', AdminPermissionController::class);
        Route::post('permissions/{permission}/assign-to-role', [AdminPermissionController::class, 'assignToRole'])->name('permissions.assign-to-role');
        Route::get('permissions/bulk/create', [AdminPermissionController::class, 'bulkCreate'])->name('permissions.bulk-create');
        Route::post('permissions/bulk/store', [AdminPermissionController::class, 'bulkStore'])->name('permissions.bulk-store');
        
        // Payment Providers (Super Admin only)
        Route::resource('payment-providers', AdminPaymentProviderController::class);
        Route::post('payment-providers/{paymentProvider}/toggle-status', [AdminPaymentProviderController::class, 'toggleStatus'])->name('payment-providers.toggle-status');
        Route::post('payment-providers/{paymentProvider}/test-connection', [AdminPaymentProviderController::class, 'testConnection'])->name('payment-providers.test-connection');
        
        // Payments (Super Admin only)
        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/openstaand', [AdminPaymentController::class, 'openstaand'])->name('payments.openstaand');
        Route::get('payments/voldaan', [AdminPaymentController::class, 'voldaan'])->name('payments.voldaan');
        
        // Invoices (Super Admin only)
        Route::resource('invoices', AdminInvoiceController::class);
        Route::post('invoices/{invoice}/send-reminder', [AdminInvoiceController::class, 'sendReminder'])->name('invoices.send-reminder');
        Route::get('invoices/{invoice}/payment-links', [AdminInvoiceController::class, 'paymentLinks'])->name('invoices.payment-links');
        Route::get('invoices/settings', [AdminInvoiceController::class, 'settings'])->name('invoices.settings');
        Route::post('invoices/settings', [AdminInvoiceController::class, 'updateSettings'])->name('invoices.settings.update');
        
        // Settings (Super Admin only)
        Route::get('settings', [App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/mail', [App\Http\Controllers\Admin\AdminSettingsController::class, 'updateMail'])->name('settings.mail.update');
        Route::post('settings/mail/test', [App\Http\Controllers\Admin\AdminSettingsController::class, 'testEmail'])->name('settings.mail.test');
        
        // Postcode lookup (for address autocomplete)
        Route::post('postcode/lookup', [App\Http\Controllers\PostcodeController::class, 'lookup'])->name('postcode.lookup');
    });
});

// Frontend home page
Route::get('/', [App\Http\Controllers\Frontend\HomeController::class, 'index'])->name('home');




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
    Route::get('/debug-upload-limits', function() {
        return response()->json([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'memory_limit' => ini_get('memory_limit')
        ]);
    });
    Route::post('/profile/skills', [ProfileController::class, 'addSkill'])->name('profile.skills.add');
    Route::delete('/profile/skills/{skill}', [ProfileController::class, 'removeSkill'])->name('profile.skills.remove');
    Route::post('/profile/experiences', [ProfileController::class, 'addExperience'])->name('profile.experiences.add');
    Route::get('/profile/experiences/{experience}', [ProfileController::class, 'showExperience'])->name('profile.experiences.show');
    Route::put('/profile/experiences/{experience}', [ProfileController::class, 'updateExperience'])->name('profile.experiences.update');
    Route::delete('/profile/experiences/{experience}', [ProfileController::class, 'removeExperience'])->name('profile.experiences.remove');
});


// Auth routes
Route::get('/login', function () {
    return view('frontend.pages.login');
})->name('login');

Route::post('/login', function () {
    $credentials = request()->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    
    if (Auth::guard('web')->attempt($credentials)) {
        request()->session()->regenerate();
        return redirect()->route('dashboard');
    }
    
    return redirect()->back()->withErrors(['email' => 'Ongeldige inloggegevens']);
})->name('login.post');

Route::get('/register', function () {
    return view('frontend.pages.register');
})->name('register');

Route::post('/register', function () {
    $validated = request()->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);
    
    $user = \App\Models\User::create([
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'email_verified_at' => now(),
    ]);
    
    Auth::guard('web')->login($user);
    
    return redirect()->route('dashboard');
})->name('register.post');
Route::post('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::get('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout.get');

// Test route voor 502 error pagina (alleen in development)
if (app()->environment('local', 'development')) {
    Route::get('/test-502', function() {
        return response()->view('errors.502', [], 502);
    });
}

// User dashboard routes
Route::middleware(['auth:web'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/matches', [MatchController::class, 'index'])->name('matches');
    
    Route::get('/agenda', [App\Http\Controllers\Frontend\AgendaController::class, 'index'])->name('agenda');
    Route::get('/agenda/events', [App\Http\Controllers\Frontend\AgendaController::class, 'events'])->name('agenda.events');
    
    // Test route for agenda
    Route::get('/test-agenda', function() {
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
    
    // CV routes
    Route::post('/profile/cv', [App\Http\Controllers\Frontend\ProfileController::class, 'uploadCV'])->name('profile.cv');
    Route::delete('/profile/cv', [App\Http\Controllers\Frontend\ProfileController::class, 'removeCV'])->name('profile.cv.remove');
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

// Static pages
Route::get('/about', function () {
    return view('frontend.pages.about');
})->name('about');

Route::get('/help', function () {
    return view('frontend.pages.help');
})->name('help');

Route::get('/contact', [App\Http\Controllers\Frontend\ContactController::class, 'index'])->name('contact');
Route::post('/contact', [App\Http\Controllers\Frontend\ContactController::class, 'submit'])->name('contact.submit');

Route::get('/privacy', function () {
    return view('frontend.pages.privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('frontend.pages.terms');
})->name('terms');

// Fallback route for storage files (MUST be last)
Route::fallback(function () {
    $path = request()->path();
    
    if (strpos($path, 'storage/') === 0) {
        $filePath = str_replace('storage/', '', $path);
        $file = storage_path('app/public/' . $filePath);
        
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
Route::get('/test-agenda-public', function() { return view('frontend.pages.agenda'); });
