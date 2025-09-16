<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verwijder bestaande templates
        EmailTemplate::truncate();
        $this->command->info('Bestaande e-mail templates verwijderd.');

        // Haal bedrijven op
        $companies = \App\Models\Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->warn('Geen bedrijven gevonden. Maak eerst bedrijven aan.');
            return;
        }

        // Maak 5 templates voor elk bedrijf
        foreach ($companies as $company) {
            $this->createTemplatesForCompany($company);
        }
        
        $this->command->info('E-mail templates succesvol aangemaakt voor alle bedrijven!');
    }

    /**
     * Maak 5 e-mail templates aan voor een specifiek bedrijf
     */
    private function createTemplatesForCompany($company)
    {
        $templates = [
            [
                'name' => 'Welkom nieuwe gebruiker - ' . $company->name,
                'subject' => 'Welkom bij ' . $company->name . '!',
                'type' => 'welcome',
                'description' => 'Welkomstmail voor nieuwe gebruikers van ' . $company->name,
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welkom bij Nexa Skillmatching</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Welkom bij ' . $company->name . '!</h1>
        <p>Beste {{ $user_name }},</p>
        <p>Hartelijk dank voor je registratie bij ' . $company->name . '. We zijn blij dat je deel uitmaakt van ons platform!</p>
        <p>Met je account kun je:</p>
        <ul>
            <li>Vacatures bekijken en zoeken</li>
            <li>Je profiel beheren</li>
            <li>Matches ontvangen</li>
            <li>Sollicitaties indienen</li>
        </ul>
        <p>Veel succes met je zoektocht naar de perfecte baan!</p>
        <p>Met vriendelijke groet,<br>Het Nexa Team</p>
    </div>
</body>
</html>',
                'text_content' => 'Welkom bij Nexa Skillmatching!

Beste {{ $user_name }},

Hartelijk dank voor je registratie bij Nexa Skillmatching. We zijn blij dat je deel uitmaakt van ons platform!

Met je account kun je:
- Vacatures bekijken en zoeken
- Je profiel beheren
- Matches ontvangen
- Sollicitaties indienen

Veel succes met je zoektocht naar de perfecte baan!

Met vriendelijke groet,
Het Nexa Team',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Nieuwe match gevonden - ' . $company->name,
                'subject' => 'Nieuwe match gevonden voor je profiel!',
                'type' => 'match',
                'description' => 'Notificatie wanneer er een nieuwe match is gevonden',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nieuwe Match Gevonden</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Nieuwe Match Gevonden!</h1>
        <p>Beste {{ $user_name }},</p>
        <p>Geweldig nieuws! We hebben een nieuwe match gevonden die perfect bij je profiel past.</p>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>{{ $vacancy_title }}</h3>
            <p><strong>Bedrijf:</strong> {{ $company_name }}</p>
            <p><strong>Locatie:</strong> {{ $vacancy_location }}</p>
            <p><strong>Match Score:</strong> {{ $match_score }}%</p>
        </div>
        <p>Bekijk de vacature en solliciteer direct via je dashboard!</p>
        <a href="{{ $vacancy_url }}" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Bekijk Vacature</a>
        <p>Met vriendelijke groet,<br>Het Nexa Team</p>
    </div>
</body>
</html>',
                'text_content' => 'Nieuwe Match Gevonden!

Beste {{ $user_name }},

Geweldig nieuws! We hebben een nieuwe match gevonden die perfect bij je profiel past.

Vacature: {{ $vacancy_title }}
Bedrijf: {{ $company_name }}
Locatie: {{ $vacancy_location }}
Match Score: {{ $match_score }}%

Bekijk de vacature en solliciteer direct via je dashboard!

Met vriendelijke groet,
Het Nexa Team',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Interview uitgenodigd - ' . $company->name,
                'subject' => 'Uitnodiging voor interview - {{ $vacancy_title }}',
                'type' => 'interview',
                'description' => 'Uitnodiging voor een interview',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Interview Uitnodiging</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Interview Uitnodiging</h1>
        <p>Beste {{ $candidate_name }},</p>
        <p>Gefeliciteerd! Je bent uitgenodigd voor een interview voor de functie <strong>{{ $vacancy_title }}</strong> bij {{ $company_name }}.</p>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Interview Details:</h3>
            <p><strong>Datum:</strong> {{ $interview_date }}</p>
            <p><strong>Tijd:</strong> {{ $interview_time }}</p>
            <p><strong>Locatie:</strong> {{ $interview_location }}</p>
            <p><strong>Type:</strong> {{ $interview_type }}</p>
        </div>
        <p>Zorg ervoor dat je op tijd aanwezig bent en bereid je goed voor op het interview.</p>
        <p>Veel succes!</p>
        <p>Met vriendelijke groet,<br>{{ $company_name }}</p>
    </div>
</body>
</html>',
                'text_content' => 'Interview Uitnodiging

Beste {{ $candidate_name }},

Gefeliciteerd! Je bent uitgenodigd voor een interview voor de functie {{ $vacancy_title }} bij {{ $company_name }}.

Interview Details:
- Datum: {{ $interview_date }}
- Tijd: {{ $interview_time }}
- Locatie: {{ $interview_location }}
- Type: {{ $interview_type }}

Zorg ervoor dat je op tijd aanwezig bent en bereid je goed voor op het interview.

Veel succes!

Met vriendelijke groet,
{{ $company_name }}',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Sollicitatie ontvangen - ' . $company->name,
                'subject' => 'Nieuwe sollicitatie ontvangen - {{ $vacancy_title }}',
                'type' => 'application_received',
                'description' => 'Bevestiging dat een sollicitatie is ontvangen',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sollicitatie Ontvangen</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Sollicitatie Ontvangen</h1>
        <p>Beste {{ $candidate_name }},</p>
        <p>Bedankt voor je sollicitatie op de functie <strong>{{ $vacancy_title }}</strong> bij {{ $company_name }}.</p>
        <p>We hebben je sollicitatie ontvangen en zullen deze zo spoedig mogelijk beoordelen. Je ontvangt binnen 5 werkdagen een reactie van ons.</p>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Sollicitatie Details:</h3>
            <p><strong>Functie:</strong> {{ $vacancy_title }}</p>
            <p><strong>Bedrijf:</strong> {{ $company_name }}</p>
            <p><strong>Sollicitatiedatum:</strong> {{ $application_date }}</p>
            <p><strong>Referentienummer:</strong> {{ $application_reference }}</p>
        </div>
        <p>We houden je op de hoogte van de verdere procedure.</p>
        <p>Met vriendelijke groet,<br>{{ $company_name }}</p>
    </div>
</body>
</html>',
                'text_content' => 'Sollicitatie Ontvangen

Beste {{ $candidate_name }},

Bedankt voor je sollicitatie op de functie {{ $vacancy_title }} bij {{ $company_name }}.

We hebben je sollicitatie ontvangen en zullen deze zo spoedig mogelijk beoordelen. Je ontvangt binnen 5 werkdagen een reactie van ons.

Sollicitatie Details:
- Functie: {{ $vacancy_title }}
- Bedrijf: {{ $company_name }}
- Sollicitatiedatum: {{ $application_date }}
- Referentienummer: {{ $application_reference }}

We houden je op de hoogte van de verdere procedure.

Met vriendelijke groet,
{{ $company_name }}',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Account gedeactiveerd - ' . $company->name,
                'subject' => 'Je account is gedeactiveerd',
                'type' => 'account_deactivated',
                'description' => 'Notificatie wanneer een account wordt gedeactiveerd',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Gedeactiveerd</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #f44336;">Account Gedeactiveerd</h1>
        <p>Beste {{ $user_name }},</p>
        <p>Je account bij Nexa Skillmatching is gedeactiveerd op {{ $deactivation_date }}.</p>
        <p>Reden: {{ $deactivation_reason }}</p>
        <p>Als je denkt dat dit een vergissing is, neem dan contact met ons op via support@nexa.com.</p>
        <p>Met vriendelijke groet,<br>Het Nexa Team</p>
    </div>
</body>
</html>',
                'text_content' => 'Account Gedeactiveerd

Beste {{ $user_name }},

Je account bij Nexa Skillmatching is gedeactiveerd op {{ $deactivation_date }}.

Reden: {{ $deactivation_reason }}

Als je denkt dat dit een vergissing is, neem dan contact met ons op via support@nexa.com.

Met vriendelijke groet,
Het Nexa Team',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Wachtwoord reset - ' . $company->name,
                'subject' => 'Wachtwoord reset aanvraag',
                'type' => 'password_reset',
                'description' => 'E-mail voor wachtwoord reset',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord Reset</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Wachtwoord Reset</h1>
        <p>Beste {{ $user_name }},</p>
        <p>Je hebt een wachtwoord reset aangevraagd voor je Nexa Skillmatching account.</p>
        <p>Klik op de onderstaande link om je wachtwoord te resetten:</p>
        <a href="{{ $reset_url }}" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0;">Reset Wachtwoord</a>
        <p>Deze link is 24 uur geldig.</p>
        <p>Als je deze aanvraag niet hebt gedaan, kun je deze e-mail negeren.</p>
        <p>Met vriendelijke groet,<br>Het Nexa Team</p>
    </div>
</body>
</html>',
                'text_content' => 'Wachtwoord Reset

Beste {{ $user_name }},

Je hebt een wachtwoord reset aangevraagd voor je Nexa Skillmatching account.

Ga naar de volgende link om je wachtwoord te resetten:
{{ $reset_url }}

Deze link is 24 uur geldig.

Als je deze aanvraag niet hebt gedaan, kun je deze e-mail negeren.

Met vriendelijke groet,
Het Nexa Team',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Vacature gepubliceerd - ' . $company->name,
                'subject' => 'Nieuwe vacature gepubliceerd - {{ $vacancy_title }}',
                'type' => 'vacancy_published',
                'description' => 'Notificatie wanneer een vacature wordt gepubliceerd',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nieuwe Vacature Gepubliceerd</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Nieuwe Vacature Gepubliceerd</h1>
        <p>Beste {{ $user_name }},</p>
        <p>Er is een nieuwe vacature gepubliceerd die mogelijk interessant voor je is!</p>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>{{ $vacancy_title }}</h3>
            <p><strong>Bedrijf:</strong> {{ $company_name }}</p>
            <p><strong>Locatie:</strong> {{ $vacancy_location }}</p>
            <p><strong>Type:</strong> {{ $employment_type }}</p>
            <p><strong>Salaris:</strong> {{ $salary_range }}</p>
        </div>
        <p>Bekijk de volledige vacature en solliciteer direct!</p>
        <a href="{{ $vacancy_url }}" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Bekijk Vacature</a>
        <p>Met vriendelijke groet,<br>Het Nexa Team</p>
    </div>
</body>
</html>',
                'text_content' => 'Nieuwe Vacature Gepubliceerd

Beste {{ $user_name }},

Er is een nieuwe vacature gepubliceerd die mogelijk interessant voor je is!

{{ $vacancy_title }}
Bedrijf: {{ $company_name }}
Locatie: {{ $vacancy_location }}
Type: {{ $employment_type }}
Salaris: {{ $salary_range }}

Bekijk de volledige vacature en solliciteer direct!

Met vriendelijke groet,
Het Nexa Team',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Interview bevestigd - ' . $company->name,
                'subject' => 'Interview bevestigd - {{ $vacancy_title }}',
                'type' => 'interview_confirmed',
                'description' => 'Bevestiging van een interview',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Interview Bevestigd</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Interview Bevestigd</h1>
        <p>Beste {{ $candidate_name }},</p>
        <p>Je interview voor de functie <strong>{{ $vacancy_title }}</strong> bij {{ $company_name }} is bevestigd.</p>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Interview Details:</h3>
            <p><strong>Datum:</strong> {{ $interview_date }}</p>
            <p><strong>Tijd:</strong> {{ $interview_time }}</p>
            <p><strong>Locatie:</strong> {{ $interview_location }}</p>
            <p><strong>Interviewer:</strong> {{ $interviewer_name }}</p>
            <p><strong>Contact:</strong> {{ $interviewer_email }}</p>
        </div>
        <p>Zorg ervoor dat je op tijd aanwezig bent. Succes!</p>
        <p>Met vriendelijke groet,<br>{{ $company_name }}</p>
    </div>
</body>
</html>',
                'text_content' => 'Interview Bevestigd

Beste {{ $candidate_name }},

Je interview voor de functie {{ $vacancy_title }} bij {{ $company_name }} is bevestigd.

Interview Details:
- Datum: {{ $interview_date }}
- Tijd: {{ $interview_time }}
- Locatie: {{ $interview_location }}
- Interviewer: {{ $interviewer_name }}
- Contact: {{ $interviewer_email }}

Zorg ervoor dat je op tijd aanwezig bent. Succes!

Met vriendelijke groet,
{{ $company_name }}',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Sollicitatie afgewezen - ' . $company->name,
                'subject' => 'Reactie op je sollicitatie - {{ $vacancy_title }}',
                'type' => 'application_rejected',
                'description' => 'Notificatie wanneer een sollicitatie wordt afgewezen',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sollicitatie Reactie</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #f44336;">Reactie op je Sollicitatie</h1>
        <p>Beste {{ $candidate_name }},</p>
        <p>Bedankt voor je interesse in de functie <strong>{{ $vacancy_title }}</strong> bij {{ $company_name }}.</p>
        <p>Na zorgvuldige overweging hebben we besloten om niet verder te gaan met je sollicitatie voor deze specifieke functie.</p>
        <p>Dit betekent niet dat je profiel niet interessant is. We bewaren je gegevens en nemen contact met je op als er in de toekomst een passende functie beschikbaar komt.</p>
        <p>We wensen je veel succes met je verdere zoektocht!</p>
        <p>Met vriendelijke groet,<br>{{ $company_name }}</p>
    </div>
</body>
</html>',
                'text_content' => 'Reactie op je Sollicitatie

Beste {{ $candidate_name }},

Bedankt voor je interesse in de functie {{ $vacancy_title }} bij {{ $company_name }}.

Na zorgvuldige overweging hebben we besloten om niet verder te gaan met je sollicitatie voor deze specifieke functie.

Dit betekent niet dat je profiel niet interessant is. We bewaren je gegevens en nemen contact met je op als er in de toekomst een passende functie beschikbaar komt.

We wensen je veel succes met je verdere zoektocht!

Met vriendelijke groet,
{{ $company_name }}',
                'is_active' => true,
                'company_id' => $company->id
            ],
            [
                'name' => 'Account geactiveerd - ' . $company->name,
                'subject' => 'Je account is geactiveerd',
                'type' => 'account_activated',
                'description' => 'Notificatie wanneer een account wordt geactiveerd',
                'html_content' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account Geactiveerd</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4caf50;">Account Geactiveerd</h1>
        <p>Beste {{ $user_name }},</p>
        <p>Geweldig nieuws! Je account bij Nexa Skillmatching is geactiveerd op {{ $activation_date }}.</p>
        <p>Je kunt nu volledig gebruik maken van alle functies van ons platform:</p>
        <ul>
            <li>Vacatures bekijken en zoeken</li>
            <li>Je profiel beheren</li>
            <li>Matches ontvangen</li>
            <li>Sollicitaties indienen</li>
            <li>Interviews plannen</li>
        </ul>
        <p>Welkom bij Nexa Skillmatching!</p>
        <a href="{{ $dashboard_url }}" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ga naar Dashboard</a>
        <p>Met vriendelijke groet,<br>Het Nexa Team</p>
    </div>
</body>
</html>',
                'text_content' => 'Account Geactiveerd

Beste {{ $user_name }},

Geweldig nieuws! Je account bij Nexa Skillmatching is geactiveerd op {{ $activation_date }}.

Je kunt nu volledig gebruik maken van alle functies van ons platform:
- Vacatures bekijken en zoeken
- Je profiel beheren
- Matches ontvangen
- Sollicitaties indienen
- Interviews plannen

Welkom bij Nexa Skillmatching!

Met vriendelijke groet,
Het Nexa Team',
                'is_active' => true,
                'company_id' => $company->id
            ]
        ];

        foreach ($templates as $template) {
            EmailTemplate::create($template);
        }
    }
}
