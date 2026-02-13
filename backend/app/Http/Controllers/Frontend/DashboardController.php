<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\JobMatch;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get recent matches (similar to MatchController but for dashboard)
        $vacancies = Vacancy::with(['company', 'category'])
            ->whereIn('status', ['Open', 'In behandeling'])
            ->active()
            ->latest()
            ->limit(6)
            ->get();

        // Simulate match scores for demo purposes
        $vacancies->each(function ($vacancy) {
            $vacancy->match_score = rand(60, 95);
        });

        // Calculate statistics
        $stats = [
            'total_matches' => $vacancies->count(),
            'active_applications' => rand(5, 12), // Simulated for demo
            'interviews' => rand(1, 5), // Simulated for demo
            'profile_complete' => rand(70, 100), // Simulated for demo
        ];

        return view()->first(
            ['skillmatching::frontend.pages.dashboard', 'frontend.pages.dashboard'],
            compact('vacancies', 'stats')
        );
    }
}
