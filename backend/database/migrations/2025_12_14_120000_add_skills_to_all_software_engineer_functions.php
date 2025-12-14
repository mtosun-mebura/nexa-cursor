<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branch_functions') || !Schema::hasTable('branch_function_skills')) {
            return;
        }

        $connection = DB::connection('pgsql');
        $now = now();

        // Alle functies met hun skills (uit de seed migration)
        $functionSkillsMap = [
            'Software_Engineer' => [
                'Programmeren_JavaScript', 'Programmeren_PHP', 'Programmeren_Python', 'Object_Oriented_Programming',
                'REST_API_Design', 'Databases_SQL', 'Versiebeheer_Git', 'Design_Patterns', 'Unit_Tests_Schrijven',
                'Debuggen', 'CI_CD_Pipelines', 'Cloud_Basics_AWS_Azure_GCP', 'Security_Basics_OWASP',
                'Laravel_Of_Ander_Framework', 'Code_Reviews', 'Probleemoplossend_Vermogen', 'Analytisch_Denken',
                'Samenwerken', 'Communicatieve_Vaardigheden', 'Zelfstandigheid', 'Plannen_En_Organiseren',
                'Nauwkeurigheid', 'Lerend_Vermogen', 'Omgaan_Met_Feedback', 'Proactieve_Houding',
            ],
            'Projectmanager' => [
                'Projectplanning', 'Scope_Management', 'Risicomanagement', 'Budgetbeheer', 'Stakeholdermanagement',
                'Resource_Planning', 'Scrum_Kennis', 'Agile_Methodieken', 'Prince2_Of_PMP_Basis', 'Rapportage_Opstellen',
                'MS_Project_Of_Alternatief', 'Change_Management', 'Kwaliteitsbewaking', 'Leiderschap', 'Besluitvaardigheid',
                'Communicatieve_Vaardigheden', 'Conflicthantering', 'Onderhandelingsvaardigheden', 'Resultaatgerichtheid',
                'Stressbestendigheid', 'Helikopterview', 'Organisatorisch_Vermogen', 'Samenwerken',
            ],
            'Accountmanager' => [
                'Relatiebeheer', 'New_Business_Acquisitie', 'Sales_Funnels_Beheren', 'Offertes_Maken',
                'Contractonderhandelingen', 'CRM_Systeem_Gebruik', 'Marktanalyse', 'Pipeline_Management',
                'Presentatievaardigheden', 'Forecasting', 'Up_En_Cross_Selling', 'Communicatieve_Vaardigheden',
                'Luistervaardigheid', 'Overtuigingskracht', 'Klantgerichtheid', 'Netwerken', 'Resultaatgerichtheid',
                'Doorzettingsvermogen', 'Relatiegericht_Denken', 'Empathisch_Vermogen', 'Zelfdiscipline',
            ],
            'Marketingmanager' => [
                'Marketingstrategie_Ontwikkelen', 'Campagneplanning', 'Digital_Marketing', 'SEO_Basis', 'SEA_Basis',
                'Social_Media_Advertising', 'Contentstrategie', 'E_mailmarketing', 'Marketingautomatisering',
                'Data_Analyseren_Google_Analytics_Of_Similar', 'Budgetbeheer', 'Marktonderzoek', 'Brand_Management',
                'Creatief_Denken', 'Analytisch_Denken', 'Communicatieve_Vaardigheden', 'Leiderschap', 'Samenwerken',
                'Strategisch_Denken', 'Besluitvaardigheid', 'Organisatorisch_Vermogen', 'Resultaatgerichtheid', 'Aanpassingsvermogen',
            ],
            'Salesmanager' => [
                'Salesstrategie_Ontwikkelen', 'Teamsturing', 'Targetsetting', 'Performance_Analyse', 'Forecasting',
                'Sales_Coaching', 'CRM_Gebruik', 'Key_Account_Management', 'Onderhandelingsstrategien', 'Rapportage_Opstellen',
                'Leiderschap', 'Motiveren_Van_Anderen', 'Resultaatgerichtheid', 'Communicatieve_Vaardigheden', 'Overtuigingskracht',
                'Conflicthantering', 'Besluitvaardigheid', 'Stressbestendigheid', 'Veranderingsbereidheid', 'Empathie',
            ],
            'HR_Manager' => [
                'HR_Beleid_Ontwikkelen', 'Werving_En_Selectie', 'Arbeidsrecht_Basis', 'Performance_Management',
                'Verzuimbegeleiding', 'Compensatie_En_Benefits', 'HR_Data_Analyse', 'Training_En_Ontwikkeling',
                'Functie_En_Salarishuis', 'Medewerkerstevredenheidsonderzoek', 'Communicatieve_Vaardigheden', 'Conflicthantering',
                'Coaching', 'Integer_Handelen', 'Organisatiesensitiviteit', 'Empathisch_Vermogen', 'Luistervaardigheid',
                'Besluitvaardigheid', 'Samenwerken', 'Adviesvaardigheden',
            ],
            'Recruiter' => [
                'Vacatureteksten_Schrijven', 'Candidate_Sourcing', 'LinkedIn_Recruitment', 'Interviewtechnieken',
                'Selectiecriteria_Opmaken', 'ATS_Systemen_Gebruik', 'Arbeidsmarktkennis', 'Screenen_Van_CVs',
                'Referentiechecks', 'Aanbod_En_Contractafhandeling', 'Communicatieve_Vaardigheden', 'Relatieopbouw',
                'Luistervaardigheid', 'Organisatorisch_Vermogen', 'Snel_Schakelen', 'Proactieve_Houding',
                'Resultaatgerichtheid', 'Empathie', 'Overtuigingskracht', 'Netwerken',
            ],
            'Klantenservice_Medewerker' => [
                'Telefoonvaardigheid', 'E_mail_En_Chat_Afhandeling', 'Ticketingsystemen', 'Basis_Administratie',
                'Product_Of_Dienstkennis', 'Klachtenafhandeling', 'Registratie_Van_Calls', 'Basis_IT_Vaardigheden',
                'Script_Volgen_En_Aanpassen', 'Klantgerichtheid', 'Geduld', 'Luistervaardigheid', 'Stressbestendigheid',
                'Empathie', 'Duidelijk_Formuleren', 'Oplossingsgericht_Denken', 'Teamwork', 'Flexibiliteit', 'Omgaan_Met_Weerstand',
            ],
            'Boekhouder' => [
                'Financiele_Administratie', 'Grootboekboekingen', 'Crediteurenbeheer', 'Debiteurenbeheer', 'BTW_Aangifte',
                'Jaarafsluiting_Ondersteuning', 'Excel_Gevorderd', 'Boekhoudpakketten_Exact_Of_Similar', 'Bankboekingen',
                'Kosten_En_Opbrengstenanalyse', 'Nauwkeurigheid', 'Structuur_En_Orde', 'Betrouwbaarheid', 'Discretie',
                'Analytisch_Denken', 'Tijdmanagement', 'Probleemoplossend_Vermogen', 'Communicatie_Met_Collegas', 'Verantwoordelijkheidsgevoel',
            ],
            'Data_Scientist' => [
                'Statistiek', 'Data_Analyse', 'Python_Of_R', 'Machine_Learning_Basics', 'SQL', 'Datavisualisatie',
                'Pandas_Of_Vergelijkbaar', 'Feature_Engineering', 'Model_Validatie', 'A_B_Testing', 'Data_Cleaning',
                'Dashboarding_Tools', 'Analytisch_Denken', 'Probleemoplossend_Vermogen', 'Nieuwsgierigheid',
                'Communicatieve_Vaardigheden', 'Complexe_Zaken_Eenvoudig_Uitleggen', 'Zelfstandigheid',
                'Samenwerken_Met_Niet_Technische_Stakeholders', 'Kritisch_Denken', 'Nauwkeurigheid',
            ],
            'Verpleegkundige' => [
                'Verpleegtechnische_Handelingen', 'Medicatie_Toedienen', 'Observatie_Van_Patienten', 'Dossiervoering',
                'Triageren', 'Basis_Life_Support', 'Hygieneprotocollen', 'Wondzorg', 'Overdracht_Schrijven',
                'Samenwerken_Met_Artsen_En_Therapeuten', 'Empathie', 'Stressbestendigheid', 'Teamwork',
                'Communicatieve_Vaardigheden', 'Zorgvuldigheid', 'Flexibiliteit', 'Besluitvaardigheid_In_Druk',
                'Geduld', 'Verantwoordelijkheidsgevoel', 'Omgaan_Met_Emoties_Van_Anderen',
            ],
            'Arts' => [
                'Medische_Diagnostiek', 'Anamnese_Afnemen', 'Lichamelijk_Onderzoek', 'Behandelplannen_Opmaken',
                'Voorschrijven_Van_Medicatie', 'Interpretatie_Van_Lab_Uitslagen', 'Kenntnis_Richtlijnen_En_Protocollen',
                'Acute_Zorg_Basis', 'Dossiervoering', 'Multidisciplinaire_Samenwerking', 'Empathie',
                'Communicatieve_Vaardigheden', 'Besluitvaardigheid', 'Stressbestendigheid', 'Ethiek_En_Integriteit',
                'Luistervaardigheid', 'Uitleggen_Van_Complexe_Informatie', 'Samenwerken', 'Leiderschap_In_Behandelteam', 'Reflectievermogen',
            ],
            'Docent' => [
                'Lesvoorbereiding', 'Didactische_Vaardigheden', 'Klassenmanagement', 'Toetsen_Ontwerpen', 'Toetsen_Nakijken',
                'Digitale_Lesmiddelen_Gebruik', 'Differentiatie_In_De_Klas', 'Leerdoelen_Formuleren', 'Evalueren_Van_Leren',
                'Basis_Administratie_Resultaten', 'Communicatieve_Vaardigheden', 'Geduld', 'Klassenleiderschap',
                'Motiveren_Van_Leerlingen', 'Empathie', 'Creativiteit', 'Flexibiliteit', 'Consequent_Handelen',
                'Samenwerken_Met_Collegas', 'Contact_Met_Ouders',
            ],
            'Grafisch_Ontwerper' => [
                'Adobe_Photoshop', 'Adobe_Illustrator', 'Adobe_InDesign', 'Typografie', 'Kleurgebruik', 'Layout_Design',
                'Branding_En_Huisstijl', 'Bestandsvoorbereiding_Drukwerk', 'Wireframing_Basis', 'Digitale_Assets_Maken_Web_Social',
                'Creatief_Denken', 'Oog_Voor_Detail', 'Tijdsbeheer', 'Feedback_Verwerken', 'Communicatie_Met_Opdrachtgevers',
                'Samenwerken_Met_Team', 'Probleemoplossend_Denken', 'Aanpassingsvermogen', 'Zelfstandigheid',
            ],
            'Product_Owner' => [
                'Product_Backlog_Management', 'User_Stories_Schrijven', 'Prioriteren_Volgens_Waarde', 'Stakeholdermanagement',
                'Agile_Scrum_Kennis', 'Roadmap_Planning', 'Basis_Datagedreven_Beslissen', 'Release_Planning',
                'Acceptatiecriteria_Formuleren', 'Communicatieve_Vaardigheden', 'Stakeholder_Afstemming', 'Besluitvaardigheid',
                'Visionair_Denken', 'Prioriteiten_Stellen', 'Samenwerken_Met_Development_Team', 'Luistervaardigheid',
                'Overtuigingskracht', 'Flexibiliteit', 'Resultaatgerichtheid',
            ],
            'Logistiek_Medewerker' => [
                'Orderpicking', 'Voorraadbeheer_Basis', 'Magazijnsystemen_WMS', 'Scannen_En_Inboeken', 'Verzendklaar_Maken',
                'In_En_Uitpakken', 'Basis_Veiligheidsvoorschriften', 'Eventueel_Heftruck_Rijden', 'Retourenverwerking',
                'Nauwkeurigheid', 'Fysieke_Belastbaarheid', 'Teamwork', 'Tijdsdruk_Aankunnen', 'Discipline',
                'Zelfstandigheid', 'Verantwoordelijkheidsgevoel', 'Communicatie_Met_Collegas',
            ],
        ];

        foreach ($functionSkillsMap as $functionName => $skills) {
            // Vind alle functies met deze naam (in alle branches)
            $functions = $connection->table('branch_functions')
                ->where('name', $functionName)
                ->get();

            foreach ($functions as $function) {
                // Check hoeveel skills deze functie al heeft
                $existingCount = $connection->table('branch_function_skills')
                    ->where('branch_function_id', $function->id)
                    ->count();

                // Alleen toevoegen als er nog geen skills zijn
                if ($existingCount === 0) {
                    foreach ($skills as $skillName) {
                        $connection->table('branch_function_skills')->updateOrInsert(
                            [
                                'branch_function_id' => $function->id,
                                'name' => $skillName,
                            ],
                            [
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        );
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank (data migration).
    }
};
