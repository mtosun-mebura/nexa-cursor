<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminVacancyController;
use App\Http\Controllers\Admin\AdminMatchController;
use App\Http\Controllers\Admin\AdminInterviewController;

/*
|--------------------------------------------------------------------------
| Skillmatching Module Routes
|--------------------------------------------------------------------------
|
| Routes voor de Skillmatching module (Vacatures, Matches, Interviews)
| Deze routes worden automatisch geregistreerd wanneer de module actief is
|
*/

// Test route om te zien of module actief is
Route::get('/test', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'Skillmatching module is actief!',
        'module' => 'skillmatching',
        'routes' => [
            'vacancies' => route('admin.skillmatching.vacancies.index'),
            'matches' => route('admin.skillmatching.matches.index'),
            'interviews' => route('admin.skillmatching.interviews.index'),
        ]
    ]);
})->name('test');

// Vacancies
Route::resource('vacancies', AdminVacancyController::class);

// Matches  
Route::resource('matches', AdminMatchController::class);

// Interviews
Route::resource('interviews', AdminInterviewController::class);
