<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobMatch;
use App\Models\Interview;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\Company;
use Carbon\Carbon;

class InterviewMatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Haal bedrijven op
        $tosunCompany = Company::where('name', 'Tosun')->first();
        $maliCompany = Company::where('name', 'Mali bedrijf')->first();

        if (!$tosunCompany || !$maliCompany) {
            $this->command->error('Bedrijven niet gevonden!');
            return;
        }

        // Haal vacatures op
        $tosunVacancies = Vacancy::where('company_id', $tosunCompany->id)->get();
        $maliVacancies = Vacancy::where('company_id', $maliCompany->id)->get();

        // Haal kandidaten op
        $tosunCandidates = User::where('company_id', $tosunCompany->id)
            ->whereHas('roles', function($q) {
                $q->where('name', 'candidate');
            })->get();

        $maliCandidates = User::where('company_id', $maliCompany->id)
            ->whereHas('roles', function($q) {
                $q->where('name', 'candidate');
            })->get();

        $this->command->info('Aanmaken van matches en interviews...');
        $this->command->info("Tosun vacatures: " . $tosunVacancies->count());
        $this->command->info("Mali vacatures: " . $maliVacancies->count());
        $this->command->info("Tosun kandidaten: " . $tosunCandidates->count());
        $this->command->info("Mali kandidaten: " . $maliCandidates->count());

        // Maak matches en interviews voor Tosun
        $this->createMatchesAndInterviews($tosunCompany, $tosunVacancies, $tosunCandidates, 'Tosun');

        // Maak matches en interviews voor Mali bedrijf
        $this->createMatchesAndInterviews($maliCompany, $maliVacancies, $maliCandidates, 'Mali bedrijf');

        $this->command->info('Matches en interviews succesvol aangemaakt!');
    }

    private function createMatchesAndInterviews($company, $vacancies, $candidates, $companyName)
    {
        $interviewTypes = ['phone', 'video', 'in-person'];
        $interviewStatuses = ['scheduled', 'completed', 'cancelled'];
        $matchStatuses = ['pending', 'accepted', 'rejected'];
        
        $interviewerNames = [
            'Jan de Vries', 'Maria Rodriguez', 'Ahmed Hassan', 'Sarah Johnson', 
            'Mohammed Ali', 'Emma van der Berg', 'David Chen', 'Fatima Al-Zahra'
        ];

        $createdCount = 0;
        $maxInterviews = 5; // Max 5 interviews per bedrijf

        foreach ($vacancies->take(3) as $vacancy) {
            foreach ($candidates->take(2) as $candidate) {
                if ($createdCount >= $maxInterviews) break;

                // Maak een match aan
                $match = JobMatch::create([
                    'user_id' => $candidate->id,
                    'vacancy_id' => $vacancy->id,
                    'match_score' => rand(70, 95) + (rand(0, 99) / 100), // 70.00 - 95.99
                    'status' => $matchStatuses[array_rand($matchStatuses)],
                    'ai_recommendation' => $this->generateAIRecommendation($candidate, $vacancy),
                    'ai_analysis' => $this->generateAIAnalysis($candidate, $vacancy),
                    'application_date' => Carbon::now()->subDays(rand(1, 30)),
                    'notes' => "Match voor {$candidate->first_name}"
                ]);

                // Maak een interview aan
                $scheduledDate = Carbon::now()->addDays(rand(1, 14));
                $interview = Interview::create([
                    'match_id' => $match->id,
                    'company_id' => $company->id,
                    'type' => $interviewTypes[array_rand($interviewTypes)],
                    'scheduled_at' => $scheduledDate,
                    'duration' => rand(30, 120), // 30-120 minuten
                    'status' => $interviewStatuses[array_rand($interviewStatuses)],
                    'location' => $this->generateLocation($interviewTypes[array_rand($interviewTypes)]),
                    'interviewer_name' => $interviewerNames[array_rand($interviewerNames)],
                    'interviewer_email' => strtolower(str_replace(' ', '.', $interviewerNames[array_rand($interviewerNames)])) . '@' . strtolower(str_replace(' ', '', $companyName)) . '.nl',
                    'notes' => $this->generateInterviewNotes($candidate, $vacancy),
                    'feedback' => $this->generateFeedback()
                ]);

                $createdCount++;
                
                $this->command->info("✓ Match en interview aangemaakt: {$candidate->first_name} {$candidate->last_name} -> {$vacancy->title} ({$companyName})");
            }
            if ($createdCount >= $maxInterviews) break;
        }
    }

    private function generateAIRecommendation($candidate, $vacancy)
    {
        $recommendations = [
            "Sterke vaardigheden.",
            "Goede culturele fit.",
            "Aanbevolen voor interview.",
            "Goede balans.",
            "Ideale kandidaat."
        ];
        
        return $recommendations[array_rand($recommendations)];
    }

    private function generateAIAnalysis($candidate, $vacancy)
    {
        return "Goede match.";
    }

    private function generateLocation($type)
    {
        switch ($type) {
            case 'phone':
                return 'Telefonisch';
            case 'video':
                return 'Microsoft Teams / Zoom';
            case 'in-person':
                return 'Kantoor Amsterdam';
            default:
                return 'Nader te bepalen';
        }
    }

    private function generateInterviewNotes($candidate, $vacancy)
    {
        $notesOptions = [
            "Interview gepland voor {$candidate->first_name} {$candidate->last_name} voor de functie {$vacancy->title}. Het gesprek zal zich richten op technische vaardigheden, ervaring met relevante projecten en culturele fit. De kandidaat heeft een sterke achtergrond in de gevraagde technologieën en toont veel enthousiasme voor de rol. Voorbereidingen zijn getroffen voor een grondige evaluatie van zowel hard als soft skills.",
            
            "Gesprek ingepland met {$candidate->first_name} {$candidate->last_name} voor de positie {$vacancy->title}. De kandidaat heeft indrukwekkende referenties en relevante werkervaring. Het interview zal bestaan uit een technische assessment, gedragsgerichte vragen en een discussie over carrièredoelen. De verwachtingen zijn hoog vanwege de sterke match score en uitgebreide portfolio.",
            
            "Interview georganiseerd voor {$candidate->first_name} {$candidate->last_name} voor {$vacancy->title}. De kandidaat toont uitstekende communicatieve vaardigheden en heeft bewezen resultaten in vergelijkbare rollen. Het gesprek zal zich concentreren op leiderschapskwaliteiten, probleemoplossend vermogen en teamwerk. Alle benodigde documenten zijn verzameld en de agenda is voorbereid.",
            
            "Persoonlijk gesprek gepland met {$candidate->first_name} {$candidate->last_name} voor de functie {$vacancy->title}. De kandidaat heeft een unieke combinatie van technische expertise en business inzicht. Het interview zal bestaan uit een case study, technische vragen en een culturele fit assessment. De voorbereidingen zijn compleet en alle stakeholders zijn geïnformeerd.",
            
            "Interview ingepland voor {$candidate->first_name} {$candidate->last_name} voor de rol {$vacancy->title}. De kandidaat heeft sterke referenties en relevante certificeringen. Het gesprek zal zich richten op praktische ervaring, leiderschapsstijl en toekomstplannen. Alle logistieke details zijn geregeld en de evaluatiecriteria zijn vastgesteld."
        ];
        
        return $notesOptions[array_rand($notesOptions)];
    }

    private function generateFeedback()
    {
        $feedbackOptions = [
            "Positief interview, kandidaat toont goede vaardigheden",
            "Uitstekende communicatie en technische kennis",
            "Goede culturele fit, aanbevolen voor volgende ronde",
            "Kandidaat heeft potentie maar mist wat ervaring",
            "Zeer professioneel, sterke motivatie voor de rol"
        ];
        
        return $feedbackOptions[array_rand($feedbackOptions)];
    }
}