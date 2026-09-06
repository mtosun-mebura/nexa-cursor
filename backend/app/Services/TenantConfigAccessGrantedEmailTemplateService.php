<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Support\NexaBranding;

class TenantConfigAccessGrantedEmailTemplateService
{
    public const TYPE = 'tenant_config_access_granted';

    public const TEMPLATE_NAME = 'Configuratie-toegang (bericht in NEXA Suite)';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'NEXA_LOGO' => 'Nexa-logo (HTML)',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
            'USER_NAME' => 'Naam van de ontvanger',
            'SENDER_NAME' => 'Naam van de afzender (super-admin)',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'CONFIG_LABELS' => 'Toegekende configuraties (tekst)',
            'ACTION_URL' => 'Link naar de admin',
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
                $existing->save();
            }

            return $existing;
        }

        return EmailTemplate::query()->create([
            'type' => self::TYPE,
            'company_id' => null,
            'name' => self::TEMPLATE_NAME,
            'subject' => 'Nieuw bericht in NEXA Suite van {{ SENDER_NAME }}',
            'description' => 'Mail wanneer een super-admin configuratie-toegang geeft: er staat een bericht klaar in NEXA Suite, met een knop naar de admin.',
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

        return array_merge(
            [
                'USER_NAME' => 'Lisa Vermeer',
                'SENDER_NAME' => 'Alex Jansen',
                'COMPANY_NAME' => e($companyName),
                'CONFIG_LABELS' => 'Google SEO en Mollie',
                'ACTION_URL' => TenantWelcomeEmailTemplateService::ADMIN_LOGIN_URL,
            ],
            app(CompanyEmailLogoService::class)->templateVariable($company?->id, $companyName),
            NexaBranding::emailLogoTemplateVariable()
        );
    }

    private function html(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuw bericht in NEXA Suite</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {{ NEXA_LOGO }}
        <h1 style="margin:16px 0 0;font-size:22px;line-height:1.3;color:#ffffff;">Nieuw bericht in NEXA Suite</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
        <p style="margin:0 0 16px;font-size:16px;">Beste {{ USER_NAME }},</p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            <strong>{{ SENDER_NAME }}</strong> heeft je een bericht gestuurd in NEXA Suite.
            Je hebt toegang gekregen tot: <strong>{{ CONFIG_LABELS }}</strong>.
        </p>
        <p style="margin:0 0 22px;font-size:15px;line-height:1.6;">
            Open de admin om het bericht te lezen en de configuratie in te vullen.
        </p>
        <p style="margin:0 0 18px;text-align:center;">
            <a href="{{ ACTION_URL }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
                <span style="color:#ffffff;">Open de admin</span>
            </a>
        </p>
        <p style="margin:0;font-size:13px;color:#6b7280;">Dit bericht geldt voor {{ COMPANY_NAME }}.</p>
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function text(): string
    {
        return <<<'TEXT'
Beste {{ USER_NAME }},

{{ SENDER_NAME }} heeft je een bericht gestuurd in NEXA Suite.
Je hebt toegang gekregen tot: {{ CONFIG_LABELS }}.

Open de admin om het bericht te lezen en de configuratie in te vullen:
{{ ACTION_URL }}
TEXT;
    }
}
