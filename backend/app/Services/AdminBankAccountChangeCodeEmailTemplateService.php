<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\NexaBranding;

/**
 * Bevestigingscode bij wijziging van de uitbetalings-IBAN (marketplace).
 * Zelfde opmaak als de admin-inlogcode, andere tekst.
 */
class AdminBankAccountChangeCodeEmailTemplateService
{
    public const TYPE = 'admin_bank_account_change_code';

    public const TEMPLATE_NAME = 'Bevestigingscode bankrekening wijzigen';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'NEXA_LOGO' => 'Nexa-logo (HTML)',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'USER_NAME' => 'Naam van de beheerder',
            'USER_EMAIL' => 'E-mailadres',
            'LOGIN_CODE' => 'Eenmalige bevestigingscode (6 cijfers)',
            'BANK_ACCOUNT_URL' => 'Link naar bankrekening-pagina',
            'CODE_EXPIRES_MINUTES' => 'Geldigheid van de code in minuten',
            'WAIT_HOURS' => 'Wachttijd in uren voordat het nieuwe rekeningnummer actief wordt',
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

            $this->upgradeStoredTemplates();

            return $existing->fresh() ?? $existing;
        }

        return EmailTemplate::query()->create([
            'type' => self::TYPE,
            'company_id' => null,
            'name' => self::TEMPLATE_NAME,
            'subject' => 'Bevestig je nieuwe rekeningnummer',
            'description' => 'Eenmalige code om een wijziging van het uitbetalingsrekeningnummer te bevestigen.',
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
     * Zorg dat bestaande templates de juiste IBAN-copy hebben (niet de oude inlogtekst).
     */
    public function upgradeStoredTemplates(): void
    {
        EmailTemplate::query()
            ->where('type', self::TYPE)
            ->get()
            ->each(function (EmailTemplate $template): void {
                $html = (string) $template->html_content;
                $text = (string) ($template->text_content ?? '');
                $subject = (string) ($template->subject ?? '');

                $looksLikeLogin = str_contains($html, 'in te loggen')
                    || str_contains($html, 'Je inlogcode')
                    || str_contains($text, 'inlogcode');

                if (! $looksLikeLogin) {
                    return;
                }

                $template->forceFill([
                    'name' => self::TEMPLATE_NAME,
                    'subject' => 'Bevestig je nieuwe rekeningnummer',
                    'description' => 'Eenmalige code om een wijziging van het uitbetalingsrekeningnummer te bevestigen.',
                    'html_content' => $this->html(),
                    'text_content' => $this->text(),
                ])->save();
            });
    }

    /**
     * @return array<string, string>
     */
    public function previewVariables(): array
    {
        $waitHours = (string) max(1, (int) config('nexa_payout.destination_change_cooling_off_hours', 48));

        return array_merge(
            [
                'USER_NAME' => 'Beheerder Taxi Enschede',
                'USER_EMAIL' => 'beheer@taxi-enschede.nl',
                'COMPANY_NAME' => 'Taxi Enschede',
                'LOGIN_CODE' => '994303',
                'BANK_ACCOUNT_URL' => $this->bankAccountUrl(),
                'CODE_EXPIRES_MINUTES' => '15',
                'WAIT_HOURS' => $waitHours,
            ],
            app(CompanyEmailLogoService::class)->templateVariable(null, 'Taxi Enschede'),
            NexaBranding::emailLogoTemplateVariable()
        );
    }

    public function bankAccountUrl(): string
    {
        try {
            return route('admin.settings.bank-account');
        } catch (\Throwable) {
            return url('/admin/settings/bank-account');
        }
    }

    private function html(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bevestigingscode rekeningnummer</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {{ NEXA_LOGO }}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">Nieuw rekeningnummer bevestigen</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
        <p style="margin:0 0 16px;font-size:16px;">Beste {{ USER_NAME }},</p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Je wilt het rekeningnummer voor uitbetalingen van <strong>{{ COMPANY_NAME }}</strong> wijzigen.
            Gebruik onderstaande eenmalige code om dat te bevestigen. De code is <strong>{{ CODE_EXPIRES_MINUTES }} minuten</strong> geldig.
        </p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Na bevestiging wachten we nog <strong>{{ WAIT_HOURS }} uur</strong> voordat we uitbetalingen naar het nieuwe rekeningnummer sturen.
            Zo blijft je rekening beter beschermd.
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
            <tr>
                <td style="padding:18px 16px;border-radius:12px;background-color:#e8eef5;text-align:center;">
                    <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Code</p>
                    <p style="margin:0;font-size:28px;letter-spacing:6px;font-weight:800;color:#0f172a;">{{ LOGIN_CODE }}</p>
                </td>
            </tr>
        </table>
        <p style="margin:0 0 18px;text-align:center;">
            <a href="{{ BANK_ACCOUNT_URL }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
                <span style="color:#ffffff;">Naar bankrekening</span>
            </a>
        </p>
        <p style="margin:0;font-size:13px;color:#6b7280;">Heb je deze wijziging niet aangevraagd? Negeer deze e-mail dan. Het rekeningnummer verandert pas als jij de code invult.</p>
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

Je wilt het rekeningnummer voor uitbetalingen van {{ COMPANY_NAME }} wijzigen.
Gebruik deze eenmalige code om dat te bevestigen ({{ CODE_EXPIRES_MINUTES }} minuten geldig):

{{ LOGIN_CODE }}

Na bevestiging wachten we nog {{ WAIT_HOURS }} uur voordat we uitbetalingen naar het nieuwe rekeningnummer sturen.

Ga naar: {{ BANK_ACCOUNT_URL }}

Heb je deze wijziging niet aangevraagd? Negeer deze e-mail dan.
TEXT;
    }
}
