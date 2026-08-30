<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\Concerns\ResolvesScopedEmailTemplate;

class TaxiAppLoginCodeEmailTemplateService
{
    use ResolvesScopedEmailTemplate;

    public const TYPE = 'taxi_app_login_code';

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
            'USER_EMAIL' => 'E-mailadres',
            'APP_NAME' => 'Naam van de app',
            'LOGIN_CODE' => 'Eenmalige inlogcode (6 cijfers)',
            'LOGIN_URL' => 'Link naar de app-login',
            'CODE_EXPIRES_MINUTES' => 'Geldigheid van de code in minuten',
        ];
    }

    public function findTemplate(?int $companyId): ?EmailTemplate
    {
        return $this->findScopedEmailTemplate(self::TYPE, $companyId);
    }

    public function resolveActiveTemplate(?int $companyId): ?EmailTemplate
    {
        return $this->resolveActiveScopedEmailTemplate(self::TYPE, $companyId);
    }

    public function ensureGlobalTemplateExists(): EmailTemplate
    {
        $template = $this->firstOrCreateScopedEmailTemplate(self::TYPE, null, $this->defaultPayload(null));
        $this->upgradeStoredTemplates();

        return $template->fresh() ?? $template;
    }

    public function ensureTenantTemplateExists(int $companyId): EmailTemplate
    {
        $this->ensureGlobalTemplateExists();

        $existing = $this->findTemplate($companyId);
        if ($existing) {
            return $existing;
        }

        $global = $this->findTemplate(null);
        $payload = $this->defaultPayload($companyId);
        if ($global) {
            $payload['subject'] = $global->subject;
            $payload['html_content'] = $global->html_content;
            $payload['text_content'] = $global->text_content;
            $payload['description'] = $global->description ?? $payload['description'];
        }

        return $this->upsertScopedEmailTemplate(self::TYPE, $companyId, $payload);
    }

    public function appOpenButtonHtml(): string
    {
        return '<p style="margin:16px 0 18px;text-align:center;">'
            .'<a href="{{ LOGIN_URL }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">'
            .'<span style="color:#ffffff;">Open {{ APP_NAME }}</span>'
            .'</a>'
            .'</p>';
    }

    /**
     * Vervang kale app-URL of kop-link door een gecentreerde knop, in globaal én tenant-templates.
     */
    private function upgradeStoredTemplates(): void
    {
        $button = $this->appOpenButtonHtml();

        EmailTemplate::query()
            ->where('type', self::TYPE)
            ->get()
            ->each(function (EmailTemplate $template) use ($button): void {
                $html = (string) $template->html_content;
                $text = (string) $template->text_content;
                $updatedHtml = $html;

                if (! str_contains($updatedHtml, 'background-color:#ea580c')
                    && ! str_contains($updatedHtml, 'background-color: #ea580c')) {
                    $updatedHtml = preg_replace(
                        '/<h2[^>]*>\s*<a[^>]*href="\{\{\s*LOGIN_URL\s*\}\}"[^>]*>[\s\S]*?<\/a>\s*<\/h2>/i',
                        $button,
                        $updatedHtml,
                        1
                    ) ?? $updatedHtml;

                    $updatedHtml = preg_replace(
                        '/<p[^>]*>\s*<a[^>]*href="\{\{\s*LOGIN_URL\s*\}\}"[^>]*>\s*\{\{\s*LOGIN_URL\s*\}\}\s*<\/a>\s*<\/p>/i',
                        $button,
                        $updatedHtml,
                        1
                    ) ?? $updatedHtml;
                }

                $updatedText = preg_replace(
                    '/Open:\s*\{\{\s*LOGIN_URL\s*\}\}/',
                    'Open {{ APP_NAME }}: {{ LOGIN_URL }}',
                    $text,
                    1
                ) ?? $text;

                if ($updatedHtml === $html && $updatedText === $text) {
                    return;
                }

                $template->html_content = $updatedHtml;
                $template->text_content = $updatedText;
                $template->save();
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultPayload(?int $companyId): array
    {
        return [
            'type' => self::TYPE,
            'company_id' => $companyId,
            'name' => $companyId
                ? 'Eenmalige app-inlogcode (eigen versie)'
                : 'Eenmalige app-inlogcode (chauffeur / contract)',
            'subject' => 'Uw inlogcode voor {{ APP_NAME }} – {{ COMPANY_NAME }}',
            'description' => 'E-mail met een eenmalige 6-cijferige code, alleen na een aanvraag in de chauffeur-app of het contractportaal.',
            'html_content' => $this->defaultHtmlContent(),
            'text_content' => $this->defaultTextContent(),
            'is_active' => true,
        ];
    }

    public function defaultHtmlContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="UTF-8"><title>Inlogcode</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#111827;margin:0;padding:0;">
<div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="margin-bottom:20px;">{{ NEXA_LOGO }}</div>
    <div style="margin-bottom:12px;">{{ COMPANY_LOGO }}</div>
    <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">{{ COMPANY_NAME }}</p>
    <h1 style="font-size:20px;margin:0 0 16px;">Uw eenmalige inlogcode</h1>
    <p>Beste {{ USER_NAME }},</p>
    <p>Iemand heeft een inlogcode aangevraagd voor <strong>{{ APP_NAME }}</strong> met dit e-mailadres. De code is <strong>{{ CODE_EXPIRES_MINUTES }} minuten</strong> geldig en mag één keer worden gebruikt.</p>
    <div style="margin:16px 0;padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;font-size:22px;letter-spacing:3px;font-weight:800;text-align:center;">
        {{ LOGIN_CODE }}
    </div>
    <p style="margin:0 0 10px;">Daarna kiest u zelf een wachtwoord. Open de app:</p>
    <p style="margin:16px 0 18px;text-align:center;">
        <a href="{{ LOGIN_URL }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
            <span style="color:#ffffff;">Open {{ APP_NAME }}</span>
        </a>
    </p>
    <p style="margin-top:18px;font-size:13px;color:#6b7280;">Heeft u dit niet aangevraagd? Negeer deze e-mail. Zonder code kan niemand inloggen.</p>
</div>
</body>
</html>
HTML;
    }

    public function defaultTextContent(): string
    {
        return <<<'TEXT'
Beste {{ USER_NAME }},

Uw eenmalige inlogcode voor {{ APP_NAME }} is: {{ LOGIN_CODE }}
Deze code is {{ CODE_EXPIRES_MINUTES }} minuten geldig en mag één keer worden gebruikt.

Open {{ APP_NAME }}: {{ LOGIN_URL }}

Heeft u dit niet aangevraagd? Negeer deze e-mail.
TEXT;
    }
}
