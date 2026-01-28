<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Skillmatching\Controllers\Admin\VacancyController;
use App\Modules\Skillmatching\Controllers\Admin\MatchController;
use App\Modules\Skillmatching\Controllers\Admin\InterviewController;
use App\Modules\Skillmatching\Models\Vacancy;
use App\Modules\Skillmatching\Models\JobMatch;
use App\Modules\Skillmatching\Models\Interview;

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
Route::bind('vacancy', function ($value) {
    return Vacancy::findOrFail($value);
});
Route::resource('vacancies', VacancyController::class);
Route::get('vacancies/{vacancy}/contact-photo', [VacancyController::class, 'getContactPhoto'])->name('vacancies.contact-photo');
Route::get('vacancies/{vacancy}/candidate/{candidate}', [VacancyController::class, 'showCandidate'])->name('vacancies.candidate');
Route::get('vacancies/{vacancy}/candidate/{candidate}/timeline', [VacancyController::class, 'getTimeline'])->name('vacancies.candidate.timeline');
Route::post('vacancies/{vacancy}/candidate/{candidate}/interview', [VacancyController::class, 'scheduleInterview'])->name('vacancies.candidate.interview');
Route::put('vacancies/{vacancy}/candidate/{candidate}/interview/{interview}', [VacancyController::class, 'updateInterview'])->name('vacancies.candidate.interview.update');
Route::put('vacancies/{vacancy}/candidate/{candidate}/interview/{interview}/cancel', [VacancyController::class, 'cancelInterview'])->name('vacancies.candidate.interview.cancel');
Route::post('vacancies/{vacancy}/candidate/{candidate}/reject', [VacancyController::class, 'rejectCandidate'])->name('vacancies.candidate.reject');
Route::post('vacancies/{vacancy}/candidate/{candidate}/accept', [VacancyController::class, 'acceptCandidate'])->name('vacancies.candidate.accept');

// Matches
Route::bind('match', function ($value) {
    return JobMatch::findOrFail($value);
});
Route::resource('matches', MatchController::class);
Route::get('matches/vacancy/{vacancy}/candidates', [MatchController::class, 'candidates'])->name('matches.candidates');

// Interviews
Route::bind('interview', function ($value) {
    return Interview::findOrFail($value);
});
Route::resource('interviews', InterviewController::class);
