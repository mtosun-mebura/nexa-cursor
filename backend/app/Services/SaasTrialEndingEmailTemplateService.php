<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Support\NexaBranding;

class SaasTrialEndingEmailTemplateService
{
    public const TYPE = 'saas_trial_ending';

    public const TEMPLATE_NAME = 'Proeftijd bijna voorbij (NEXA Suite)';

    public const FROM_NAME = 'NEXA Suite';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'PACKAGE_NAME' => 'Pakketnaam',
            'TRIAL_ENDS_AT' => 'Einde proeftijd (datum)',
            'DAYS_REMAINING' => 'Aantal dagen tot einde proef',
            'START_DATE' => 'Ingangsdatum abonnement',
            'STOP_TRIAL_URL' => 'Link om de proef te stoppen',
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
            if ($existing->name !== self::TEMPLATE_NAME) {
                $existing->name = self::TEMPLATE_NAME;
            }
            if ($this->storedHtmlNeedsRefresh((string) $existing->html_content)) {
                $existing->subject = $this->defaultSubject();
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
            'subject' => $this->defaultSubject(),
            'description' => 'Aankondiging vanuit NEXA vóór het einde van de gratis maanden: facturatie start, tenzij de klant de proef stopt. Bedragen staan niet in deze mail.',
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
     * @return array<string, string>
     */
    public function previewVariables(?Company $company = null): array
    {
        $companyName = $company?->name ?: 'Horizon Taxi';
        $start = now()->addDays(5);

        return array_merge(
            [
                'COMPANY_NAME' => e($companyName),
                'PACKAGE_NAME' => 'Pro',
                'TRIAL_ENDS_AT' => $start->translatedFormat('j F Y'),
                'DAYS_REMAINING' => '5',
                'START_DATE' => $start->translatedFormat('j F Y'),
                'STOP_TRIAL_URL' => url('/proefperiode/voorbeeld/stoppen'),
            ],
            NexaBranding::emailLogoTemplateVariable()
        );
    }

    private function storedHtmlNeedsRefresh(string $html): bool
    {
        return str_contains($html, 'FIRST_AMOUNT')
            || str_contains($html, 'COVERAGE_LABEL')
            || str_contains($html, '#ea580c')
            || str_contains($html, 'gaat het abonnement in. Je ontvangt')
            || str_contains($html, '({{ TRIAL_ENDS_AT }})')
            || str_contains($html, 'Je ontvangt dan een aparte e-mail')
            || str_contains($html, 'Na die betaling wordt de automatische incasso')
            || str_contains($html, 'Je tenant wordt inactief gezet');
    }

    private function defaultSubject(): string
    {
        return 'Je NEXA-abonnement start op {{ START_DATE }} — {{ COMPANY_NAME }}';
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
    <title>Proeftijd NEXA Suite</title>
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
                            <p style="margin:0 0 24px;">Je proefperiode van het pakket <strong>{{ PACKAGE_NAME }}</strong> loopt over <strong>{{ DAYS_REMAINING }} dagen</strong> af.</p>
                            <p style="margin:0 0 16px;">Op <strong>{{ START_DATE }}</strong> gaat het abonnement in.</p>
                            <p style="margin:0 0 24px;">Daarover zal een aparte mail worden verstuurd met de ingangsdatum en een link om de eerste betaling te voldoen.</p>
                            <p style="margin:0 0 16px;">Het jaarcontract telt vanaf de start van de proefperiode.</p>
                            <p style="margin:0;color:#64748b;font-size:13px;">Vragen? Mail <a href="mailto:info@nexasuite.nl" style="color:#2563eb;">info@nexasuite.nl</a>.</p>
                            <p style="margin:28px 0 0;font-size:12px;line-height:1.5;color:#94a3b8;">Geen abonnement afnemen? Dan kun je de proefperiode tot {{ START_DATE }} beëindigen. Je blijft tot die datum toegang houden, er wordt niets geïncasseerd en je kunt het abonnement later weer activeren.<br>
                            <a href="{{ STOP_TRIAL_URL }}" style="color:#94a3b8;text-decoration:underline;">Proefperiode beëindigen</a></p>
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

Je proefperiode van het pakket {{ PACKAGE_NAME }} loopt over {{ DAYS_REMAINING }} dagen af.

Op {{ START_DATE }} gaat het abonnement in.

Daarover zal een aparte mail worden verstuurd met de ingangsdatum en een link om de eerste betaling te voldoen.

Het jaarcontract telt vanaf de start van de proefperiode.

Vragen? Mail info@nexasuite.nl.

Geen abonnement afnemen? Beëindig de proefperiode tot {{ START_DATE }} via:
{{ STOP_TRIAL_URL }}
Je blijft tot die datum toegang houden, er wordt niets geïncasseerd en je kunt het abonnement later weer activeren.
TEXT;
    }
}
