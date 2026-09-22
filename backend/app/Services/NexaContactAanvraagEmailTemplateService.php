<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\InfoRequestFormField;

class NexaContactAanvraagEmailTemplateService
{
    public const RECIPIENT_EMAIL = 'info@nexasuite.nl';

    public const TEMPLATE_NAME = 'NEXA Suite contactaanvraag';

    public const FROM_NAME = 'NEXA Suite';

    /**
     * Globale informatieaanvraag-template voor de NEXA Suite-hoofdwebsite.
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

        $template = EmailTemplate::query()->updateOrCreate(
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

        \App\Support\EmailCardHtml::stripRedundantBrandKickerFromStoredTemplates();

        return $template;
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
    <style type="text/css" data-info-request-fields-responsive="1">
        @media only screen and (max-width: 600px) {
            .info-request-email-header,
            .info-request-email-body,
            .info-request-email-footer { padding: 16px !important; }
            table.info-request-fields { width: 100% !important; table-layout: auto !important; }
            table.info-request-fields td.info-request-field-label,
            table.info-request-fields td.info-request-field-value,
            table.info-request-fields td.info-request-field-value--multiline {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                text-align: left !important;
                white-space: normal !important;
            }
            table.info-request-fields td.info-request-field-label { padding: 10px 0 0 !important; }
            table.info-request-fields td.info-request-field-value,
            table.info-request-fields td.info-request-field-value--multiline { padding: 2px 0 10px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <table role="presentation" class="info-request-email-card" width="100%" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    <tr>
                        <td class="info-request-email-header" width="100%" bgcolor="#0f172a" style="padding: 24px 30px; background-color: #0f172a; border-radius: 8px 8px 0 0; width: 100%;">
                            {{ NEXA_LOGO }}
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; line-height: 1.3;">Nieuwe aanvraag via nexasuite.nl</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-request-email-body" width="100%" bgcolor="#ffffff" style="padding: 30px; background-color: #ffffff; color: #333333; width: 100%;">
                            <p style="margin: 0 0 16px; color: #333333; font-size: 16px; line-height: 1.5;">
                                Er is een nieuwe aanvraag binnengekomen via het contactformulier.
                            </p>
                            <table role="presentation" class="info-request-fields" width="100%" style="width: 100%; max-width: 100%; border-collapse: collapse; margin: 0; font-size: 15px; color: #333333; background-color: #ffffff; text-align: left; table-layout: auto;">
{{ DYNAMIC_FORM_FIELDS }}
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="info-request-email-footer" width="100%" bgcolor="#f9fafb" style="padding: 20px 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb; width: 100%;">
                            <p style="margin: 0; color: #6b7280; font-size: 14px; text-align: center; line-height: 1.6;">
                                Antwoord deze e-mail om de klant te bereiken.
                            </p>
                            <p style="margin: 16px 0 0; color: #6b7280; font-size: 13px; text-align: center; line-height: 1.6;">
                                Powered by NEXA Suite.
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

Antwoord deze e-mail om de klant te bereiken.

Powered by NEXA Suite.
TEXT;
    }
}
