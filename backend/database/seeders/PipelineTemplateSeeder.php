<?php

namespace Database\Seeders;

use App\Models\PipelineTemplate;
use Illuminate\Database\Seeder;

class PipelineTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default template that can be used as a base for all companies
        $defaultStages = [
            [
                'id' => 'stg_1',
                'stageType' => 'SOURCE',
                'label' => 'Binnengekomen',
                'sequence' => 10,
                'optional' => false,
            ],
            [
                'id' => 'stg_2',
                'stageType' => 'SCREENING',
                'label' => 'CV screening',
                'sequence' => 20,
                'optional' => true,
            ],
            [
                'id' => 'stg_3',
                'stageType' => 'INTAKE',
                'label' => 'Intake gesprek',
                'sequence' => 30,
                'optional' => false,
            ],
            [
                'id' => 'stg_4',
                'stageType' => 'TEAM_INTERVIEW',
                'label' => 'Vervolggesprek (team)',
                'sequence' => 40,
                'optional' => false,
            ],
            [
                'id' => 'stg_5',
                'stageType' => 'SALARY_NEGOTIATION',
                'label' => 'Salarisgesprek',
                'sequence' => 50,
                'optional' => true,
            ],
            [
                'id' => 'stg_6',
                'stageType' => 'OFFER',
                'label' => 'Aanbod',
                'sequence' => 60,
                'optional' => true,
            ],
            [
                'id' => 'stg_7',
                'stageType' => 'SIGNING',
                'label' => 'Ondertekening',
                'sequence' => 70,
                'optional' => true,
            ],
        ];

        // Create a global default template (no company_id)
        PipelineTemplate::updateOrCreate(
            [
                'key' => 'default_general',
                'company_id' => null,
            ],
            [
                'name' => 'Standaard sollicitatieflow',
                'version' => 1,
                'is_default' => true,
                'is_active' => true,
                'stages' => $defaultStages,
                'terminal_stages' => ['REJECTION', 'WITHDRAWN'],
                'description' => 'Standaard sollicitatieflow die als basis kan dienen voor alle bedrijven.',
            ]
        );
    }
}
