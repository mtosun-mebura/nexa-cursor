<?php

namespace Database\Seeders;

use App\Models\InfoRequestFormField;
use Illuminate\Database\Seeder;

class InfoRequestFormFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            ['name' => 'voornaam', 'label' => 'Voornaam', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 10],
            ['name' => 'achternaam', 'label' => 'Achternaam', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 20],
            ['name' => 'email_aanvraag', 'label' => 'E-mailadres', 'is_required' => true, 'validation_rule' => 'email', 'sort_order' => 30],
            ['name' => 'telefoonnummer', 'label' => 'Telefoonnummer', 'is_required' => false, 'validation_rule' => 'tel', 'sort_order' => 40],
            ['name' => 'omschrijving', 'label' => 'Omschrijving / vraag', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 50],
        ];

        foreach ($defaults as $row) {
            InfoRequestFormField::firstOrCreate(
                ['name' => $row['name']],
                $row
            );
        }
    }
}
