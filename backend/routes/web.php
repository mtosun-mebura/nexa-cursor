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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Publieke vacatures routes
Route::get('/vacatures', [PublicVacancyController::class, 'index'])->name('vacancies.index');
Route::get('/vacatures/{company:slug}/{vacancy}', [PublicVacancyController::class, 'show'])->name('vacancies.show');

// Admin Authentication Routes
Route::middleware(['web'])->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('admin.login.post');
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Admin Protected Routes
Route::middleware(['web', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/tenant/switch', [AdminDashboardController::class, 'switchTenant'])->name('tenant.switch');
    
    // Companies
    Route::resource('companies', AdminCompanyController::class);
    
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
Route::get('/vacature-matching', function () {
    return view('frontend.pages.vacature-matching');
})->name('vacature-matching');

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

// Job routes
Route::get('/jobs', [App\Http\Controllers\Frontend\JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [App\Http\Controllers\Frontend\JobController::class, 'show'])->name('jobs.show');


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
    Route::get('/dashboard', function () {
        return view('frontend.pages.dashboard');
    })->name('dashboard');
    
    Route::get('/matches', function () {
        return view('frontend.pages.matches');
    })->name('matches');
    
    Route::get('/profile', function () {
        return view('frontend.pages.profile');
    })->name('profile');
    
    Route::get('/applications', function () {
        return view('frontend.pages.applications');
    })->name('applications');
    
    Route::get('/settings', function () {
        return view('frontend.pages.settings');
    })->name('settings');
});

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
