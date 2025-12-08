<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CVFile;
use App\Models\JobMatch;
use Carbon\Carbon;

class AddCandidateCVAndMotivation extends Command
{
    protected $signature = 'app:add-candidate-cv-motivation {count=7}';
    protected $description = 'Voeg CV en motivatie toe aan kandidaten';

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
        
        // Neem maximaal het aantal gevraagde kandidaten
        $candidates = $candidates->take($count);
        
        $motivations = [
            'Ik ben zeer geïnteresseerd in deze functie omdat het perfect aansluit bij mijn ervaring en ambities. Ik heb jarenlange ervaring in de ontwikkeling van webapplicaties en ben enthousiast om bij te dragen aan jullie team.',
            'Deze vacature spreekt me zeer aan omdat ik op zoek ben naar een uitdaging waarbij ik mijn technische vaardigheden kan combineren met mijn passie voor innovatie. Ik ben gemotiveerd om te groeien binnen jullie organisatie.',
            'Na het lezen van de vacature ben ik overtuigd dat ik een waardevolle toevoeging kan zijn voor jullie team. Mijn achtergrond in full-stack development en mijn sterke communicatieve vaardigheden maken mij een ideale kandidaat.',
            'Ik ben zeer geïnteresseerd in deze functie en zie dit als een perfecte match tussen mijn vaardigheden en jullie behoeften. Ik ben gemotiveerd om mijn expertise in te zetten en tegelijkertijd te blijven leren en ontwikkelen.',
            'Deze functie biedt precies de uitdaging waar ik naar op zoek ben. Met mijn ervaring in moderne technologieën en mijn drive voor kwaliteit, ben ik ervan overtuigd dat ik een positieve bijdrage kan leveren aan jullie projecten.',
            'Ik ben enthousiast over deze mogelijkheid omdat het aansluit bij mijn carrièredoelen en mijn interesse in innovatieve oplossingen. Ik ben gemotiveerd om deel uit te maken van een team dat waarde creëert voor klanten.',
            'Na het bestuderen van jullie organisatie en deze vacature, ben ik overtuigd dat ik de juiste persoon ben voor deze functie. Mijn combinatie van technische expertise en soft skills maakt mij een sterke kandidaat.'
        ];
        
        $cvFileNames = [
            'CV_Tom_Smit.pdf',
            'CV_Lisa_de_Vries.pdf',
            'CV_Jan_Jansen.pdf',
            'CV_Maria_Bakker.pdf',
            'CV_Peter_Visser.pdf',
            'CV_Sophie_Meijer.pdf',
            'CV_David_Bos.pdf'
        ];
        
        $created = 0;
        
        foreach ($candidates as $index => $candidate) {
            // Voeg CV toe (dummy CVFile record)
            if (!$candidate->cvFiles()->exists() && !$candidate->cv_path) {
                // Maak een dummy CV file path
                $cvPath = 'cvs/candidate_' . $candidate->id . '_cv.pdf';
                $cvFileName = $cvFileNames[$index % count($cvFileNames)] ?? 'CV_' . $candidate->first_name . '_' . $candidate->last_name . '.pdf';
                
                // Update user cv_path
                $candidate->update([
                    'cv_path' => $cvPath,
                    'cv_original_name' => $cvFileName
                ]);
                
                // Maak CVFile record
                CVFile::create([
                    'user_id' => $candidate->id,
                    'original_name' => $cvFileName,
                    'file_path' => $cvPath,
                    'file_type' => 'application/pdf',
                    'file_size' => rand(500000, 3000000), // 500 KB - 3 MB
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);
            }
            
            // Voeg motivatie toe aan matches
            $matches = JobMatch::where('user_id', $candidate->id)->get();
            foreach ($matches as $match) {
                if (empty($match->notes) || $match->notes === 'Automatische match voor ' . $candidate->first_name . ' ' . $candidate->last_name) {
                    $motivation = $motivations[$index % count($motivations)] ?? $motivations[0];
                    $match->update([
                        'notes' => $motivation
                    ]);
                }
            }
            
            $created++;
            $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->middle_name ?? '') . ' ' . ($candidate->last_name ?? '')) ?: 'Onbekend';
            $this->info("✓ CV en motivatie toegevoegd aan: {$candidateName} (ID: {$candidate->id})");
        }
        
        $this->info("✓ Succesvol CV en motivatie toegevoegd aan {$created} kandidaten!");
        
        return 0;
    }
}

