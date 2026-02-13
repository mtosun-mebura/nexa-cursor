<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Models\Category;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    /**
     * Toon matches voor de ingelogde gebruiker
     */
    public function index(Request $request)
    {
        // Haal alle actieve vacatures op (Open en In behandeling)
        $query = Vacancy::with(['company', 'category'])
            ->whereIn('status', ['Open', 'In behandeling'])
            ->latest('publication_date');
        
        // Filtering opties
        if ($request->filled('category')) {
            $query->where('category_id', $request->get('category'));
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->get('location') . '%');
        }
        
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->get('employment_type'));
        }
        
        if ($request->filled('remote_work')) {
            $query->where('remote_work', $request->boolean('remote_work'));
        }
        
        // Haal vacatures op (beperk tot 12 voor de grid)
        $vacancies = $query->take(12)->get();
        
        // Als er minder dan 10 vacatures zijn, voeg wat extra toe
        if ($vacancies->count() < 10) {
            $additionalVacancies = Vacancy::with(['company', 'category'])
                ->whereNotIn('id', $vacancies->pluck('id'))
                ->take(10 - $vacancies->count())
                ->get();
            $vacancies = $vacancies->merge($additionalVacancies);
        }
        
        // Voeg match scores toe (simulatie - in een echte app zou dit gebaseerd zijn op user profiel)
        $vacancies = $vacancies->map(function ($vacancy) {
            $vacancy->match_score = rand(70, 95);
            return $vacancy;
        });
        
        // Categorieën voor filters
        $categories = Category::orderBy('name')->get();
        
        return view()->first(
            ['skillmatching::frontend.pages.matches', 'frontend.pages.matches'],
            compact('vacancies', 'categories')
        );
    }
    
    /**
     * Toon vacature matching demo pagina
     */
    public function demo()
    {
        // Haal alle actieve vacatures op voor de demo (Open en In behandeling)
        $vacancies = Vacancy::with(['company', 'category'])
            ->whereIn('status', ['Open', 'In behandeling'])
            ->latest('publication_date')
            ->take(6)
            ->get();
        
        // Als er minder dan 6 vacatures zijn, voeg wat extra toe
        if ($vacancies->count() < 6) {
            $additionalVacancies = Vacancy::with(['company', 'category'])
                ->whereNotIn('id', $vacancies->pluck('id'))
                ->take(6 - $vacancies->count())
                ->get();
            $vacancies = $vacancies->merge($additionalVacancies);
        }
        
        // Voeg match scores toe
        $vacancies = $vacancies->map(function ($vacancy) {
            $vacancy->match_score = rand(75, 94);
            return $vacancy;
        });
        
        return view('frontend.pages.vacature-matching', compact('vacancies'));
    }
}
