<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branch_functions')) {
            Schema::create('branch_functions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('name');
                $table->timestamps();

                $table->unique(['branch_id', 'name']);
                $table->index(['branch_id', 'name']);
            });
        }

        // Seed initial branch functions list (as requested)
        $data = [
            'Marketing' => [
                'Marketingmanager','Digital_Marketeer','Content_Marketeer','SEO_Specialist','SEA_Specialist','Socialmedia_Manager','Brandmanager','Productmarketeer','Growth_Marketeer','Emailmarketeer','Marketinganalist','Copywriter','Creatief_Directeur','Marketingcoordinator','Campagnemanager','Communitymanager','Influencer_Manager','UX_Schrijver',
            ],
            'Finance' => [
                'Financieel_Analist','Accountant','Controller','Finance_Manager','CFO','Beleggingsanalist','Portefeuillemanager','Risico_Analist','Compliance_Officer','Auditor','Budgetanalist','Treasury_Analist','Belastingadviseur','Financieel_Planner','Kredietanalist','Actuaris',
            ],
            'HR' => [
                'HR_Manager','HR_Businesspartner','Recruiter','Talent_Acquisition_Specialist','HR_Coordinator','Opleiding_Development_Specialist','Compensation_Benefits_Specialist','HR_Generalist','HR_Directeur','Payroll_Specialist','Diversiteit_Inclusie_Manager',
            ],
            'Sales' => [
                'Vertegenwoordiger','Accountmanager','Salesmanager','Business_Development_Manager','Inside_Sales','Outside_Sales','Key_Accountmanager','Sales_Executive','Salesdirecteur','Customer_Success_Manager','Regiomanager','SDR','BDR',
            ],
            'Engineering' => [
                'Software_Engineer','Softwareontwikkelaar','Werktuigbouwkundig_Ingenieur','Elektrotechnisch_Ingenieur','Civiel_Ingenieur','Chemisch_Ingenieur','Aerospace_Engineer','Systems_Engineer','QA_Engineer','DevOps_Engineer','Data_Engineer','Netwerkingenieur','Constructeur','Robotica_Engineer','Embedded_Engineer',
            ],
            'Education' => [
                'Docent','Leerkracht','Leraar','Universitair_Docent','Professor','Onderwijsassistent','Onderwijscoordinator','Onderwijskundig_Ontwerper','Studieadviseur','Schoolleider','Curriculumontwikkelaar','Docent_Speciaal_Onderwijs','Tutor','Onderwijsconsultant',
            ],
            'Construction' => [
                'Bouwvakker','Uitvoerder','Projectleider_Bouw','Architect','Civiel_Ingenieur','Calculator','Timmerman','Elektricien','Loodgieter','Voorman','Veiligheidskundige','Bouwinspecteur',
            ],
            'Hospitality' => [
                'Hotelmanager','Receptiemedewerker','Concierge','Housekeeping_Medewerker','Eventmanager','Chefkok','Souschef','Bartender','Bedieningsmedewerker','Restaurantmanager','Gastheer_Gastvrouw','Guest_Relations_Manager',
            ],
            'Legal' => [
                'Jurist','Advocaat','Legal_Counsel','Paralegal','Juridisch_Assistent','Bedrijfsjurist','Contractspecialist','Compliance_Officer','Rechter','Juridisch_Onderzoeker','Procesjurist',
            ],
            'Real_Estate' => [
                'Makelaar','Vastgoedadviseur','Property_Manager','Vastgoedbeheerder','Verhuurconsulent','Taxateur','Vastgoedanalist','Asset_Manager','Facilitair_Manager',
            ],
            'Retail' => [
                'Filiaalmanager','Verkoopmedewerker','Kassamedewerker','Visual_Merchandiser','Voorraadbeheerder','Assistent_Filiaalmanager','Retail_Buyer','Diefstalpreventie_Medewerker',
            ],
            'Travel_Tourism' => [
                'Reisagent','Reisadviseur','Gids','Reserveringsmedewerker','Stewardess','Piloot','Toerisme_Manager','Guest_Services_Medewerker',
            ],
            'Transportation' => [
                'Vrachtwagenchauffeur','Koerier','Logistiek_Coordinator','Fleet_Manager','Transportplanner','Dispatcher','Magazijnmedewerker','Supply_Chain_Analist',
            ],
            'Manufacturing' => [
                'Productiemedewerker','Machineoperator','Manufacturing_Engineer','Kwaliteitscontroleur','Plantmanager','Productiemanager','Onderhoudsmonteur','Supply_Chain_Specialist',
            ],
            'Arts' => [
                'Grafisch_Ontwerper','Illustrator','Animator','Art_Director','Fotograaf','Videograaf','Muzikant','Beeldhouwer','Conservator','Creatief_Producer',
            ],
            'Science' => [
                'Onderzoeker','Laborant','Chemicus','Natuurkundige','Bioloog','Data_Scientist','Onderzoeksassistent','Veldonderzoeker',
            ],
            'Government' => [
                'Beleidsadviseur','Ambtenaar','Publiek_Administrator','Programmamanager','Inspecteur','Diplomaat','Stedenbouwkundige','Maatschappelijk_Werker',
            ],
            'Non_Profit' => [
                'Programmacoordinator','Fondsenwerver','Subsidieschrijver','Vrijwilligerscoordinator','Outreach_Specialist','Directeur_Stichting','Casemanager',
            ],
            'Advertising' => [
                'Art_Director','Copywriter','Mediaplanner','Account_Executive','Creative_Director','Campagnestrateeg','Ad_Ops_Specialist',
            ],
            'Agriculture' => [
                'Landbouwer','Agronoom','Landbouwkundig_Ingenieur','Bedrijfsleider_Landbouw','Tuinbouwer','Veeteeltmanager','Landbouwtechnicus',
            ],
            'Automotive' => [
                'Automonteur','Technisch_Specialist_Auto','Serviceadviseur','Automotive_Engineer','Productiemedewerker_Auto','Onderdelenspecialist','Kwaliteitscontroleur',
            ],
            'Biotechnology' => [
                'Biotech_Onderzoeker','Laboratoriumtechnicus','Bioinformaticus','Procesingenieur','Regulatory_Affairs_Specialist','QA_Wetenschapper',
            ],
            'Consulting' => [
                'Consultant','Managementconsultant','Strategieconsultant','IT_Consultant','Financieel_Consultant','HR_Consultant','Operations_Consultant','Business_Analist',
            ],
            'Sports' => [
                'Coach','Sporter','Fitnessinstructeur','Sportmanager','Scheidsrechter','Sportmarketeer','Sportfysiotherapeut','Scout',
            ],
            'Energy' => [
                'Energie_Engineer','Petroleumingenieur','Zonnepaneeltechnicus','Windturbinetechnicus','Energie_Analist','Centrale_Operator','Milieutechnisch_Ingenieur',
            ],
            'Entertainment' => [
                'Acteur','Producer','Regisseur','Scenarioschrijver','Video_Editor','Geluidstechnicus','Talentmanager','Productieassistent',
            ],
            'Environmental' => [
                'Milieuwetenschapper','Duurzaamheidsspecialist','Milieuconsultant','Ecoloog','Afvalbeheer_Specialist','Milieu_Engineer',
            ],
            'Fashion' => [
                'Modeontwerper','Stylist','Patroonmaker','Mode_Inkoper','Merchandiser','Mode_Illustrator','Productontwikkelaar_Mode','Showroommanager',
            ],
            'Food_Beverage' => [
                'Chefkok','Kok','Banketbakker','Barista','Restaurantmanager','Voedingsmiddelentechnoloog','QA_Technicus_Voeding','Productiemedewerker_Voeding',
            ],
            'Gaming' => [
                'Game_Designer','Game_Developer','3D_Artist','Level_Designer','QA_Tester','Narrative_Designer','Game_Producer','Communitymanager',
            ],
            'Insurance' => [
                'Verzekeringsadviseur','Schadebehandelaar','Acceptant','Actuaris','Risicomanager','Verzekeringsanalist','Klantenservicemedewerker',
            ],
            'Media' => [
                'Journalist','Redacteur','Producer','Reporter','Cameraperson','Audiotechnicus','Social_Media_Editor','Content_Producer',
            ],
            'Pharmaceuticals' => [
                'Farmaceutisch_Onderzoeker','Apothekersassistent','Clinical_Research_Associate','Kwaliteitscontroleur_Farmacie','Regulatory_Affairs_Specialist','Productontwikkelaar_Farmacie',
            ],
            'Public_Relations' => [
                'PR_Adviseur','Communicatieadviseur','Woordvoerder','Mediarelatie_Specialist','PR_Manager','Event_Coordinator',
            ],
            'Research_Development' => [
                'R&D_Engineer','Onderzoeker','Innovatiemanager','Productontwikkelaar','Labonderzoeker','Research_Engineer',
            ],
            'Security' => [
                'Beveiliger','Security_Officer','Cybersecurity_Specialist','Security_Analist','Bedrijfsrechercheur','Security_Consultant',
            ],
            'Telecommunications' => [
                'Netwerkbeheerder','Telecom_Engineer','Customer_Service_Telecom','Telecom_Sales','Systems_Engineer_Telecom','Installatiemonteur',
            ],
            'Healthcare' => [
                'Verpleegkundige','Arts','Apotheker','Fysiotherapeut','Zorgassistent','Medisch_Specialist','Psycholoog','Tandarts','Radioloog','Verpleegkundig_Specialist',
            ],
            'IT' => [
                'Softwareontwikkelaar','Software_Engineer','IT_Support','Systeembeheerder','DevOps_Engineer','Cloud_Engineer','Data_Scientist','Data_Engineer','IT_Consultant','Business_Analist_IT','Cybersecurity_Specialist','Solutions_Architect',
            ],
            'Accounting' => [
                'Boekhouder','Accountant','Financieel_Administratief_Medewerker','Controller','Assistent_Accountant','Auditmedewerker','Payroll_Specialist','Belastingadviseur',
            ],
        ];

        $now = now();

        foreach ($data as $branchName => $functions) {
            // Branch keys can contain underscores. We store the branch name as a human label.
            $branchDisplayName = str_replace('_', ' ', $branchName);
            $branchSlug = Str::slug($branchDisplayName);

            $branchId = DB::table('branches')
                ->where('slug', $branchSlug)
                ->orWhere('name', $branchDisplayName)
                ->orWhere('name', $branchName)
                ->value('id');

            if (!$branchId) {
                $branchId = DB::table('branches')->insertGetId([
                    'name' => $branchDisplayName,
                    'slug' => $branchSlug,
                    'description' => null,
                    'color' => null,
                    'icon' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($functions as $fn) {
                DB::table('branch_functions')->updateOrInsert(
                    ['branch_id' => $branchId, 'name' => $fn],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_functions');
    }
};


