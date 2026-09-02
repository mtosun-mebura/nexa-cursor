<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Support\NexaBranding;

class SaasBillingStartEmailTemplateService
{
    public const TYPE = 'saas_billing_start';

    public const TEMPLATE_NAME = 'Eerste betaling NEXA-abonnement';

    public const FROM_NAME = 'NEXA Suite';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'PACKAGE_NAME' => 'Pakketnaam',
            'START_DATE' => 'Ingangsdatum abonnement',
            'FIRST_AMOUNT' => 'Eerste bedrag (incl. btw)',
            'MONTHLY_AMOUNT' => 'Maandprijs daarna (excl. btw)',
            'RECURRING_FROM' => 'Vanaf wanneer het volle maandbedrag',
            'INVOICE_NUMBER' => 'Factuurnummer',
            'PAYMENT_URL' => 'Link naar de eerste betaling',
            'NEXA_LOGO' => 'Nexa-logo (HTML, linksboven)',
        ];
    }

    public function ensureExists(): EmailTemplate
    {
        $existing = EmailTemplate::query()
            ->where('type', self::TYPE)
            ->whereNull('company_id')
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->is_active = true;
            }
            if ($this->storedHtmlNeedsRefresh((string) $existing->html_content)) {
                $existing->subject = 'Eerste betaling NEXA: abonnement start {{ START_DATE }} — {{ COMPANY_NAME }}';
                $existing->html_content = $this->html();
                $existing->text_content = $this->text();
            }
            $existing->save();

            return $existing;
        }

        return EmailTemplate::query()->create([
            'type' => self::TYPE,
            'company_id' => null,
            'name' => self::TEMPLATE_NAME,
            'subject' => 'Eerste betaling NEXA: abonnement start {{ START_DATE }} — {{ COMPANY_NAME }}',
            'description' => 'Eerste factuur na de proef: ingangsdatum, totaalbedrag, factuur-PDF in de bijlage, betaalknop en daarna automatische incasso.',
            'html_content' => $this->html(),
            'text_content' => $this->text(),
            'is_active' => true,
            'recipient_type' => 'email',
            'recipient_email' => null,
        ]);
    }

    public function resolveActive(): EmailTemplate
    {
        return $this->ensureExists();
    }

    /**
     * @param  array{
     *     period_lines?: array<int, array{label: string, amount: float}>,
     *     discount_amount?: float,
     *     first_amount_excl?: float,
     *     first_amount_incl?: float,
     *     tax_amount?: float,
     *     tax_percent?: float
     * }  $presentation
     */
    public function amountTableHtml(array $presentation): string
    {
        $pricing = app(NexaPricingService::class);
        $lines = $presentation['period_lines'] ?? [];
        $discount = round((float) ($presentation['discount_amount'] ?? 0), 2);
        $subtotal = round((float) ($presentation['first_amount_excl'] ?? 0), 2);
        $taxAmount = round((float) ($presentation['tax_amount'] ?? 0), 2);
        $total = round((float) ($presentation['first_amount_incl'] ?? 0), 2);
        $taxPercent = (float) ($presentation['tax_percent'] ?? 21);
        $taxLabel = abs($taxPercent - (int) $taxPercent) < 0.001
            ? (string) (int) $taxPercent
            : rtrim(rtrim(number_format($taxPercent, 2, ',', ''), '0'), ',');

        $cellLeft = 'padding:12px 12px 12px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:14px;line-height:1.45;vertical-align:top;';
        $cellRight = 'padding:12px 0 12px 12px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:14px;line-height:1.45;text-align:right;white-space:nowrap;vertical-align:top;';
        $mutedLeft = 'padding:10px 12px 10px 0;color:#64748b;font-size:14px;line-height:1.45;vertical-align:top;';
        $mutedRight = 'padding:10px 0 10px 12px;color:#64748b;font-size:14px;line-height:1.45;text-align:right;white-space:nowrap;vertical-align:top;';

        $rows = '';
        foreach ($lines as $line) {
            $label = e((string) ($line['label'] ?? ''));
            $amount = $pricing->displayAmount(number_format((float) ($line['amount'] ?? 0), 2, '.', ''));
            $rows .= '<tr>'
                .'<td style="'.$cellLeft.'">'.$label.'</td>'
                .'<td style="'.$cellRight.'">'.e($amount).'</td>'
                .'</tr>';
        }

        if ($discount > 0) {
            $rows .= '<tr>'
                .'<td style="'.$cellLeft.'">Korting</td>'
                .'<td style="'.$cellRight.'">− '.e($pricing->displayAmount(number_format($discount, 2, '.', ''))).'</td>'
                .'</tr>';
        }

        return '<table role="presentation" width="100%" style="width:100%;border-collapse:collapse;margin:8px 0 24px;font-family:Arial,Helvetica,sans-serif;">'
            .'<tr>'
            .'<td style="padding:0 12px 8px 0;border-bottom:2px solid #0f172a;color:#64748b;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;font-weight:700;">Periode</td>'
            .'<td style="padding:0 0 8px 12px;border-bottom:2px solid #0f172a;color:#64748b;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;font-weight:700;text-align:right;">Bedrag excl. btw</td>'
            .'</tr>'
            .$rows
            .'<tr>'
            .'<td style="'.$mutedLeft.'padding-top:14px;">Subtotaal excl. btw</td>'
            .'<td style="'.$mutedRight.'padding-top:14px;">'.e($pricing->displayAmount(number_format($subtotal, 2, '.', ''))).'</td>'
            .'</tr>'
            .'<tr>'
            .'<td style="'.$mutedLeft.'">btw '.$taxLabel.'%</td>'
            .'<td style="'.$mutedRight.'">'.e($pricing->displayAmount(number_format($taxAmount, 2, '.', ''))).'</td>'
            .'</tr>'
            .'<tr>'
            .'<td style="padding:14px 12px 0 0;border-top:2px solid #0f172a;color:#0f172a;font-size:15px;font-weight:700;line-height:1.45;">Totaal incl. btw</td>'
            .'<td style="padding:14px 0 0 12px;border-top:2px solid #0f172a;color:#0f172a;font-size:16px;font-weight:700;line-height:1.45;text-align:right;white-space:nowrap;">'.e($pricing->displayAmount(number_format($total, 2, '.', ''))).'</td>'
            .'</tr>'
            .'</table>';
    }

    /**
     * @param  array{
     *     period_lines?: array<int, array{label: string, amount: float}>,
     *     discount_amount?: float,
     *     first_amount_excl?: float,
     *     first_amount_incl?: float,
     *     tax_amount?: float,
     *     tax_percent?: float
     * }  $presentation
     */
    public function amountTableText(array $presentation): string
    {
        $pricing = app(NexaPricingService::class);
        $lines = $presentation['period_lines'] ?? [];
        $discount = round((float) ($presentation['discount_amount'] ?? 0), 2);
        $subtotal = round((float) ($presentation['first_amount_excl'] ?? 0), 2);
        $taxAmount = round((float) ($presentation['tax_amount'] ?? 0), 2);
        $total = round((float) ($presentation['first_amount_incl'] ?? 0), 2);
        $taxPercent = (float) ($presentation['tax_percent'] ?? 21);
        $taxLabel = abs($taxPercent - (int) $taxPercent) < 0.001
            ? (string) (int) $taxPercent
            : rtrim(rtrim(number_format($taxPercent, 2, ',', ''), '0'), ',');

        $out = [];
        foreach ($lines as $line) {
            $amount = $pricing->displayAmount(number_format((float) ($line['amount'] ?? 0), 2, '.', ''));
            $out[] = trim((string) ($line['label'] ?? '')).': '.$amount;
        }
        if ($discount > 0) {
            $out[] = 'Korting: − '.$pricing->displayAmount(number_format($discount, 2, '.', ''));
        }
        $out[] = 'Subtotaal excl. btw: '.$pricing->displayAmount(number_format($subtotal, 2, '.', ''));
        $out[] = 'btw '.$taxLabel.'%: '.$pricing->displayAmount(number_format($taxAmount, 2, '.', ''));
        $out[] = 'Totaal incl. btw: '.$pricing->displayAmount(number_format($total, 2, '.', ''));

        return implode("\n", $out);
    }

    /**
     * @return array<string, string>
     */
    public function previewVariables(?Company $company = null): array
    {
        $companyName = $company?->name ?: 'Horizon Taxi';
        $presentation = $this->previewPresentation();
        $pricing = app(NexaPricingService::class);

        return array_merge(
            [
                'COMPANY_NAME' => e($companyName),
                'PACKAGE_NAME' => 'Pro',
                'START_DATE' => $presentation['start']->translatedFormat('j F Y'),
                'FIRST_AMOUNT' => $pricing->displayAmount(number_format($presentation['first_amount_incl'], 2, '.', '')),
                'MONTHLY_AMOUNT' => $pricing->displayAmount(number_format($presentation['monthly_amount'], 2, '.', '')),
                'RECURRING_FROM' => $presentation['recurring_from']->translatedFormat('j F Y'),
                'INVOICE_NUMBER' => 'NEXA-2026-0001',
                'PAYMENT_URL' => url('/admin/nexa-facturatie/facturen'),
            ],
            NexaBranding::emailLogoTemplateVariable()
        );
    }

    /**
     * @return array{bytes: string, filename: string, mime: string}|null
     */
    public function sampleInvoiceAttachment(?Company $company = null): ?array
    {
        $presentation = $this->previewPresentation();
        $previewCompany = $company ?? new Company([
            'name' => 'Horizon Taxi',
            'email' => 'facturatie@horizontaxi.nl',
            'street' => 'Voorbeeldstraat',
            'house_number' => '12',
            'postal_code' => '7511 AB',
            'city' => 'Enschede',
            'country' => 'Nederland',
        ]);
        $settings = \App\Models\PlatformBillingSetting::current();
        $paymentTermsDays = max(1, (int) $settings->payment_terms_days);
        $lineItems = [];
        foreach ($presentation['period_lines'] as $index => $line) {
            $daysNote = '';
            if ($index === 0 && $presentation['start']->day !== 1) {
                $daysInMonth = $presentation['start']->daysInMonth;
                $remaining = $daysInMonth - $presentation['start']->day + 1;
                $daysNote = ' ('.$remaining.'/'.$daysInMonth.' dagen)';
            }
            $lineItems[] = [
                'description' => 'Pro — '.$line['label'].$daysNote,
                'quantity' => 1,
                'unit_price' => $line['amount'],
                'total' => $line['amount'],
                'type' => 'subscription',
            ];
        }
        $preview = [
            'invoice_number' => 'NEXA-2026-0001',
            'billing_period' => $presentation['start']->format('Y-m'),
            'invoice_date' => $presentation['start']->toDateString(),
            'due_date' => $presentation['start']->copy()->addDays($paymentTermsDays)->toDateString(),
            'payment_terms_days' => $paymentTermsDays,
            'line_items' => $lineItems,
            'amount' => $presentation['first_amount_excl'],
            'tax_amount' => $presentation['tax_amount'],
            'total_amount' => $presentation['first_amount_incl'],
            'tax_rate' => $presentation['tax_percent'],
            'issuer' => $settings->issuerDetailsSnapshot(),
            'recipient' => $settings->recipientDetailsSnapshot($previewCompany),
            'payment_terms_text' => \App\Models\PlatformBillingSetting::invoicePaymentTermsTextForInvoice(
                new \App\Models\PlatformInvoice([
                    'payment_terms_days' => $paymentTermsDays,
                    'status' => 'sent',
                    'invoice_date' => $presentation['start']->toDateString(),
                    'due_date' => $presentation['start']->copy()->addDays($paymentTermsDays)->toDateString(),
                ])
            ),
        ];

        try {
            $bytes = app(\App\Services\PlatformBilling\PlatformInvoicePdfService::class)
                ->renderPreviewPdfBytes($previewCompany, $preview);
        } catch (\Throwable) {
            return null;
        }

        if ($bytes === '') {
            return null;
        }

        return [
            'bytes' => $bytes,
            'filename' => 'saas-factuur-NEXA-2026-0001.pdf',
            'mime' => 'application/pdf',
        ];
    }

    /**
     * @return array{
     *     start: \Carbon\Carbon,
     *     recurring_from: \Carbon\Carbon,
     *     period_lines: array<int, array{label: string, amount: float}>,
     *     first_amount_excl: float,
     *     first_amount_incl: float,
     *     tax_amount: float,
     *     tax_percent: float,
     *     monthly_amount: float
     * }
     */
    private function previewPresentation(): array
    {
        $start = now()->startOfMonth()->addDays(3);
        $daysInMonth = $start->daysInMonth;
        $remaining = $daysInMonth - $start->day + 1;
        $monthly = 149.00;
        $prorata = round($monthly * ($remaining / $daysInMonth), 2);
        $excl = round($prorata + $monthly, 2);
        $taxPercent = 21.0;
        $taxAmount = round($excl * ($taxPercent / 100), 2);

        return [
            'start' => $start,
            'recurring_from' => $start->copy()->addMonthsNoOverflow(2)->startOfMonth(),
            'period_lines' => [
                [
                    'label' => $start->translatedFormat('j').' t/m '.$start->copy()->endOfMonth()->translatedFormat('j F Y'),
                    'amount' => $prorata,
                ],
                [
                    'label' => $start->copy()->addMonthNoOverflow()->translatedFormat('F Y'),
                    'amount' => $monthly,
                ],
            ],
            'first_amount_excl' => $excl,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'first_amount_incl' => round($excl + $taxAmount, 2),
            'monthly_amount' => $monthly,
        ];
    }

    private function storedHtmlNeedsRefresh(string $html): bool
    {
        return str_contains($html, 'AMOUNT_TABLE')
            || str_contains($html, 'Het eerste te betalen bedrag is')
            || str_contains($html, 'Met de knop hieronder voldoe je');
    }

    private function html(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Eerste betaling NEXA Suite</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;">
    <table role="presentation" width="100%" style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="padding:24px 16px;">
                <table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;">
                    <tr>
                        <td style="padding:24px 30px;background-color:#0f172a;border-radius:8px 8px 0 0;">
                            {{ NEXA_LOGO }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 30px;color:#0f172a;font-size:15px;line-height:1.6;">
                            <p style="margin:0 0 16px;">Beste {{ COMPANY_NAME }},</p>
                            <p style="margin:0 0 16px;">Je NEXA-abonnement <strong>{{ PACKAGE_NAME }}</strong> gaat in op <strong>{{ START_DATE }}</strong>.</p>
                            <p style="margin:0 0 16px;">Er wordt een bedrag van <strong>{{ FIRST_AMOUNT }}</strong> incl. btw geïncasseerd. De details staan in de factuur in de bijlage.</p>
                            <p style="margin:0 0 16px;">Vanaf <strong>{{ RECURRING_FROM }}</strong> wordt maandelijks het volle bedrag van {{ MONTHLY_AMOUNT }} excl. btw geïncasseerd.</p>
                            <p style="margin:0 0 8px;">Via de knop hieronder kan de eerste betaling voldaan worden.</p>
                            <p style="margin:0 0 24px;">Na deze betaling zullen de overige maanden via automatische incasso verlopen.</p>
                            <p style="margin:0 0 28px;text-align:center;">
                                <a href="{{ PAYMENT_URL }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:8px;">Eerste betaling voldoen</a>
                            </p>
                            <p style="margin:0 0 8px;color:#64748b;font-size:13px;">Factuurnummer: {{ INVOICE_NUMBER }}</p>
                            <p style="margin:0;color:#64748b;font-size:13px;">Vragen? Mail <a href="mailto:info@nexasuite.nl" style="color:#2563eb;">info@nexasuite.nl</a>.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function text(): string
    {
        return <<<'TEXT'
Beste {{ COMPANY_NAME }},

Je NEXA-abonnement {{ PACKAGE_NAME }} gaat in op {{ START_DATE }}.

Er wordt een bedrag van {{ FIRST_AMOUNT }} incl. btw geïncasseerd. De details staan in de factuur in de bijlage.

Vanaf {{ RECURRING_FROM }} wordt maandelijks het volle bedrag van {{ MONTHLY_AMOUNT }} excl. btw geïncasseerd.

Via de knop hieronder kan de eerste betaling voldaan worden.
Na deze betaling zullen de overige maanden via automatische incasso verlopen.

Eerste betaling voldoen:
{{ PAYMENT_URL }}

Factuurnummer: {{ INVOICE_NUMBER }}

Vragen? Mail info@nexasuite.nl.
TEXT;
    }
}
