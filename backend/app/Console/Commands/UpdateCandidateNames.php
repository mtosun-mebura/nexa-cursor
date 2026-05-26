<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use App\Models\User;

class UpdateCandidateNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:update-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update alle kandidaat namen om (K) toe te voegen';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bijwerken van kandidaat namen...');

        // Update Candidate model records
        $candidates = Candidate::all();
        $updatedCandidates = 0;

        foreach ($candidates as $candidate) {
            $firstName = $candidate->first_name;
            $lastName = $candidate->last_name;

            // Voeg (K) toe als het er nog niet in staat
            if (strpos($firstName, '(K)') === false) {
                $candidate->first_name = $firstName . ' (K)';
                $candidate->save();
                $updatedCandidates++;
                $this->line("✓ Candidate: {$firstName} {$lastName} -> {$candidate->first_name} {$lastName}");
            }
        }

        // Update User records met candidate rol
        $candidateUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'candidate');
        })->get();
        
        $updatedUsers = 0;

        foreach ($candidateUsers as $user) {
            $firstName = $user->first_name;
            $lastName = $user->last_name;

            // Voeg (K) toe als het er nog niet in staat
            if (strpos($firstName, '(K)') === false) {
                $user->first_name = $firstName . ' (K)';
                $user->save();
                $updatedUsers++;
                $this->line("✓ User: {$firstName} {$lastName} -> {$user->first_name} {$lastName}");
            }
        }

        $totalUpdated = $updatedCandidates + $updatedUsers;
        $this->info("Klaar! {$updatedCandidates} Candidate record(s) en {$updatedUsers} User record(s) bijgewerkt. Totaal: {$totalUpdated}");
        
        return Command::SUCCESS;
    }
}

