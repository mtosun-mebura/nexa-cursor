<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Interview;
use App\Models\CVFile;
use App\Models\JobMatch;
use Carbon\Carbon;

class AddCVToInterviewUser extends Command
{
    protected $signature = 'app:add-cv-to-interview-user {interview_id}';
    protected $description = 'Voeg CV toe aan de user van een specifiek interview';

    public function handle()
    {
        $interviewId = (int) $this->argument('interview_id');
        
        $interview = Interview::with('match.candidate')->find($interviewId);
        
        if (!$interview) {
            $this->error("Interview met ID {$interviewId} niet gevonden!");
            return 1;
        }
        
        if (!$interview->match) {
            $this->error("Interview heeft geen match!");
            return 1;
        }
        
        $candidate = $interview->match->candidate;
        
        if (!$candidate) {
            $this->error("Match heeft geen candidate!");
            return 1;
        }
        
        $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')) ?: 'Onbekend';
        
        // Note: This command was designed for User, but matches now use Candidate.
        // You may need to find the User by email or refactor this command.
        $user = \App\Models\User::where('email', $candidate->email)->first();
        
        if (!$user) {
            $this->error("Geen user gevonden voor candidate email: {$candidate->email}");
            return 1;
        }
        
        // Voeg CV toe als deze nog niet bestaat
        if (!$user->cv_path && !$user->cvFiles()->exists()) {
            $cvPath = 'cvs/candidate_' . $user->id . '_cv.pdf';
            $cvFileName = 'CV_' . str_replace(' ', '_', $candidateName) . '.pdf';
            
            // Update user cv_path
            $user->update([
                'cv_path' => $cvPath,
                'cv_original_name' => $cvFileName
            ]);
            
            // Maak CVFile record
            CVFile::create([
                'user_id' => $user->id,
                'original_name' => $cvFileName,
                'file_path' => $cvPath,
                'file_type' => 'application/pdf',
                'file_size' => rand(500000, 3000000), // 500 KB - 3 MB
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
            
            $this->info("✓ CV toegevoegd aan: {$candidateName} (User ID: {$user->id})");
        } else {
            $this->info("User {$candidateName} heeft al een CV.");
        }
        
        // Voeg motivatie toe als deze nog niet bestaat
        if (empty($interview->match->notes) || strpos($interview->match->notes, 'Automatische match') !== false) {
            $motivations = [
                'Ik ben zeer geïnteresseerd in deze functie omdat het perfect aansluit bij mijn ervaring en ambities. Ik heb jarenlange ervaring in de ontwikkeling van webapplicaties en ben enthousiast om bij te dragen aan jullie team.',
                'Deze vacature spreekt me zeer aan omdat ik op zoek ben naar een uitdaging waarbij ik mijn technische vaardigheden kan combineren met mijn passie voor innovatie. Ik ben gemotiveerd om te groeien binnen jullie organisatie.',
                'Na het lezen van de vacature ben ik overtuigd dat ik een waardevolle toevoeging kan zijn voor jullie team. Mijn achtergrond in full-stack development en mijn sterke communicatieve vaardigheden maken mij een ideale kandidaat.',
            ];
            
            $motivation = $motivations[array_rand($motivations)];
            $interview->match->update([
                'notes' => $motivation
            ]);
            
            $this->info("✓ Motivatie toegevoegd aan match.");
        } else {
            $this->info("Match heeft al motivatie.");
        }
        
        return 0;
    }
}















