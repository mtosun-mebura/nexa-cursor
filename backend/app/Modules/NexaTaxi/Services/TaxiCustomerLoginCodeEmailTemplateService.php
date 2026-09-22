<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\Concerns\ResolvesScopedEmailTemplate;
use App\Support\EmailCardHtml;

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
        return $this->resolveActiveScopedEmailTemplate(self::TYPE, $companyId);
    }

    public function ensureGlobalTemplateExists(): EmailTemplate
    {
        $template = $this->firstOrCreateScopedEmailTemplate(self::TYPE, null, $this->defaultPayload(null));
        EmailCardHtml::upgradeTypeToCardLayout(self::TYPE, fn () => $this->defaultHtmlContent());

        return $template->fresh() ?? $template;
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
        $body = <<<'HTML'
<p style="margin:0 0 16px;font-size:16px;">Beste {{ USER_NAME }},</p>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
    Gebruik onderstaande code om in te loggen. Deze code is <strong>{{ CODE_EXPIRES_MINUTES }} minuten</strong> geldig.
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
    <a href="{{ LOGIN_URL }}" style="display:inline-block;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:6px;">
        <span style="color:#ffffff;">Inloggen</span>
    </a>
</p>
<p style="margin:0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">Heeft u dit niet aangevraagd? Dan kunt u deze e-mail negeren.</p>
HTML;

        return EmailCardHtml::wrap(
            'Inlogcode',
            'Uw eenmalige inlogcode',
            $body,
            EmailCardHtml::companyLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
            '{{ COMPANY_NAME }}',
        );
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
