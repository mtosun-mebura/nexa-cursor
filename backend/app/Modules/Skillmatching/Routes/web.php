<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Skillmatching\Controllers\Admin\VacancyController;
use App\Modules\Skillmatching\Controllers\Admin\MatchController;
use App\Modules\Skillmatching\Controllers\Admin\InterviewController;
use App\Modules\Skillmatching\Models\Vacancy;
use App\Modules\Skillmatching\Models\JobMatch;
use App\Modules\Skillmatching\Models\Interview;
use App\Http\Controllers\Admin\AdminBranchController;
use App\Http\Controllers\Admin\AdminBranchFunctionController;
use App\Http\Controllers\Admin\AdminBranchFunctionSkillController;
use App\Models\Branch;

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

// Branches - gebruik slug voor route binding
Route::bind('branch', function ($value) {
    return Branch::where('slug', $value)->firstOrFail();
});
Route::resource('branches', AdminBranchController::class)->parameters(['branches' => 'branch:slug']);
Route::post('branches/{branch:slug}/toggle-status', [AdminBranchController::class, 'toggleStatus'])->name('branches.toggle-status');
Route::get('branches/{branch:slug}/data', [AdminBranchController::class, 'getData'])->name('branches.data');
Route::get('branches/functions/all', [AdminBranchController::class, 'getAllFunctions'])->name('branches.functions.all');
Route::post('branches/{branch:slug}/functions', [AdminBranchFunctionController::class, 'store'])->name('branches.functions.store');
Route::put('branches/{branch:slug}/functions/{function}', [AdminBranchFunctionController::class, 'update'])->name('branches.functions.update');
Route::delete('branches/{branch:slug}/functions/{function}', [AdminBranchFunctionController::class, 'destroy'])->name('branches.functions.destroy');
Route::get('branches/{branch:slug}/functions/{function}/skills', [AdminBranchFunctionSkillController::class, 'index'])->name('branches.functions.skills.index');
Route::post('branches/{branch:slug}/functions/{function}/skills', [AdminBranchFunctionSkillController::class, 'store'])->name('branches.functions.skills.store');
Route::delete('branches/{branch:slug}/functions/{function}/skills/{skill}', [AdminBranchFunctionSkillController::class, 'destroy'])->name('branches.functions.skills.destroy');
