<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobMatch;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;

class AddDummyMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:add-dummy {count=5 : Number of dummy matches to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add dummy matches for testing pagination';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        
        // Haal kandidaten op (users met candidate role, of anders gewoon alle users)
        $candidates = User::whereHas('roles', function($q) {
            $q->where('name', 'candidate');
        })->get();
        
        // Als er geen candidates zijn, gebruik dan alle users
        if ($candidates->isEmpty()) {
            $candidates = User::all();
            $this->warn('Geen kandidaten met candidate role gevonden, gebruik alle users.');
        }
        
        if ($candidates->isEmpty()) {
            $this->error('Geen users gevonden! Maak eerst users aan.');
            return 1;
        }
        
        // Haal vacatures op
        $vacancies = Vacancy::all();
        
        if ($vacancies->isEmpty()) {
            $this->error('Geen vacatures gevonden! Maak eerst vacatures aan.');
            return 1;
        }
        
        $matchStatuses = ['pending', 'accepted', 'rejected', 'interviewed'];
        $aiRecommendations = ['strong_match', 'good_match', 'moderate_match', 'weak_match'];
        
        $created = 0;
        $attempts = 0;
        $maxAttempts = $count * 10; // Max aantal pogingen om duplicaten te voorkomen
        
        $this->info("Aanmaken van {$count} dummy matches...");
        
        while ($created < $count && $attempts < $maxAttempts) {
            $candidate = $candidates->random();
            $vacancy = $vacancies->random();
            
            // Controleer of deze combinatie al bestaat
            $existingMatch = JobMatch::where('candidate_id', $candidate->id)
                ->where('vacancy_id', $vacancy->id)
                ->first();
            
            if ($existingMatch) {
                $attempts++;
                continue;
            }
            
            // Maak de match aan
            $match = JobMatch::create([
                'user_id' => $candidate->id,
                'vacancy_id' => $vacancy->id,
                'match_score' => round(rand(50, 100) + (rand(0, 99) / 100), 2), // 50.00 - 100.99
                'status' => $matchStatuses[array_rand($matchStatuses)],
                'ai_recommendation' => $aiRecommendations[array_rand($aiRecommendations)],
                'ai_analysis' => $this->generateAIAnalysis($candidate, $vacancy),
                'application_date' => Carbon::now()->subDays(rand(1, 60)),
                'notes' => "Automatische match voor {$candidate->first_name} {$candidate->last_name} op vacature: {$vacancy->title}",
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                'updated_at' => Carbon::now()->subDays(rand(0, 30)),
            ]);
            
            $created++;
            $attempts++;
            
            $this->info("✓ Match {$created}/{$count} aangemaakt: {$candidate->first_name} {$candidate->last_name} -> {$vacancy->title} (Score: {$match->match_score}%)");
        }
        
        if ($created < $count) {
            $this->warn("Alleen {$created} van de {$count} matches konden worden aangemaakt (mogelijk te weinig unieke combinaties).");
        } else {
            $this->info("✓ Succesvol {$created} dummy matches aangemaakt!");
        }
        
        return 0;
    }
    
    private function generateAIAnalysis($candidate, $vacancy)
    {
        $analyses = [
            "Kandidaat heeft relevante ervaring voor deze functie.",
            "Goede match tussen vaardigheden en vereisten.",
            "Kandidaat toont interesse in het bedrijf en de functie.",
            "Sterke culturele fit met het team.",
            "Kandidaat heeft bewezen track record in vergelijkbare rollen.",
        ];
        
        return $analyses[array_rand($analyses)];
    }
}
