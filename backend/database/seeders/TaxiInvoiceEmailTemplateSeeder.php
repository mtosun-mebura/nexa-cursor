<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Support\EmailCardHtml;
use Illuminate\Database\Seeder;

class TaxiInvoiceEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $html = $this->html();
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

        EmailCardHtml::upgradeTypeToCardLayout('invoice', fn () => $html);

        $this->command?->info('Factuur e-mailtemplate(s) bijgewerkt met huisstijl en BTW-opsplitsing.');
    }

    private function html(): string
    {
        $body = <<<'HTML'
<p style="margin:0 0 16px;font-size:16px;">Beste {{ CUSTOMER_NAME }},</p>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
    In de bijlage vindt u uw factuur <strong>{{ INVOICE_NUMBER }}</strong> van {{ INVOICE_DATE }}.
</p>
<p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Overzicht bedragen</p>
{{ INVOICE_AMOUNTS_HTML }}
<p style="margin:16px 0 0;font-size:15px;line-height:1.6;">Met vriendelijke groet,<br>{{ COMPANY_NAME }}</p>
HTML;

        return EmailCardHtml::wrap(
            'Factuur',
            'Factuur {{ INVOICE_NUMBER }}',
            $body,
            EmailCardHtml::companyLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
            '{{ COMPANY_NAME }}',
        );
    }
}
