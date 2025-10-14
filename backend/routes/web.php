<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVacancyController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminMatchController;
use App\Http\Controllers\Admin\AdminInterviewController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Admin\AdminCandidateController;

use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminPaymentProviderController;
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

// Publieke vacatures routes
Route::get('/vacatures', [PublicVacancyController::class, 'index'])->name('vacancies.index');
Route::get('/vacatures/{company:slug}/{vacancy}', [PublicVacancyController::class, 'show'])->name('vacatures.show');

// Frontend vacancy details
Route::get('/vacature/{company:slug}/{vacancy}', [PublicVacancyController::class, 'frontendShow'])->name('frontend.vacancy-details');

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
    
    // Users
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('users.assign-role');
    
    // Categories
    Route::resource('categories', AdminCategoryController::class);
    
    // Vacancies
    Route::resource('vacancies', AdminVacancyController::class);
    
    // Matches
    Route::resource('matches', AdminMatchController::class);
    
    // Interviews
    Route::resource('interviews', AdminInterviewController::class);
    
    // Agenda
    Route::get('agenda', [App\Http\Controllers\Admin\AgendaController::class, 'index'])->name('agenda.index');
    Route::get('agenda/events', [App\Http\Controllers\Admin\AgendaController::class, 'events'])->name('agenda.events');
    
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
    });
});

// Frontend home page
Route::get('/', function () {
    return view('frontend.pages.home');
})->name('home');




// Vacature matching demo page
Route::get('/vacature-matching', [MatchController::class, 'demo'])->name('vacature-matching');

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

// Job routes
Route::get('/jobs', [App\Http\Controllers\Frontend\JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [App\Http\Controllers\Frontend\JobController::class, 'show'])->name('jobs.show');

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
    
    
    Route::get('/applications', function () {
        return view('frontend.pages.applications');
    })->name('applications');
    
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

Route::get('/contact', function () {
    return view('frontend.pages.contact');
})->name('contact');

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
