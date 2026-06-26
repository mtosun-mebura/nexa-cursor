<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\Concerns\ResolvesScopedEmailTemplate;

class TaxiCustomerLoginCodeEmailTemplateService
{
    use ResolvesScopedEmailTemplate;

    public const TYPE = 'taxi_customer_login_code';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML, automatisch ingevuld)',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'USER_NAME' => 'Naam klant',
            'USER_EMAIL' => 'E-mailadres klant',
            'LOGIN_CODE' => 'Eenmalige inlogcode ('.TaxiCustomerLoginCodeService::CODE_LENGTH.' cijfers)',
            'LOGIN_URL' => 'Link naar inlogpagina (met code)',
            'CODE_EXPIRES_MINUTES' => 'Geldigheid code in minuten (waarde uit Chauffeur dispatch → Mijn Taxi)',
        ];
    }

    public function findTemplate(?int $companyId): ?EmailTemplate
    {
        return $this->findScopedEmailTemplate(self::TYPE, $companyId);
    }

    /**
     * Actieve template voor verzending (tenant-specifiek heeft voorrang op globaal).
     */
    public function resolveActiveTemplate(?int $companyId): ?EmailTemplate
    {
        return EmailTemplate::query()
            ->where('type', self::TYPE)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId !== null && $companyId > 0) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderByDesc('company_id')
            ->first();
    }

    public function ensureGlobalTemplateExists(): EmailTemplate
    {
        return $this->upsertScopedEmailTemplate(self::TYPE, null, $this->defaultPayload(null));
    }

    /**
     * Zorg dat een tenant een bewerkbaar template in de e-maillijst heeft (kopie van globaal indien nodig).
     * Alleen aanroepen vanuit admin/seeders — niet bij elke e-mailverzending.
     */
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

    /**
     * @return array<string, mixed>
     */
    public function defaultPayload(?int $companyId): array
    {
        return [
            'type' => self::TYPE,
            'company_id' => $companyId,
            'name' => $companyId
                ? 'Eenmalige inlogcode (eigen versie)'
                : 'Eenmalige inlogcode (Nexa Taxi)',
            'subject' => 'Uw inlogcode – {{ COMPANY_NAME }}',
            'description' => 'E-mail met een eenmalige code van '.TaxiCustomerLoginCodeService::CODE_LENGTH.' cijfers om als klant in te loggen en daarna een wachtwoord aan te maken.',
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
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827; margin: 0; padding: 0;">
<div style="max-width: 600px; margin: 0 auto; padding: 24px;">
    <div style="margin-bottom: 20px;">{{ COMPANY_LOGO }}</div>
    <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280;">{{ COMPANY_NAME }}</p>
    <h1 style="font-size: 20px; margin: 0 0 16px;">Uw eenmalige inlogcode</h1>
    <p>Beste {{ USER_NAME }},</p>
    <p>Gebruik onderstaande code om in te loggen. Deze code is <strong>{{ CODE_EXPIRES_MINUTES }}</strong> minuten geldig.</p>
    <div style="margin: 16px 0; padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f9fafb; font-size: 22px; letter-spacing: 3px; font-weight: 800; text-align: center;">
        {{ LOGIN_CODE }}
    </div>
    <p style="margin: 0 0 10px;">Inloggen kan ook via deze link:</p>
    <p><a href="{{ LOGIN_URL }}" style="color: #2563eb; font-weight: 700;">{{ LOGIN_URL }}</a></p>
    <p style="margin-top: 18px; font-size: 13px; color: #6b7280;">Heeft u dit niet aangevraagd? Dan kunt u deze e-mail negeren.</p>
</div>
</body>
</html>
HTML;
    }

    public function defaultTextContent(): string
    {
        return <<<'TEXT'
{{ COMPANY_NAME }}

Uw eenmalige inlogcode ({{ CODE_EXPIRES_MINUTES }} min geldig):

{{ LOGIN_CODE }}

Inloggen: {{ LOGIN_URL }}
TEXT;
    }
}
