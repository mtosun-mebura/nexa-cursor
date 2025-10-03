<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vacancy;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Support\Str;

class VacancySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $categories = Category::all();
        
        if ($companies->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('Geen bedrijven of categorieën gevonden. Maak eerst deze aan.');
            return;
        }

        // Verwijder alle bestaande vacatures
        Vacancy::truncate();
        $this->command->info('Bestaande vacatures verwijderd.');

        // Maak 5 vacatures voor Tosun
        $this->createTosunVacancies($companies, $categories);
        
        // Maak 5 vacatures voor Mali bedrijf
        $this->createMaliBedrijfVacancies($companies, $categories);
        
        // Maak extra vacatures voor meer diversiteit
        $this->createExtraVacancies($companies, $categories);
        
        $this->command->info('Extra vacatures succesvol aangemaakt!');
    }
    
    /**
     * Maak 5 vacatures aan voor Tosun
     */
    private function createTosunVacancies($companies, $categories)
    {
        $tosun = Company::where('name', 'Tosun')->first();
        
        if (!$tosun) {
            $this->command->warn('Tosun niet gevonden in database.');
            return;
        }
        
        $tosunVacancies = [
            [
                'title' => 'Senior Full Stack Developer',
                'location' => 'Tilburg Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Wij zoeken een ervaren Full Stack Developer die ons team kan versterken bij het ontwikkelen van innovatieve webapplicaties. Je werkt met moderne technologieën zoals React, Node.js en Laravel. Je draagt bij aan de technische architectuur en begeleidt junior developers.',
                'requirements' => "- Minimaal 5 jaar ervaring met JavaScript, PHP en moderne frameworks\n- Ervaring met React, Vue.js of Angular\n- Kennis van Laravel, Symfony of vergelijkbare PHP frameworks\n- Ervaring met databases (MySQL, PostgreSQL)\n- Kennis van Docker en CI/CD pipelines\n- Goede communicatieve vaardigheden en teamspirit\n- Ervaring met agile development methodieken",
                'offer' => "- Marktconform salaris van €4.500 - €6.500 per maand\n- Flexibele werktijden en remote werk mogelijkheden\n- 25 vakantiedagen per jaar\n- Pensioenregeling en ziektekostenverzekering\n- Budget voor opleidingen en conferenties\n- Moderne werkomgeving in het centrum van Tilburg\n- Doorgroeimogelijkheden naar Tech Lead of Architect",
                'application_instructions' => 'Stuur je CV, motivatiebrief en portfolio naar hr@tosun.nl. Vermeld in je motivatie waarom je geïnteresseerd bent in deze functie en wat je kunt bijdragen aan ons team.',
                'salary_range' => '€4.500 - €6.500 per maand',
                'start_date' => '2024-02-01',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'TOSUN-DEV-001',
                'logo' => 'https://via.placeholder.com/150x50/9c27b0/ffffff?text=Tosun',
            ],
            [
                'title' => 'Marketing Manager Digital',
                'location' => 'Rotterdam Blaak',
                'employment_type' => 'Fulltime',
                'description' => 'Als Marketing Manager Digital ben je verantwoordelijk voor alle digitale marketing activiteiten van ons groeiende bedrijf. Je ontwikkelt en implementeert marketingstrategieën die onze online aanwezigheid versterken en leads genereren.',
                'requirements' => "- HBO/WO opleiding in Marketing, Communicatie of aanverwant\n- Minimaal 4 jaar ervaring in digitale marketing\n- Ervaring met Google Ads, Facebook Ads en LinkedIn Ads\n- Kennis van SEO, SEM en content marketing\n- Ervaring met marketing automation tools (HubSpot, Mailchimp)\n- Analytische mindset en data-driven aanpak\n- Uitstekende communicatieve vaardigheden",
                'offer' => "- Aantrekkelijk salaris van €3.800 - €5.200 per maand\n- Bonussysteem gebaseerd op KPI\'s\n- 24 vakantiedagen per jaar\n- Thuiswerk mogelijkheden\n- Budget voor marketing tools en opleidingen\n- Moderne werkomgeving in het centrum van Rotterdam",
                'application_instructions' => 'Solliciteer via onze website met je CV, motivatiebrief en voorbeelden van succesvolle marketing campagnes die je hebt uitgevoerd.',
                'salary_range' => '€3.800 - €5.200 per maand',
                'start_date' => '2024-01-15',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => false,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'TOSUN-MKT-002',
                'logo' => 'https://via.placeholder.com/150x50/2196F3/ffffff?text=Tosun',
            ],
            [
                'title' => 'UX/UI Designer Senior',
                'location' => 'Utrecht Centrum',
                'employment_type' => 'Parttime',
                'description' => 'Wij zoeken een creatieve en ervaren UX/UI Designer die gebruiksvriendelijke en aantrekkelijke interfaces kan ontwerpen. Je werkt samen met ons development team aan het verbeteren van onze producten.',
                'requirements' => "- Minimaal 4 jaar ervaring in UX/UI design\n- Ervaring met Figma, Sketch of Adobe XD\n- Portfolio met relevante projecten en case studies\n- Kennis van user-centered design principes\n- Ervaring met design systems en component libraries\n- Kennis van accessibility guidelines (WCAG)\n- Goede communicatieve vaardigheden",
                'offer' => "- Flexibele werktijden (24-32 uur per week)\n- Creatieve vrijheid en eigen verantwoordelijkheid\n- Moderne tools en software (Figma Pro, Adobe Creative Suite)\n- Budget voor design tools en opleidingen\n- Inspirerende werkomgeving in het centrum van Utrecht",
                'application_instructions' => 'Stuur je portfolio, CV en motivatiebrief naar design@tosun.nl. Zorg ervoor dat je portfolio recente projecten bevat.',
                'salary_range' => '€3.200 - €4.800 per maand (24-32 uur)',
                'start_date' => '2024-02-15',
                'working_hours' => '24-32 uur per week',
                'travel_expenses' => false,
                'remote_work' => true,
                'status' => 'In behandeling',
                'language' => 'Nederlands',
                'reference_number' => 'TOSUN-DES-003',
                'logo' => 'https://via.placeholder.com/150x50/FF9800/ffffff?text=Tosun',
            ],
            [
                'title' => 'Data Scientist Senior',
                'location' => 'Den Haag Centrum',
                'employment_type' => 'Contract',
                'description' => 'Als Senior Data Scientist analyseer je grote datasets om waardevolle inzichten te genereren voor het management. Je bouwt machine learning modellen, dashboards en rapporten die helpen bij strategische besluitvorming.',
                'requirements' => "- WO opleiding in Data Science, Statistiek, Wiskunde of aanverwant\n- Minimaal 5 jaar ervaring als Data Scientist\n- Ervaring met Python, R, SQL en machine learning libraries\n- Kennis van data visualisatie tools (Tableau, Power BI)\n- Ervaring met big data technologieën (Spark, Hadoop)\n- Kennis van statistische modellen en algoritmes\n- Uitstekende communicatieve vaardigheden",
                'offer' => "- Project-gebaseerd werk met hoge dagtarieven\n- Flexibele inzetbaarheid en remote werk mogelijkheden\n- Uitdagende projecten bij verschillende klanten\n- Mogelijkheid tot het opbouwen van een eigen portfolio\n- Netwerk events en kennisuitwisseling",
                'application_instructions' => 'Solliciteer met je CV, motivatiebrief en voorbeelden van eerdere data science projecten.',
                'salary_range' => '€500 - €700 per dag',
                'start_date' => '2024-01-20',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'TOSUN-DATA-004',
                'logo' => 'https://via.placeholder.com/150x50/4CAF50/ffffff?text=Tosun',
            ],
            [
                'title' => 'DevOps Engineer Lead',
                'location' => 'Groningen Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Wij zoeken een ervaren DevOps Engineer die ons helpt bij het automatiseren van onze deployment processen en het optimaliseren van onze infrastructuur. Je leidt het DevOps team en zorgt voor best practices.',
                'requirements' => "- Minimaal 6 jaar ervaring in DevOps of system administration\n- Ervaring met Docker, Kubernetes en container orchestration\n- Kennis van cloud platforms (AWS, Azure, GCP)\n- Ervaring met CI/CD pipelines (Jenkins, GitLab CI, GitHub Actions)\n- Scripting vaardigheden (Bash, Python, PowerShell)\n- Kennis van infrastructure as code (Terraform, CloudFormation)\n- Kennis van security best practices en compliance",
                'offer' => "- Competitief salaris van €4.500 - €7.000 per maand\n- Moderne tech stack en tools\n- Remote werk mogelijkheden\n- Uitstekende secundaire arbeidsvoorwaarden\n- Doorgroeimogelijkheden naar DevOps Architect\n- Budget voor certificeringen en opleidingen",
                'application_instructions' => 'Solliciteer via onze website met je CV en GitHub profiel.',
                'salary_range' => '€4.500 - €7.000 per maand',
                'start_date' => '2024-01-30',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Gesloten',
                'language' => 'Nederlands',
                'reference_number' => 'TOSUN-DEVOPS-005',
                'logo' => 'https://via.placeholder.com/150x50/607D8B/ffffff?text=Tosun',
            ],
        ];
        
        foreach ($tosunVacancies as $vacancyData) {
            $category = $categories->random();
            
            // Voeg company_id en category_id toe
            $vacancyData['company_id'] = $tosun->id;
            $vacancyData['category_id'] = $category->id;
            
            // Genereer een publicatiedatum (laatste 30 dagen)
            $vacancyData['publication_date'] = now()->subDays(rand(1, 30));
            
            // Genereer een sluitdatum (binnen 30 dagen)
            $vacancyData['closing_date'] = now()->addDays(rand(7, 30));
            
            // Genereer SEO velden
            $vacancyData['meta_title'] = $vacancyData['title'] . ' - Tosun';
            $vacancyData['meta_description'] = $this->generateMetaDescription($vacancyData);
            $vacancyData['meta_keywords'] = $this->generateMetaKeywords($vacancyData, $tosun, $category);

            Vacancy::create($vacancyData);
        }
        
        $this->command->info('5 vacatures voor Tosun succesvol aangemaakt!');
    }
    
    /**
     * Maak specifieke vacatures aan voor Mali bedrijf
     */
    private function createMaliBedrijfVacancies($companies, $categories)
    {
        $maliBedrijf = Company::where('name', 'Mali bedrijf')->first();
        
        if (!$maliBedrijf) {
            $this->command->warn('Mali bedrijf niet gevonden in database.');
            return;
        }
        
        $maliVacancies = [
            [
                'title' => 'Frontend Developer React',
                'location' => 'Breda Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Mali bedrijf zoekt een ervaren Frontend Developer die gespecialiseerd is in React. Je werkt aan moderne webapplicaties en draagt bij aan de ontwikkeling van gebruiksvriendelijke interfaces. Je werkt in een dynamisch team van developers en designers.',
                'requirements' => "- Minimaal 3 jaar ervaring met React en moderne JavaScript\n- Ervaring met TypeScript en ES6+\n- Kennis van CSS preprocessors (Sass, Less)\n- Ervaring met state management (Redux, Context API)\n- Kennis van responsive design en accessibility\n- Ervaring met testing frameworks (Jest, React Testing Library)\n- Goede communicatieve vaardigheden en teamspirit\n- Ervaring met Git en agile development",
                'offer' => "- Marktconform salaris van €3.800 - €5.500 per maand\n- Flexibele werktijden en hybride werk mogelijkheden\n- 25 vakantiedagen per jaar\n- Uitstekende secundaire arbeidsvoorwaarden\n- Budget voor opleidingen en conferenties\n- Moderne werkomgeving in Breda Centrum\n- Doorgroeimogelijkheden naar Senior Developer",
                'application_instructions' => 'Solliciteer via onze website met je CV, motivatiebrief en portfolio. Zorg ervoor dat je portfolio recente React projecten bevat en toont hoe je denkt over frontend development.',
                'salary_range' => '€3.800 - €5.500 per maand',
                'start_date' => '2024-03-01',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'MALI-FE-001',
                'logo' => 'https://via.placeholder.com/150x50/FF5722/ffffff?text=MaliBedrijf',
            ],
            [
                'title' => 'Backend Developer PHP/Laravel',
                'location' => 'Rotterdam Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Wij zoeken een ervaren Backend Developer die gespecialiseerd is in PHP en Laravel. Je ontwikkelt robuuste API\'s en backend systemen die onze frontend applicaties ondersteunen. Je werkt aan schaalbare oplossingen en draagt bij aan de technische architectuur.',
                'requirements' => "- Minimaal 4 jaar ervaring met PHP en Laravel\n- Ervaring met RESTful API development\n- Kennis van databases (MySQL, PostgreSQL)\n- Ervaring met caching (Redis, Memcached)\n- Kennis van Docker en deployment processen\n- Ervaring met testing (PHPUnit)\n- Kennis van security best practices\n- Goede communicatieve vaardigheden",
                'offer' => "- Competitief salaris van €4.200 - €6.000 per maand\n- Moderne tech stack en tools\n- Remote werk mogelijkheden\n- Uitstekende secundaire arbeidsvoorwaarden\n- Budget voor certificeringen en opleidingen\n- Inspirerende werkomgeving in Rotterdam Centrum\n- Doorgroeimogelijkheden naar Tech Lead",
                'application_instructions' => 'Stuur je CV en motivatiebrief naar tech@malibedrijf.nl. We vragen ook om voorbeelden van eerdere Laravel projecten die je hebt ontwikkeld.',
                'salary_range' => '€4.200 - €6.000 per maand',
                'start_date' => '2024-03-15',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'MALI-BE-002',
                'logo' => 'https://via.placeholder.com/150x50/FF5722/ffffff?text=MaliBedrijf',
            ],
            [
                'title' => 'UX Designer',
                'location' => 'Utrecht Centrum',
                'employment_type' => 'Parttime',
                'description' => 'Als UX Designer bij Mali bedrijf ben je verantwoordelijk voor het ontwerpen van gebruiksvriendelijke en aantrekkelijke interfaces. Je werkt samen met ons development team en draagt bij aan het verbeteren van de gebruikerservaring van onze producten.',
                'requirements' => "- Minimaal 3 jaar ervaring in UX design\n- Ervaring met Figma, Sketch of Adobe XD\n- Portfolio met relevante projecten\n- Kennis van user-centered design principes\n- Ervaring met user research en usability testing\n- Kennis van design systems en component libraries\n- Goede communicatieve vaardigheden\n- Ervaring met agile development processen",
                'offer' => "- Flexibele werktijden (24-32 uur per week)\n- Creatieve vrijheid en eigen verantwoordelijkheid\n- Moderne design tools en software\n- Budget voor design tools en opleidingen\n- Inspirerende werkomgeving in Utrecht Centrum\n- Mogelijkheid tot remote werk\n- Doorgroeimogelijkheden naar Senior UX Designer",
                'application_instructions' => 'Solliciteer met je portfolio, CV en motivatiebrief naar design@malibedrijf.nl. Zorg ervoor dat je portfolio recente UX projecten bevat.',
                'salary_range' => '€3.000 - €4.500 per maand (24-32 uur)',
                'start_date' => '2024-03-10',
                'working_hours' => '24-32 uur per week',
                'travel_expenses' => false,
                'remote_work' => true,
                'status' => 'In behandeling',
                'language' => 'Nederlands',
                'reference_number' => 'MALI-UX-003',
                'logo' => 'https://via.placeholder.com/150x50/FF5722/ffffff?text=MaliBedrijf',
            ],
            [
                'title' => 'Project Manager IT',
                'location' => 'Den Haag Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Als Project Manager IT ben je verantwoordelijk voor het leiden van IT projecten van begin tot eind. Je coördineert tussen verschillende teams, beheert project planningen en zorgt ervoor dat projecten binnen tijd en budget worden afgerond.',
                'requirements' => "- HBO/WO opleiding in IT, Business of aanverwant\n- Minimaal 5 jaar ervaring in IT project management\n- Ervaring met agile en waterfall methodieken\n- Kennis van project management tools (Jira, Asana, MS Project)\n- Uitstekende communicatieve en leiderschapsvaardigheden\n- Ervaring met stakeholder management\n- Kennis van risk management en quality assurance\n- Ervaring met budget management en resource planning",
                'offer' => "- Marktconform salaris van €4.500 - €6.500 per maand\n- Uitstekende secundaire arbeidsvoorwaarden\n- Doorgroeimogelijkheden naar Program Manager\n- Moderne werkomgeving in Den Haag Centrum\n- Budget voor project management certificeringen (PMP, PRINCE2)\n- Mogelijkheid tot remote werk\n- Inspirerende en uitdagende projecten",
                'application_instructions' => 'Solliciteer met je CV en motivatiebrief naar projects@malibedrijf.nl. We vragen ook om voorbeelden van succesvolle IT projecten die je hebt geleid.',
                'salary_range' => '€4.500 - €6.500 per maand',
                'start_date' => '2024-03-20',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'MALI-PM-004',
                'logo' => 'https://via.placeholder.com/150x50/FF5722/ffffff?text=MaliBedrijf',
            ],
            [
                'title' => 'Data Analyst',
                'location' => 'Eindhoven High Tech Campus',
                'employment_type' => 'Fulltime',
                'description' => 'Als Data Analyst bij Mali bedrijf analyseer je data om waardevolle inzichten te genereren voor het management. Je bouwt dashboards en rapporten die helpen bij strategische besluitvorming. Je werkt samen met verschillende afdelingen.',
                'requirements' => "- HBO/WO opleiding in Data Science, Statistiek, Wiskunde of aanverwant\n- Minimaal 3 jaar ervaring als Data Analyst\n- Ervaring met SQL en data querying\n- Kennis van data visualisatie tools (Tableau, Power BI, Looker)\n- Ervaring met Python of R voor data analysis\n- Kennis van statistische modellen en analyses\n- Uitstekende communicatieve vaardigheden\n- Ervaring met business intelligence en reporting",
                'offer' => "- Marktconform salaris van €3.500 - €5.000 per maand\n- Moderne data tools en technologieën\n- Remote werk mogelijkheden\n- Uitstekende secundaire arbeidsvoorwaarden\n- Budget voor data science opleidingen en certificeringen\n- Inspirerende werkomgeving op de High Tech Campus\n- Doorgroeimogelijkheden naar Senior Data Analyst",
                'application_instructions' => 'Solliciteer met je CV en motivatiebrief naar data@malibedrijf.nl. We vragen ook om voorbeelden van data analyses die je hebt uitgevoerd.',
                'salary_range' => '€3.500 - €5.000 per maand',
                'start_date' => '2024-03-25',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Gesloten',
                'language' => 'Nederlands',
                'reference_number' => 'MALI-DA-005',
                'logo' => 'https://via.placeholder.com/150x50/FF5722/ffffff?text=MaliBedrijf',
            ],
        ];
        
        foreach ($maliVacancies as $vacancyData) {
            $category = $categories->random();
            
            // Voeg company_id en category_id toe
            $vacancyData['company_id'] = $maliBedrijf->id;
            $vacancyData['category_id'] = $category->id;
            
            // Genereer een publicatiedatum (laatste 30 dagen)
            $vacancyData['publication_date'] = now()->subDays(rand(1, 30));
            
            // Genereer een sluitdatum (binnen 30 dagen)
            $vacancyData['closing_date'] = now()->addDays(rand(7, 30));
            
            // Genereer SEO velden
            $vacancyData['meta_title'] = $vacancyData['title'] . ' - Mali bedrijf';
            $vacancyData['meta_description'] = $this->generateMetaDescription($vacancyData);
            $vacancyData['meta_keywords'] = $this->generateMetaKeywords($vacancyData, $maliBedrijf, $category);

            Vacancy::create($vacancyData);
        }
        
        $this->command->info('5 vacatures voor Mali bedrijf succesvol aangemaakt!');
    }

    /**
     * Genereer meta description voor SEO
     */
    private function generateMetaDescription($vacancyData)
    {
        $description = $vacancyData['title'];
        
        if ($vacancyData['location']) {
            $description .= ' in ' . $vacancyData['location'];
        }
        
        if ($vacancyData['employment_type']) {
            $description .= ' - ' . $vacancyData['employment_type'];
        }
        
        if ($vacancyData['description']) {
            $description .= '. ' . Str::limit(strip_tags($vacancyData['description']), 120);
        }
        
        return Str::limit($description, 160);
    }

    /**
     * Genereer meta keywords voor SEO
     */
    private function generateMetaKeywords($vacancyData, $company, $category)
    {
        $keywords = [];
        
        // Basis keywords
        $keywords[] = 'vacature';
        $keywords[] = 'werk';
        $keywords[] = 'baan';
        $keywords[] = 'sollicitatie';
        $keywords[] = 'carrière';
        
        // Titel keywords
        $titleWords = explode(' ', strtolower($vacancyData['title']));
        $keywords = array_merge($keywords, array_slice($titleWords, 0, 5));
        
        // Locatie
        if ($vacancyData['location']) {
            $keywords[] = strtolower($vacancyData['location']);
            $keywords[] = 'nederland';
        }
        
        // Werktype
        if ($vacancyData['employment_type']) {
            $keywords[] = strtolower($vacancyData['employment_type']);
        }
        
        // Categorie
        $keywords[] = strtolower($category->name);
        
        // Bedrijf
        $keywords[] = strtolower($company->name);
        
        // Remote werk
        if ($vacancyData['remote_work']) {
            $keywords[] = 'remote';
            $keywords[] = 'thuiswerken';
            $keywords[] = 'hybride';
        }
        
        // Salaris
        if ($vacancyData['salary_range']) {
            $keywords[] = 'salaris';
            $keywords[] = 'loon';
        }
        
        return implode(', ', array_unique($keywords));
    }
    
    /**
     * Maak extra vacatures aan voor meer diversiteit
     */
    private function createExtraVacancies($companies, $categories)
    {
        $extraVacancies = [
            [
                'title' => 'Product Manager',
                'location' => 'Maastricht Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Als Product Manager ben je verantwoordelijk voor de ontwikkeling en uitvoering van productstrategieën. Je werkt samen met verschillende teams om producten te ontwikkelen die voldoen aan de behoeften van onze klanten.',
                'requirements' => "- HBO/WO opleiding in Business, Marketing of aanverwant\n- Minimaal 4 jaar ervaring in product management\n- Ervaring met agile development processen\n- Kennis van product analytics en user research\n- Uitstekende communicatieve en leiderschapsvaardigheden\n- Ervaring met stakeholder management\n- Kennis van product roadmap planning",
                'offer' => "- Marktconform salaris van €4.000 - €6.000 per maand\n- Uitstekende secundaire arbeidsvoorwaarden\n- Doorgroeimogelijkheden naar Senior Product Manager\n- Moderne werkomgeving in Maastricht Centrum\n- Budget voor product management certificeringen\n- Mogelijkheid tot remote werk",
                'application_instructions' => 'Solliciteer met je CV en motivatiebrief naar product@company.nl.',
                'salary_range' => '€4.000 - €6.000 per maand',
                'start_date' => '2024-04-01',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'PROD-001',
                'logo' => 'https://via.placeholder.com/150x50/9C27B0/ffffff?text=Company',
            ],
            [
                'title' => 'Sales Representative',
                'location' => 'Zwolle Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Wij zoeken een gemotiveerde Sales Representative die ons team kan versterken. Je bent verantwoordelijk voor het genereren van nieuwe leads en het onderhouden van bestaande klantrelaties.',
                'requirements' => "- HBO opleiding in Sales, Marketing of aanverwant\n- Minimaal 2 jaar ervaring in sales\n- Uitstekende communicatieve vaardigheden\n- Ervaring met CRM systemen\n- Resultaatgerichte instelling\n- Kennis van B2B sales processen\n- Ervaring met lead generation",
                'offer' => "- Basis salaris + commissie structuur\n- Onbeperkte verdienmogelijkheden\n- Uitstekende secundaire arbeidsvoorwaarden\n- Doorgroeimogelijkheden naar Account Manager\n- Moderne werkomgeving in Zwolle Centrum\n- Budget voor sales training en certificeringen",
                'application_instructions' => 'Solliciteer met je CV en motivatiebrief naar sales@company.nl.',
                'salary_range' => '€2.500 - €3.500 + commissie',
                'start_date' => '2024-04-15',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => false,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'SALES-002',
                'logo' => 'https://via.placeholder.com/150x50/FF9800/ffffff?text=Company',
            ],
            [
                'title' => 'HR Business Partner',
                'location' => 'Alkmaar Centrum',
                'employment_type' => 'Parttime',
                'description' => 'Als HR Business Partner ben je de schakel tussen HR en de business. Je adviseert managers over HR vraagstukken en draagt bij aan de ontwikkeling van medewerkers en teams.',
                'requirements' => "- HBO/WO opleiding in HR, Psychologie of aanverwant\n- Minimaal 3 jaar ervaring in HR\n- Kennis van arbeidsrecht en HR processen\n- Ervaring met recruitment en selectie\n- Uitstekende communicatieve vaardigheden\n- Ervaring met performance management\n- Kennis van HR systemen en tools",
                'offer' => "- Flexibele werktijden (24-32 uur per week)\n- Marktconform salaris\n- Uitstekende secundaire arbeidsvoorwaarden\n- Doorgroeimogelijkheden naar Senior HR Business Partner\n- Moderne werkomgeving in Alkmaar Centrum\n- Budget voor HR certificeringen",
                'application_instructions' => 'Solliciteer met je CV en motivatiebrief naar hr@company.nl.',
                'salary_range' => '€3.000 - €4.500 per maand (24-32 uur)',
                'start_date' => '2024-05-01',
                'working_hours' => '24-32 uur per week',
                'travel_expenses' => false,
                'remote_work' => true,
                'status' => 'In behandeling',
                'language' => 'Nederlands',
                'reference_number' => 'HR-003',
                'logo' => 'https://via.placeholder.com/150x50/4CAF50/ffffff?text=Company',
            ],
            [
                'title' => 'Customer Success Manager',
                'location' => 'Leeuwarden Centrum',
                'employment_type' => 'Fulltime',
                'description' => 'Als Customer Success Manager ben je verantwoordelijk voor het behouden en laten groeien van onze klanten. Je werkt nauw samen met klanten om ervoor te zorgen dat ze maximale waarde halen uit onze producten.',
                'requirements' => "- HBO opleiding in Business, Communicatie of aanverwant\n- Minimaal 3 jaar ervaring in customer success of account management\n- Uitstekende communicatieve vaardigheden\n- Ervaring met CRM systemen\n- Kennis van customer success metrics\n- Ervaring met upselling en cross-selling\n- Probleemoplossend vermogen",
                'offer' => "- Marktconform salaris van €3.500 - €5.000 per maand\n- Bonussysteem gebaseerd op customer success metrics\n- Uitstekende secundaire arbeidsvoorwaarden\n- Doorgroeimogelijkheden naar Senior Customer Success Manager\n- Moderne werkomgeving in Leeuwarden Centrum\n- Budget voor customer success training",
                'application_instructions' => 'Solliciteer met je CV en motivatiebrief naar customersuccess@company.nl.',
                'salary_range' => '€3.500 - €5.000 per maand',
                'start_date' => '2024-05-15',
                'working_hours' => '40 uur per week',
                'travel_expenses' => true,
                'remote_work' => true,
                'status' => 'Open',
                'language' => 'Nederlands',
                'reference_number' => 'CS-004',
                'logo' => 'https://via.placeholder.com/150x50/2196F3/ffffff?text=Company',
            ],
        ];
        
        foreach ($extraVacancies as $vacancyData) {
            $company = $companies->random();
            $category = $categories->random();
            
            // Voeg company_id en category_id toe
            $vacancyData['company_id'] = $company->id;
            $vacancyData['category_id'] = $category->id;
            
            // Genereer een publicatiedatum (laatste 30 dagen)
            $vacancyData['publication_date'] = now()->subDays(rand(1, 30));
            
            // Genereer een sluitdatum (binnen 30 dagen)
            $vacancyData['closing_date'] = now()->addDays(rand(7, 30));
            
            // Genereer SEO velden
            $vacancyData['meta_title'] = $vacancyData['title'] . ' - ' . $company->name;
            $vacancyData['meta_description'] = $this->generateMetaDescription($vacancyData);
            $vacancyData['meta_keywords'] = $this->generateMetaKeywords($vacancyData, $company, $category);

            Vacancy::create($vacancyData);
        }
        
        $this->command->info('4 extra vacatures succesvol aangemaakt!');
    }
}
