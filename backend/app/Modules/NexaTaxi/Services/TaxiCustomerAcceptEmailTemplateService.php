<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\EmailTemplate;
use App\Modules\NexaTaxi\Services\Concerns\ResolvesScopedEmailTemplate;
use App\Support\EmailCardHtml;

class TaxiCustomerAcceptEmailTemplateService
{
    use ResolvesScopedEmailTemplate;

    public const TYPE = 'taxi_ride_accepted';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML, automatisch ingevuld)',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'COMPANY_PHONE' => 'Telefoon bedrijf',
            'COMPANY_EMAIL' => 'E-mail bedrijf',
            'COMPANY_ADDRESS' => 'Bedrijfsadres',
            'CUSTOMER_NAME' => 'Naam klant',
            'CUSTOMER_EMAIL' => 'E-mail klant',
            'CUSTOMER_PHONE' => 'Telefoon klant',
            'DRIVER_NAME' => 'Naam chauffeur',
            'DRIVER_PHONE' => 'Telefoon chauffeur',
            'PICKUP_AT' => 'Ophaalmoment',
            'PICKUP_ADDRESS' => 'Ophaaladres',
            'DROPOFF_ADDRESS' => 'Afzetadres',
            'RIDE_ID' => 'Ritnummer',
        ];
    }

    /**
     * Template om te tonen in het bewerkformulier (tenant-specifiek of globaal als fallback).
     *
     * @return array{template: EmailTemplate, usesGlobalFallback: bool}
     */
    public function templateForEditing(?int $companyId): array
    {
        $this->ensureGlobalTemplateExists();

        if ($companyId !== null && $companyId > 0) {
            $tenant = $this->findTemplate($companyId);
            if ($tenant) {
                return ['template' => $tenant, 'usesGlobalFallback' => false];
            }

            $global = $this->findTemplate(null);
            if ($global) {
                return ['template' => $global, 'usesGlobalFallback' => true];
            }
        }

        $global = $this->findTemplate(null);
        if ($global) {
            return ['template' => $global, 'usesGlobalFallback' => false];
        }

        return ['template' => $this->ensureGlobalTemplateExists(), 'usesGlobalFallback' => false];
    }

    /**
     * Slaat de e-mailtekst op. Bij tenant + globale fallback wordt een tenant-kopie aangemaakt.
     *
     * @param  array{subject: string, html_content: string, text_content?: string|null}  $data
     */
    public function saveForCompany(?int $companyId, array $data, bool $usesGlobalFallback): EmailTemplate
    {
        $this->ensureGlobalTemplateExists();

        $subject = trim((string) ($data['subject'] ?? ''));
        $html = (string) ($data['html_content'] ?? '');
        $text = isset($data['text_content']) ? (string) $data['text_content'] : null;

        if ($companyId !== null && $companyId > 0) {
            $template = $this->findTemplate($companyId);
            if (! $template && $usesGlobalFallback) {
                $global = $this->findTemplate(null);

                return $this->upsertScopedEmailTemplate(self::TYPE, $companyId, array_merge(
                    $this->defaultPayload($companyId),
                    [
                        'subject' => $subject,
                        'html_content' => $html,
                        'text_content' => $text ?: ($global?->text_content ?? ''),
                        'name' => 'Rit geaccepteerd (eigen versie)',
                        'description' => 'E-mail naar de klant wanneer een chauffeur de rit accepteert (tenant).',
                    ]
                ));
            }
        } else {
            $template = $this->findTemplate(null);
        }

        if (! $template) {
            $template = $this->upsertScopedEmailTemplate(self::TYPE, $companyId, $this->defaultPayload($companyId));
        }

        $template->update([
            'subject' => $subject,
            'html_content' => $html,
            'text_content' => $text,
            'is_active' => true,
        ]);

        return $template->fresh();
    }

    public function ensureGlobalTemplateExists(): EmailTemplate
    {
        $template = $this->upsertScopedEmailTemplate(self::TYPE, null, $this->defaultPayload(null));
        EmailCardHtml::upgradeTypeToCardLayout(self::TYPE, fn () => $this->defaultHtmlContent());

        return $template->fresh() ?? $template;
    }

    public function findTemplate(?int $companyId): ?EmailTemplate
    {
        return $this->findScopedEmailTemplate(self::TYPE, $companyId);
    }

    /**
     * Actieve template voor verzending (zelfde logica als notificatieservice).
     */
    public function resolveActiveTemplate(?int $companyId): ?EmailTemplate
    {
        return $this->resolveActiveScopedEmailTemplate(self::TYPE, $companyId);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPayload(?int $companyId): array
    {
        return [
            'type' => self::TYPE,
            'company_id' => $companyId,
            'name' => $companyId
                ? 'Rit geaccepteerd (eigen versie)'
                : 'Rit geaccepteerd (Nexa Taxi)',
            'subject' => 'Uw taxirit is geaccepteerd – {{ COMPANY_NAME }}',
            'description' => 'E-mail naar de klant wanneer een chauffeur de rit accepteert.',
            'html_content' => $this->defaultHtmlContent(),
            'text_content' => $this->defaultTextContent(),
            'is_active' => true,
        ];
    }

    private function defaultHtmlContent(): string
    {
        $body = <<<'HTML'
<p style="margin:0 0 16px;font-size:16px;">Beste {{ CUSTOMER_NAME }},</p>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Goed nieuws: uw rit is geaccepteerd door <strong>{{ DRIVER_NAME }}</strong>.</p>
<table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">
    <tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">
        <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Ophaalmoment:</strong> {{ PICKUP_AT }}</p>
        <p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>Ophalen:</strong> {{ PICKUP_ADDRESS }}</p>
        <p style="margin:0;font-size:15px;line-height:1.6;"><strong>Afzetten:</strong> {{ DROPOFF_ADDRESS }}</p>
    </td></tr>
</table>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Vragen? Neem contact op via {{ COMPANY_PHONE }} of {{ COMPANY_EMAIL }}.</p>
<p style="margin:0;font-size:15px;line-height:1.6;">Met vriendelijke groet,<br>{{ COMPANY_NAME }}</p>
HTML;

        return EmailCardHtml::wrap(
            'Rit geaccepteerd',
            'Uw taxirit is geaccepteerd',
            $body,
            EmailCardHtml::companyLogoMarkup(),
            EmailCardHtml::poweredByFooter(),
            '{{ COMPANY_NAME }}',
        );
    }

    private function defaultTextContent(): string
    {
        return <<<'TEXT'
Beste {{ CUSTOMER_NAME }},

Uw taxirit is geaccepteerd door {{ DRIVER_NAME }}.

Ophaalmoment: {{ PICKUP_AT }}
Ophalen: {{ PICKUP_ADDRESS }}
Afzetten: {{ DROPOFF_ADDRESS }}

Vragen? {{ COMPANY_PHONE }} / {{ COMPANY_EMAIL }}

Met vriendelijke groet,
{{ COMPANY_NAME }}
TEXT;
    }
}
