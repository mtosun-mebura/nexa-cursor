<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Candidate;
use Faker\Factory as Faker;

class CandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if candidates already exist
        if (Candidate::count() > 0) {
            $this->command->info('Kandidaten bestaan al, overslaan...');
            return;
        }

        $faker = Faker::create('nl_NL');

        // Create sample candidates
        $candidates = [
            [
                'first_name' => 'Jan',
                'last_name' => 'Jansen',
                'email' => 'jan.jansen@email.com',
                'phone' => '+31 6 12345678',
                'date_of_birth' => '1990-05-15',
                'address' => 'Hoofdstraat 123',
                'city' => 'Amsterdam',
                'postal_code' => '1000 AB',
                'country' => 'Nederland',
                'current_position' => 'Senior Developer',
                'desired_position' => 'Lead Developer',
                'experience_years' => 8,
                'education_level' => 'bachelor',
                'salary_expectation' => 75000.00,
                'availability' => 'immediate',
                'preferred_work_type' => 'full_time',
                'preferred_location' => 'Amsterdam, Utrecht',
                'skills' => ['PHP', 'Laravel', 'JavaScript', 'Vue.js', 'MySQL', 'Git'],
                'languages' => ['Nederlands', 'Engels'],
                'status' => 'active',
                'source' => 'linkedin',
                'consent_gdpr' => true,
                'consent_marketing' => true,
                'notes' => 'Zeer ervaren developer met uitstekende communicatieve vaardigheden.',
                'linkedin_url' => 'https://linkedin.com/in/jan-jansen',
                'website_url' => 'https://janjansen.dev'
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'de Vries',
                'email' => 'sarah.devries@email.com',
                'phone' => '+31 6 87654321',
                'date_of_birth' => '1995-08-22',
                'address' => 'Kerkstraat 45',
                'city' => 'Rotterdam',
                'postal_code' => '3000 CD',
                'country' => 'Nederland',
                'current_position' => 'UX Designer',
                'desired_position' => 'Senior UX Designer',
                'experience_years' => 4,
                'education_level' => 'master',
                'salary_expectation' => 65000.00,
                'availability' => '1_month',
                'preferred_work_type' => 'hybrid',
                'preferred_location' => 'Rotterdam, Den Haag',
                'skills' => ['Figma', 'Adobe XD', 'User Research', 'Prototyping', 'Design Systems'],
                'languages' => ['Nederlands', 'Engels', 'Duits'],
                'status' => 'pending',
                'source' => 'website',
                'consent_gdpr' => true,
                'consent_marketing' => false,
                'notes' => 'Creatieve designer met oog voor detail en gebruikerservaring.',
                'linkedin_url' => 'https://linkedin.com/in/sarah-devries'
            ],
            [
                'first_name' => 'Mohammed',
                'last_name' => 'Ahmed',
                'email' => 'm.ahmed@email.com',
                'phone' => '+31 6 11223344',
                'date_of_birth' => '1988-12-03',
                'address' => 'Industrieweg 78',
                'city' => 'Eindhoven',
                'postal_code' => '5600 EF',
                'country' => 'Nederland',
                'current_position' => 'Project Manager',
                'desired_position' => 'Program Manager',
                'experience_years' => 12,
                'education_level' => 'master',
                'salary_expectation' => 85000.00,
                'availability' => '3_months',
                'preferred_work_type' => 'full_time',
                'preferred_location' => 'Eindhoven, Tilburg',
                'skills' => ['Project Management', 'Agile', 'Scrum', 'Stakeholder Management', 'Risk Management'],
                'languages' => ['Nederlands', 'Engels', 'Arabisch'],
                'status' => 'active',
                'source' => 'referral',
                'consent_gdpr' => true,
                'consent_marketing' => true,
                'notes' => 'Ervaren projectmanager met sterke leiderschapskwaliteiten.',
                'linkedin_url' => 'https://linkedin.com/in/mohammed-ahmed'
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Bakker',
                'email' => 'lisa.bakker@email.com',
                'phone' => '+31 6 55667788',
                'date_of_birth' => '1992-03-18',
                'address' => 'Molenlaan 12',
                'city' => 'Groningen',
                'postal_code' => '9700 GH',
                'country' => 'Nederland',
                'current_position' => 'Marketing Specialist',
                'desired_position' => 'Marketing Manager',
                'experience_years' => 6,
                'education_level' => 'bachelor',
                'salary_expectation' => 60000.00,
                'availability' => '2_weeks',
                'preferred_work_type' => 'remote',
                'preferred_location' => 'Groningen, Friesland',
                'skills' => ['Digital Marketing', 'SEO', 'Google Ads', 'Social Media', 'Analytics'],
                'languages' => ['Nederlands', 'Engels'],
                'status' => 'hired',
                'source' => 'jobboard',
                'consent_gdpr' => true,
                'consent_marketing' => true,
                'notes' => 'Succesvol aangenomen voor marketing manager positie.',
                'linkedin_url' => 'https://linkedin.com/in/lisa-bakker'
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Visser',
                'email' => 't.visser@email.com',
                'phone' => '+31 6 99887766',
                'date_of_birth' => '1997-07-25',
                'address' => 'Schoolstraat 67',
                'city' => 'Den Haag',
                'postal_code' => '2500 GH',
                'country' => 'Nederland',
                'current_position' => 'Junior Developer',
                'desired_position' => 'Medior Developer',
                'experience_years' => 2,
                'education_level' => 'bachelor',
                'salary_expectation' => 45000.00,
                'availability' => 'immediate',
                'preferred_work_type' => 'full_time',
                'preferred_location' => 'Den Haag, Leiden',
                'skills' => ['JavaScript', 'React', 'Node.js', 'MongoDB', 'Docker'],
                'languages' => ['Nederlands', 'Engels'],
                'status' => 'rejected',
                'source' => 'website',
                'consent_gdpr' => true,
                'consent_marketing' => false,
                'notes' => 'Goede technische vaardigheden maar nog te weinig ervaring voor de gewenste positie.',
                'linkedin_url' => 'https://linkedin.com/in/thomas-visser'
            ]
        ];

        foreach ($candidates as $candidateData) {
            Candidate::create($candidateData);
        }

        // Create additional random candidates
        for ($i = 0; $i < 15; $i++) {
            $skills = $faker->randomElements([
                'PHP', 'Laravel', 'JavaScript', 'Vue.js', 'React', 'Node.js', 'Python', 'Java', 'C#', 'SQL',
                'AWS', 'Docker', 'Kubernetes', 'Git', 'CI/CD', 'Agile', 'Scrum', 'Project Management',
                'UX Design', 'UI Design', 'Figma', 'Adobe Creative Suite', 'Digital Marketing', 'SEO',
                'Google Ads', 'Social Media', 'Content Creation', 'Data Analysis', 'Machine Learning'
            ], $faker->numberBetween(3, 8));

            $languages = $faker->randomElements([
                'Nederlands', 'Engels', 'Duits', 'Frans', 'Spaans', 'Italiaans', 'Arabisch', 'Mandarijn'
            ], $faker->numberBetween(1, 3));

            Candidate::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'date_of_birth' => $faker->date('Y-m-d', '-18 years'),
                'address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'postal_code' => $faker->postcode(),
                'country' => 'Nederland',
                'current_position' => $faker->jobTitle(),
                'desired_position' => $faker->jobTitle(),
                'experience_years' => $faker->numberBetween(0, 20),
                'education_level' => $faker->randomElement(['high_school', 'vocational', 'bachelor', 'master', 'phd']),
                'salary_expectation' => $faker->randomFloat(2, 30000, 120000),
                'availability' => $faker->randomElement(['immediate', '2_weeks', '1_month', '3_months', 'custom']),
                'preferred_work_type' => $faker->randomElement(['full_time', 'part_time', 'freelance', 'contract', 'hybrid', 'remote']),
                'preferred_location' => $faker->city() . ', ' . $faker->city(),
                'skills' => $skills,
                'languages' => $languages,
                'status' => $faker->randomElement(['pending', 'active', 'rejected', 'hired']),
                'source' => $faker->randomElement(['website', 'linkedin', 'referral', 'jobboard', 'other']),
                'consent_gdpr' => $faker->boolean(90),
                'consent_marketing' => $faker->boolean(60),
                'notes' => $faker->optional(0.7)->realText(100),
                'linkedin_url' => $faker->optional(0.8)->url(),
                'website_url' => $faker->optional(0.3)->url(),
                'cover_letter' => $faker->optional(0.6)->realText(300)
            ]);
        }

        $this->command->info('Kandidaten succesvol aangemaakt!');
    }
}





