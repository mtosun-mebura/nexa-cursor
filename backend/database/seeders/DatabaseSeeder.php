<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ApplicationBootstrapSeeder::class,
            VacancySeeder::class,
            CandidateSeeder::class,
            MatchSeeder::class, // Nieuwe seeder voor matches
            InterviewMatchSeeder::class,
            NotificationSeeder::class,
            EmailTemplateSeeder::class,
        ]);
    }
}
