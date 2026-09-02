<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\NexaBranding;

class AdminFirstLoginCodeEmailTemplateService
{
    public const TYPE = 'admin_first_login_code';

    public const TEMPLATE_NAME = 'Eenmalige inlogcode admin (eerste login)';

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
            'LOGIN_CODE' => 'Eenmalige inlogcode (6 cijfers)',
            'ADMIN_LOGIN_URL' => 'Link naar de admin-login',
            'CODE_EXPIRES_MINUTES' => 'Geldigheid van de code in minuten',
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
            'subject' => 'Je inlogcode voor NEXA Suite',
            'description' => 'Eenmalige code om als company-admin voor het eerst in te loggen en daarna zelf een wachtwoord te kiezen.',
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

    public function upgradeStoredTemplates(): void
    {
        EmailTemplate::query()
            ->where('type', self::TYPE)
            ->get()
            ->each(function (EmailTemplate $template): void {
                $html = (string) $template->html_content;
                $text = (string) ($template->text_content ?? '');
                $subject = (string) ($template->subject ?? '');
                $updatedHtml = $this->informalizeCopy($html);
                $updatedText = $this->informalizeCopy($text);
                $updatedSubject = $this->informalizeCopy($subject);

                $dirty = false;
                if ($updatedHtml !== $html) {
                    $template->html_content = $updatedHtml;
                    $dirty = true;
                }
                if ($updatedText !== $text) {
                    $template->text_content = $updatedText;
                    $dirty = true;
                }
                if ($updatedSubject !== $subject) {
                    $template->subject = $updatedSubject;
                    $dirty = true;
                }
                if ($dirty) {
                    $template->save();
                }
            });
    }

    private function informalizeCopy(string $content): string
    {
        return str_replace(
            [
                'Uw inlogcode voor NEXA Suite',
                'Uw inlogcode',
                'Uw eenmalige inlogcode',
                'Daarna kiest u zelf een wachtwoord.',
                'Heeft u deze code niet aangevraagd? Dan kunt u deze e-mail negeren.',
            ],
            [
                'Je inlogcode voor NEXA Suite',
                'Je inlogcode',
                'Je eenmalige inlogcode',
                'Daarna kies je zelf een wachtwoord.',
                'Heb je deze code niet aangevraagd? Dan kun je deze e-mail negeren.',
            ],
            $content
        );
    }

    /**
     * @return array<string, string>
     */
    public function previewVariables(): array
    {
        return array_merge(
            [
                'USER_NAME' => 'Lisa Vermeer',
                'USER_EMAIL' => 'lisa@horizontaxi.nl',
                'COMPANY_NAME' => 'Horizon Taxi',
                'LOGIN_CODE' => '482917',
                'ADMIN_LOGIN_URL' => TenantWelcomeEmailTemplateService::ADMIN_LOGIN_URL,
                'CODE_EXPIRES_MINUTES' => '15',
            ],
            app(CompanyEmailLogoService::class)->templateVariable(null, 'Horizon Taxi'),
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
    <title>Inlogcode</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {{ NEXA_LOGO }}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">Je inlogcode</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
        <p style="margin:0 0 16px;font-size:16px;">Beste {{ USER_NAME }},</p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Gebruik onderstaande eenmalige code om in te loggen op de admin van <strong>{{ COMPANY_NAME }}</strong>.
            De code is <strong>{{ CODE_EXPIRES_MINUTES }} minuten</strong> geldig. Daarna kies je zelf een wachtwoord.
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
            <a href="{{ ADMIN_LOGIN_URL }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
                <span style="color:#ffffff;">Open de admin</span>
            </a>
        </p>
        <p style="margin:0;font-size:13px;color:#6b7280;">Heb je deze code niet aangevraagd? Dan kun je deze e-mail negeren.</p>
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

Je eenmalige inlogcode voor de admin van {{ COMPANY_NAME }} ({{ CODE_EXPIRES_MINUTES }} minuten geldig):

{{ LOGIN_CODE }}

Open de admin: {{ ADMIN_LOGIN_URL }}
TEXT;
    }
}
