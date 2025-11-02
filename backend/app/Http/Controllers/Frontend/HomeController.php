<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Rotatie mechanisme: nieuwe set vacatures elke 2 uur
        // Gebruik de dag van het jaar en het uur om een consistente maar roterende selectie te maken
        $rotationKey = floor(now()->timestamp / (2 * 3600)); // Elke 2 uur een nieuwe key
        
        $jobs = Cache::remember("home_jobs_rotation_{$rotationKey}", 7200, function () use ($rotationKey) {
            // Haal alle beschikbare vacatures op
            $allJobs = Vacancy::with(['company', 'category'])
                ->where('is_active', true)
                ->where(function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('published_at', '<=', now())
                             ->orWhereNull('published_at')
                             ->orWhereNull('publication_date');
                    });
                })
                ->orderBy('published_at', 'desc')
                ->get();
            
            // Als er 6 of minder vacatures zijn, geef ze allemaal terug
            if ($allJobs->count() <= 6) {
                return $allJobs;
            }
            
            // Gebruik de rotation key om een verschillende start positie te bepalen
            $startIndex = ($rotationKey % $allJobs->count());
            
            // Neem 6 vacatures, beginnend vanaf de rotatie positie
            // Als we aan het einde van de lijst komen, wrap around naar het begin
            $selectedJobs = collect();
            for ($i = 0; $i < 6; $i++) {
                $index = ($startIndex + $i) % $allJobs->count();
                $selectedJobs->push($allJobs[$index]);
            }
            
            return $selectedJobs;
        });
        
        return view('frontend.pages.home', compact('jobs'));
    }
}

