<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\InfoRequestFormField;

class NexaContactAanvraagEmailTemplateService
{
    public const RECIPIENT_EMAIL = 'info@nexasuite.nl';

    public const TEMPLATE_NAME = 'NEXA Suite contactaanvraag';

    public const FROM_NAME = 'NEXA SaaS';

    /**
     * Globale informatieaanvraag-template voor de Nexa SaaS-hoofdwebsite.
     */
    public function ensureExists(): EmailTemplate
    {
        $this->ensurePackageField();

        $fieldIds = InfoRequestFormField::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return EmailTemplate::query()->updateOrCreate(
            [
                'type' => 'informatieaanvraag',
                'company_id' => null,
            ],
            [
                'name' => self::TEMPLATE_NAME,
                'subject' => 'Nieuwe aanvraag via nexasuite.nl – {{ VOORNAAM }} {{ ACHTERNAAM }}',
                'description' => 'Aanvraagformulier op de NEXA Suite-website. Ontvanger: '.self::RECIPIENT_EMAIL.'.',
                'html_content' => $this->html(),
                'text_content' => $this->text(),
                'is_active' => true,
                'recipient_type' => 'email',
                'recipient_email' => self::RECIPIENT_EMAIL,
                'form_field_order' => $fieldIds !== [] ? $fieldIds : null,
            ]
        );
    }

    private function ensurePackageField(): void
    {
        InfoRequestFormField::query()->updateOrCreate(
            ['name' => 'pakket'],
            [
                'label' => 'Pakket',
                'is_required' => false,
                'validation_rule' => 'nexa_package',
                'sort_order' => 45,
            ]
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
    <meta name="color-scheme" content="light">
    <title>Nieuwe aanvraag NEXA Suite</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <table role="presentation" class="info-request-email-card" width="100%" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    <tr>
                        <td class="info-request-email-header" width="100%" bgcolor="#0f172a" style="padding: 24px 30px; background-color: #0f172a; border-radius: 8px 8px 0 0; width: 100%;">
                            <p style="margin: 0 0 6px; color: #94a3b8; font-size: 13px; letter-spacing: 0.04em;">NEXA Suite</p>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; line-height: 1.3;">Nieuwe aanvraag via nexasuite.nl</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-request-email-body" width="100%" bgcolor="#ffffff" style="padding: 30px; background-color: #ffffff; color: #333333; width: 100%;">
                            <p style="margin: 0 0 16px; color: #333333; font-size: 16px; line-height: 1.5;">
                                Er is een nieuwe aanvraag binnengekomen via het contactformulier.
                            </p>
                            <table role="presentation" class="info-request-fields" width="100%" style="width: 100%; border-collapse: collapse; margin: 0; font-size: 15px; color: #333333; background-color: #ffffff; text-align: left; table-layout: fixed;">
                                <colgroup><col width="175" style="width: 175px;"><col width="*" style="width: auto;"></colgroup>
{{ DYNAMIC_FORM_FIELDS }}
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-request-email-footer" width="100%" bgcolor="#f9fafb" style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb; width: 100%;">
                            <p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center;">
                                Antwoord deze e-mail om de klant te bereiken.<br>
                                NEXA Suite · nexasuite.nl
                            </p>
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
Nieuwe aanvraag via nexasuite.nl

Voornaam: {{ VOORNAAM }}
Achternaam: {{ ACHTERNAAM }}
E-mail: {{ EMAIL_AANVRAAG }}
Telefoon: {{ TELEFOONNUMMER }}
Pakket: {{ PAKKET }}
Datum: {{ DATUM_AANVRAAG }}

Omschrijving:
{{ OMSCHRIJVING }}

NEXA Suite · nexasuite.nl
TEXT;
    }
}
