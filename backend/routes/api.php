<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VacancyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\MatchController;
// Controllers will be created as needed

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Matching routes (for n8n integration)
// Note: Consider adding API key authentication for production
Route::post('/matches', [MatchController::class, 'getMatches']);
Route::get('/matches/rule-based/{candidateId}', [MatchController::class, 'getRuleBasedMatches']);
Route::get('/matches/semantic/{candidateId}', [MatchController::class, 'getSemanticMatches']);
Route::get('/matches/hybrid/{candidateId}', [MatchController::class, 'getHybridMatches']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'me']);
    Route::post('/auth/2fa/verify', [AuthController::class, 'verifyTwoFactor']);
    
    // Vacancies
    Route::apiResource('vacancies', VacancyController::class);
    
    // Admin routes (Super Admin only)
    Route::middleware(['role:super-admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/companies/{company}', [CompanyController::class, 'show']);
        Route::put('/companies/{company}', [CompanyController::class, 'update']);
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy']);
        
        // Users management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
        
        // Additional admin routes will be added as controllers are created
    });
    
    // Tenant-specific routes (Company Admin and Staff)
    Route::middleware(['role:company-admin|staff'])->prefix('tenant')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'tenantDashboard']);
        Route::get('/vacancies', [VacancyController::class, 'tenantIndex']);
        Route::post('/vacancies', [VacancyController::class, 'store']);
        Route::get('/vacancies/{vacancy}', [VacancyController::class, 'show']);
        Route::put('/vacancies/{vacancy}', [VacancyController::class, 'update']);
        Route::delete('/vacancies/{vacancy}', [VacancyController::class, 'destroy']);
        
        // Additional tenant routes will be added as controllers are created
    });
});


