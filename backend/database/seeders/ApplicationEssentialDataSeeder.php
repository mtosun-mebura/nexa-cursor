<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Referentiedata voor productie en lokale setup: branches, pipeline, thema's, formulieren, betalingen.
 * Idempotent waar mogelijk; veilig om na elke migratie opnieuw te draaien.
 */
class ApplicationEssentialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            StageTypeSeeder::class,
            PipelineTemplateSeeder::class,
            FrontendThemeSeeder::class,
            InfoRequestFormFieldSeeder::class,
            PaymentProviderSeeder::class,
            TaxiRideAcceptedEmailTemplateSeeder::class,
        ]);
    }
}
