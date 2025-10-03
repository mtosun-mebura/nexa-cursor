<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Maak nieuwe bedrijven aan
        $companies = [
            [
                'name' => 'TechCorp Solutions',
                'description' => 'Innovatieve technologiebedrijf gespecialiseerd in software ontwikkeling en digitale transformatie.',
                'industry' => 'Technologie',
                'city' => 'Amsterdam',
                'is_intermediary' => false,
            ],
            [
                'name' => 'Healthcare Plus',
                'description' => 'Vooraanstaand zorgbedrijf dat zich richt op patiëntenzorg en medische innovatie.',
                'industry' => 'Zorg & Welzijn',
                'city' => 'Utrecht',
                'is_intermediary' => false,
            ],
            [
                'name' => 'FinanceFirst',
                'description' => 'Toonaangevende financiële dienstverlener met focus op duurzame investeringen.',
                'industry' => 'Financiële Dienstverlening',
                'city' => 'Rotterdam',
                'is_intermediary' => false,
            ],
            [
                'name' => 'GreenEnergy Co',
                'description' => 'Pionier in duurzame energie en milieuvriendelijke oplossingen.',
                'industry' => 'Energie & Milieu',
                'city' => 'Den Haag',
                'is_intermediary' => false,
            ],
            [
                'name' => 'EduTech Academy',
                'description' => 'Moderne onderwijsinstelling die technologie integreert in het leerproces.',
                'industry' => 'Onderwijs',
                'city' => 'Eindhoven',
                'is_intermediary' => false,
            ],
            [
                'name' => 'RetailMax',
                'description' => 'Grote retailketen met focus op klantbeleving en digitale innovatie.',
                'industry' => 'Retail & Handel',
                'city' => 'Breda',
                'is_intermediary' => false,
            ],
            [
                'name' => 'ManufacturingPro',
                'description' => 'Geavanceerd productiebedrijf gespecialiseerd in precisie-engineering.',
                'industry' => 'Productie & Industrie',
                'city' => 'Tilburg',
                'is_intermediary' => false,
            ],
            [
                'name' => 'LogisticsHub',
                'description' => 'Efficiënte logistieke dienstverlener met focus op duurzame transportoplossingen.',
                'industry' => 'Logistiek & Transport',
                'city' => 'Zwolle',
                'is_intermediary' => false,
            ],
            [
                'name' => 'CreativeStudio',
                'description' => 'Creatief bureau gespecialiseerd in branding, marketing en digitale media.',
                'industry' => 'Marketing & Communicatie',
                'city' => 'Maastricht',
                'is_intermediary' => false,
            ],
            [
                'name' => 'LegalAssociates',
                'description' => 'Gerenommeerd advocatenkantoor met expertise in bedrijfsrecht en compliance.',
                'industry' => 'Recht & Juridisch',
                'city' => 'Alkmaar',
                'is_intermediary' => false,
            ],
        ];

        foreach ($companies as $companyData) {
            $company = Company::firstOrCreate(
                ['name' => $companyData['name']],
                $companyData
            );
            
            if ($company->wasRecentlyCreated) {
                $this->command->info("Bedrijf '{$companyData['name']}' aangemaakt.");
            } else {
                $this->command->line("Bedrijf '{$companyData['name']}' bestaat al.");
            }
        }

        // Markeer bestaande bedrijven als tussenpartij
        $intermediaryCompanies = [
            'Tosun', // Recruitment/detachering bureau
        ];

        foreach ($intermediaryCompanies as $companyName) {
            $company = Company::where('name', $companyName)->first();
            if ($company) {
                $company->update(['is_intermediary' => true]);
                $this->command->info("Bedrijf '{$companyName}' gemarkeerd als tussenpartij.");
            }
        }

        $this->command->info('Company seeder voltooid.');
    }
}
