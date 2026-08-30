<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\Concerns\ResolvesScopedEmailTemplate;

class TaxiAppUserWelcomeEmailTemplateService
{
    use ResolvesScopedEmailTemplate;

    public const TYPE_CHAUFFEUR = 'user_welcome_chauffeur';

    public const TYPE_CONTRACTANT = 'user_welcome_contractant';

    public const TYPE_CONTRACTOUDER = 'user_welcome_contractouder';

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CHAUFFEUR,
            self::TYPE_CONTRACTANT,
            self::TYPE_CONTRACTOUDER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'NEXA_LOGO' => 'Nexa-logo (HTML)',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'USER_NAME' => 'Naam van de gebruiker',
            'USER_EMAIL' => 'E-mailadres (gebruikersnaam)',
            'ROLE_LABEL' => 'Rol (Chauffeur, Contractant of Contractouder)',
            'APP_NAME' => 'Naam van de app',
            'LOGIN_URL' => 'Link naar de juiste app-login',
        ];
    }

    public function ensureAllGlobalTemplatesExist(): void
    {
        foreach (self::types() as $type) {
            $this->ensureGlobalTemplateExists($type);
        }
        $this->upgradeStoredTemplates();
    }

    public function ensureGlobalTemplateExists(string $type): EmailTemplate
    {
        return $this->firstOrCreateScopedEmailTemplate($type, null, $this->defaultPayload($type, null));
    }

    public function ensureTenantTemplateExists(string $type, int $companyId): EmailTemplate
    {
        $this->ensureGlobalTemplateExists($type);

        $existing = $this->findScopedEmailTemplate($type, $companyId);
        if ($existing) {
            return $existing;
        }

        $global = $this->findScopedEmailTemplate($type, null);
        $payload = $this->defaultPayload($type, $companyId);
        if ($global) {
            $payload['subject'] = $global->subject;
            $payload['html_content'] = $global->html_content;
            $payload['text_content'] = $global->text_content;
            $payload['description'] = $global->description ?? $payload['description'];
        }

        return $this->upsertScopedEmailTemplate($type, $companyId, $payload);
    }

    public function resolveActiveTemplate(string $type, ?int $companyId): ?EmailTemplate
    {
        return $this->resolveActiveScopedEmailTemplate($type, $companyId);
    }

    /**
     * Rondere inlogblokken en duidelijkere labelkleur in bestaande welkomstmails.
     */
    public function upgradeStoredTemplates(): void
    {
        $block = '<table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">'
            .'<tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">'
            .'<p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Inloggen</p>'
            .'<p style="margin:0;font-size:14px;"><strong>E-mailadres:</strong> {{ USER_EMAIL }}</p>'
            .'</td></tr>'
            .'</table>';

        EmailTemplate::query()
            ->whereIn('type', self::types())
            ->get()
            ->each(function (EmailTemplate $template) use ($block): void {
                $html = (string) $template->html_content;
                $updated = preg_replace(
                    '/<table role="presentation" width="100%" style="[^"]*background-color:\s*#f8fafc;[^"]*">[\s\S]*?Inloggen[\s\S]*?<\/table>/i',
                    $block,
                    $html,
                    1
                ) ?? $html;

                if ($updated === $html) {
                    return;
                }

                $template->html_content = $updated;
                $template->save();
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultPayload(string $type, ?int $companyId): array
    {
        $meta = $this->typeMeta($type);

        return [
            'type' => $type,
            'company_id' => $companyId,
            'name' => $companyId
                ? $meta['name'].' (eigen versie)'
                : $meta['name'],
            'subject' => $meta['subject'],
            'description' => $meta['description'],
            'html_content' => $this->defaultHtmlContent($meta),
            'text_content' => $this->defaultTextContent($meta),
            'is_active' => true,
        ];
    }

    /**
     * @return array{name: string, subject: string, description: string, app: string, role: string}
     */
    public function typeMeta(string $type): array
    {
        return match ($type) {
            self::TYPE_CONTRACTANT => [
                'name' => 'Welkomstmail contractant',
                'subject' => 'Welkom bij het contractportaal van {{ COMPANY_NAME }}',
                'description' => 'Welkomstmail bij aanmaken van een contractant. Zonder wachtwoord; inloggen via een eenmalige code in de app.',
                'app' => 'het contractportaal',
                'role' => 'Contractant',
            ],
            self::TYPE_CONTRACTOUDER => [
                'name' => 'Welkomstmail contractouder',
                'subject' => 'Welkom bij het contractportaal van {{ COMPANY_NAME }}',
                'description' => 'Welkomstmail bij aanmaken van een contractouder. Zonder wachtwoord; inloggen via een eenmalige code in de app.',
                'app' => 'het contractportaal',
                'role' => 'Contractouder',
            ],
            default => [
                'name' => 'Welkomstmail chauffeur',
                'subject' => 'Welkom bij de chauffeur-app van {{ COMPANY_NAME }}',
                'description' => 'Welkomstmail bij aanmaken van een chauffeur. Zonder wachtwoord; inloggen via een eenmalige code in de app.',
                'app' => 'de chauffeur-app',
                'role' => 'Chauffeur',
            ],
        };
    }

    /**
     * @param  array{app: string, role: string}  $meta
     */
    public function defaultHtmlContent(array $meta): string
    {
        $app = e($meta['app']);
        $role = e($meta['role']);

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Welkom</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;color:#111827;color-scheme:light;">
<table role="presentation" style="width:100%;border-collapse:collapse;">
<tr><td style="padding:24px 12px;">
<table role="presentation" width="100%" style="width:100%;max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #d1d5db;border-radius:8px;border-collapse:separate;border-spacing:0;overflow:hidden;">
<tr>
    <td bgcolor="#0f172a" style="padding:28px 32px;background-color:#0f172a;">
        {{ NEXA_LOGO }}
        <h1 style="margin:0;font-size:22px;line-height:1.3;color:#ffffff;">Uw account is klaar</h1>
    </td>
</tr>
<tr>
    <td style="padding:28px 32px;">
        <div style="margin-bottom:16px;">{{ COMPANY_LOGO }}</div>
        <p style="margin:0 0 16px;font-size:16px;">Beste {{ USER_NAME }},</p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            <strong>{{ COMPANY_NAME }}</strong> heeft een account voor u aangemaakt als <strong>{$role}</strong>.
            Er staat <strong>geen wachtwoord</strong> in deze e-mail: dat is veiliger.
        </p>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
            Open {$app}, vul uw e-mailadres in en kies <strong>Inlogcode aanvragen</strong>.
            U ontvangt dan een eenmalige code die kort geldig is. Daarna kiest u zelf een wachtwoord.
        </p>
        <table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
            <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
                <p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Inloggen</p>
                <p style="margin:0;font-size:14px;"><strong>E-mailadres:</strong> {{ USER_EMAIL }}</p>
            </td></tr>
        </table>
        <p style="margin:0 0 18px;text-align:center;">
            <a href="{{ LOGIN_URL }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
                <span style="color:#ffffff;">Open {{ APP_NAME }}</span>
            </a>
        </p>
        <p style="margin:0;font-size:13px;color:#6b7280;">Heeft u deze e-mail niet verwacht? Neem contact op met {{ COMPANY_NAME }}.</p>
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /**
     * @param  array{app: string, role: string}  $meta
     */
    public function defaultTextContent(array $meta): string
    {
        $app = $meta['app'];
        $role = $meta['role'];

        return <<<TEXT
Beste {{ USER_NAME }},

{{ COMPANY_NAME }} heeft een account voor u aangemaakt als {$role}.
Er staat geen wachtwoord in deze e-mail.

1. Open {$app}: {{ LOGIN_URL }}
2. Vul uw e-mailadres in: {{ USER_EMAIL }}
3. Kies “Inlogcode aanvragen”
4. Vul de code uit de volgende e-mail in en kies een eigen wachtwoord

Heeft u deze e-mail niet verwacht? Neem contact op met {{ COMPANY_NAME }}.
TEXT;
    }
}
