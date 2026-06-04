<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class TaxiRideAcceptedEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="UTF-8"><title>Rit geaccepteerd</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0;">
<div style="max-width: 600px; margin: 0 auto; padding: 24px;">
    <div style="margin-bottom: 24px;">{{ COMPANY_LOGO }}</div>
    <p style="margin: 0 0 8px; font-size: 13px; color: #64748b; text-align: left;">{{ COMPANY_NAME }}</p>
    <h1 style="font-size: 20px; margin: 0 0 16px; text-align: left;">Uw taxirit is geaccepteerd</h1>
    <p style="text-align: left;">Beste {{ CUSTOMER_NAME }},</p>
    <p style="text-align: left;">Goed nieuws: uw rit is geaccepteerd door <strong>{{ DRIVER_NAME }}</strong>.</p>
    <p style="text-align: left;">
        <strong>Ophaalmoment:</strong> {{ PICKUP_AT }}<br>
        <strong>Ophalen:</strong> {{ PICKUP_ADDRESS }}<br>
        <strong>Afzetten:</strong> {{ DROPOFF_ADDRESS }}
    </p>
    <p style="text-align: left;">Vragen? Neem contact op via {{ COMPANY_PHONE }} of {{ COMPANY_EMAIL }}.</p>
    <p style="text-align: left;">Met vriendelijke groet,<br>{{ COMPANY_NAME }}</p>
</div>
</body>
</html>
HTML;

        $text = <<<'TEXT'
Beste {{ CUSTOMER_NAME }},

Uw taxirit is geaccepteerd door {{ DRIVER_NAME }}.

Ophaalmoment: {{ PICKUP_AT }}
Ophalen: {{ PICKUP_ADDRESS }}
Afzetten: {{ DROPOFF_ADDRESS }}

Vragen? {{ COMPANY_PHONE }} / {{ COMPANY_EMAIL }}

Met vriendelijke groet,
{{ COMPANY_NAME }}
TEXT;

        EmailTemplate::query()->updateOrCreate(
            ['type' => 'taxi_ride_accepted', 'company_id' => null],
            [
                'name' => 'Rit geaccepteerd (Nexa Taxi)',
                'subject' => 'Uw taxirit is geaccepteerd – {{ COMPANY_NAME }}',
                'description' => 'E-mail naar de klant wanneer een chauffeur de rit accepteert.',
                'html_content' => $html,
                'text_content' => $text,
                'is_active' => true,
            ]
        );

        app(\App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeEmailTemplateService::class)
            ->ensureGlobalTemplateExists();
    }
}
