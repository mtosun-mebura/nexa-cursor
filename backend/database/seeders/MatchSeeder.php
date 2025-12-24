<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobMatch;
use App\Models\Candidate;
use App\Models\Vacancy;
use App\Models\Company;
use Carbon\Carbon;

class MatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Verwijderen van alle bestaande matches...');
        
        // Verwijder alle matches
        JobMatch::truncate();
        $this->command->info('Alle matches verwijderd.');

        // Haal alle kandidaten op uit de candidates tabel
        $candidates = Candidate::all();

        // Zorg dat alle kandidaten een geboortedatum hebben
        $this->command->info('Controleren en bijwerken van geboortedatums voor kandidaten...');
        $ageRanges = [
            [18, 25], [26, 30], [31, 35], [36, 40], [41, 45], [46, 50], [51, 55]
        ];
        
        foreach ($candidates as $candidate) {
            if (!$candidate->date_of_birth) {
                $ageRange = $ageRanges[array_rand($ageRanges)];
                $age = rand($ageRange[0], $ageRange[1]);
                $candidate->date_of_birth = Carbon::now()->subYears($age)->subDays(rand(0, 365));
                $candidate->save();
            }
        }
        $this->command->info('Geboortedatums bijgewerkt voor ' . $candidates->count() . ' kandidaten.');

        // Haal alle vacatures op
        $vacancies = Vacancy::all();

        if ($candidates->isEmpty()) {
            $this->command->error('Geen kandidaten gevonden! Maak eerst kandidaten aan.');
            return;
        }

        if ($vacancies->isEmpty()) {
            $this->command->error('Geen vacatures gevonden! Maak eerst vacatures aan.');
            return;
        }

        $this->command->info('Aanmaken van 20 nieuwe matches...');

        $matchStatuses = ['pending', 'accepted', 'rejected', 'interview'];
        $matchScores = [
            [85, 100], // Hoge scores
            [70, 84],  // Goede scores
            [60, 69],  // Matige scores
            [40, 59],  // Lage scores
        ];

        $created = 0;
        $maxMatches = 20;

        // Zorg voor variatie in matches
        while ($created < $maxMatches) {
            $candidate = $candidates->random();
            $vacancy = $vacancies->random();

            // Controleer of deze combinatie al bestaat
            $existingMatch = JobMatch::where('candidate_id', $candidate->id)
                ->where('vacancy_id', $vacancy->id)
                ->first();

            if ($existingMatch) {
                continue; // Skip als match al bestaat
            }

            // Kies een willekeurige score range
            $scoreRange = $matchScores[array_rand($matchScores)];
            $matchScore = rand($scoreRange[0] * 100, $scoreRange[1] * 100) / 100;

            // Kies een willekeurige status
            $status = $matchStatuses[array_rand($matchStatuses)];

            // Genereer AI recommendation
            $aiRecommendation = $this->generateAIRecommendation($matchScore);

            // Genereer AI analysis
            $aiAnalysis = $this->generateAIAnalysis($candidate, $vacancy, $matchScore);

            // Maak de match aan
            JobMatch::create([
                'candidate_id' => $candidate->id,
                'vacancy_id' => $vacancy->id,
                'match_score' => $matchScore,
                'status' => $status,
                'ai_recommendation' => $aiRecommendation,
                'ai_analysis' => $aiAnalysis,
                'application_date' => Carbon::now()->subDays(rand(1, 60)),
                'notes' => "Match gegenereerd voor {$candidate->first_name} {$candidate->last_name} op {$vacancy->title}",
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 10)),
            ]);

            $created++;
            $this->command->info("✓ Match {$created}/{$maxMatches} aangemaakt: {$candidate->first_name} {$candidate->last_name} -> {$vacancy->title} (Score: {$matchScore}%, Status: {$status})");
        }

        $this->command->info("✓ {$created} matches succesvol aangemaakt!");
    }

    private function generateAIRecommendation($matchScore)
    {
        if ($matchScore >= 85) {
            return 'strong_match';
        } elseif ($matchScore >= 70) {
            return 'good_match';
        } elseif ($matchScore >= 60) {
            return 'moderate_match';
        } else {
            return 'weak_match';
        }
    }

    private function generateAIAnalysis($candidate, $vacancy, $matchScore)
    {
        $analyses = [
            "De kandidaat heeft relevante ervaring en vaardigheden die goed aansluiten bij de vacature. De match score van {$matchScore}% geeft aan dat er een sterke overeenkomst is tussen het profiel van de kandidaat en de vereisten van de functie.",
            "Er is een goede basis voor een match. De kandidaat beschikt over de belangrijkste kwalificaties en heeft ervaring in een vergelijkbare rol. Aanbevolen om verder te verkennen.",
            "De match score van {$matchScore}% suggereert dat er enkele overeenkomsten zijn, maar er zijn ook gebieden waar de kandidaat mogelijk extra ontwikkeling nodig heeft.",
            "Hoewel de match score lager is, kan de kandidaat potentieel hebben met de juiste begeleiding en ontwikkeling. Overweeg een gesprek om te beoordelen of er een goede culturele fit is.",
        ];

        if ($matchScore >= 85) {
            return $analyses[0];
        } elseif ($matchScore >= 70) {
            return $analyses[1];
        } elseif ($matchScore >= 60) {
            return $analyses[2];
        } else {
            return $analyses[3];
        }
    }
}

