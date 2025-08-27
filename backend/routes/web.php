<?php

use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\Admin\AdminLayoutController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminPermissionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/



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
    
    // Layouts
    Route::resource('layouts', AdminLayoutController::class);
    
    // Roles & Permissions (Super Admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::resource('roles', AdminRoleController::class);
        Route::resource('permissions', AdminPermissionController::class);
        Route::post('permissions/{permission}/assign-to-role', [AdminPermissionController::class, 'assignToRole'])->name('permissions.assign-to-role');
        Route::get('permissions/bulk/create', [AdminPermissionController::class, 'bulkCreate'])->name('permissions.bulk-create');
        Route::post('permissions/bulk/store', [AdminPermissionController::class, 'bulkStore'])->name('permissions.bulk-store');
    });
});

// Redirect root to admin login
Route::get('/', function () {
    return redirect()->route('admin.login');
});
