<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class TaxiInvoiceEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="UTF-8"><title>Factuur</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0;">
<div style="max-width: 600px; margin: 0 auto; padding: 24px;">
    <div style="margin-bottom: 24px;">{{ COMPANY_LOGO }}</div>
    <p style="margin: 0 0 8px; font-size: 13px; color: #64748b; text-align: left;">{{ COMPANY_NAME }}</p>
    <p style="margin: 0 0 24px; font-size: 12px; color: #64748b; white-space: pre-line; text-align: left;">{{ COMPANY_ADDRESS }}</p>
    <h1 style="font-size: 20px; margin: 0 0 16px; text-align: left;">Factuur {{ INVOICE_NUMBER }}</h1>
    <p style="text-align: left;">Beste {{ CUSTOMER_NAME }},</p>
    <p style="text-align: left;">In de bijlage vindt u uw factuur <strong>{{ INVOICE_NUMBER }}</strong> van {{ INVOICE_DATE }}.</p>
    <p style="text-align: left; margin: 0 0 8px;">Overzicht bedragen:</p>
    {{ INVOICE_AMOUNTS_HTML }}
    <p style="text-align: left;">Met vriendelijke groet,<br>{{ COMPANY_NAME }}</p>
</div>
</body>
</html>
HTML;

        $text = <<<'TEXT'
Beste {{ CUSTOMER_NAME }},

In de bijlage vindt u factuur {{ INVOICE_NUMBER }} van {{ INVOICE_DATE }}.

{{ INVOICE_AMOUNTS_TEXT }}

Met vriendelijke groet,
{{ COMPANY_NAME }}
TEXT;

        $payload = [
            'name' => 'Factuur (Nexa Taxi)',
            'subject' => 'Factuur {{ INVOICE_NUMBER }} – {{ COMPANY_NAME }}',
            'type' => 'invoice',
            'description' => 'E-mail bij versturen van een taxirit-factuur als PDF-bijlage.',
            'html_content' => $html,
            'text_content' => $text,
            'is_active' => true,
        ];

        EmailTemplate::query()->updateOrCreate(
            ['type' => 'invoice', 'company_id' => null],
            $payload
        );

        EmailTemplate::query()
            ->where('type', 'invoice')
            ->update([
                'html_content' => $html,
                'text_content' => $text,
            ]);

        $this->command?->info('Factuur e-mailtemplate(s) bijgewerkt met BTW-opsplitsing.');
    }
}
