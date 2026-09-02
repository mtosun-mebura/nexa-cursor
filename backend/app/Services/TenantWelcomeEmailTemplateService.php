<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Support\NexaBranding;

class TenantWelcomeEmailTemplateService
{
    public const TYPE = 'tenant_welcome';

    public const TEMPLATE_NAME = 'Welkomstmail tenant (company-admin)';

    public const ADMIN_LOGIN_URL = 'https://nexasuite.nl/admin';

    public const HANDLEIDING_URL = 'https://nexasuite.nl/admin/handleiding';

    /**
     * @return array<string, string>
     */
    public static function variableLabels(): array
    {
        return [
            'USER_NAME' => 'Naam van de beheerder',
            'USER_EMAIL' => 'E-mailadres (gebruikersnaam)',
            'COMPANY_NAME' => 'Bedrijfsnaam',
            'PACKAGE_NAME' => 'Pakketnaam',
            'PACKAGE_FEATURES_HTML' => 'Kenmerken van het pakket (HTML-lijst)',
            'PACKAGE_FEATURES_TEXT' => 'Kenmerken van het pakket (platte tekst)',
            'ADMIN_LOGIN_URL' => 'Link naar de admin (nexasuite.nl/admin)',
            'HANDLEIDING_URL' => 'Link naar de handleiding',
            'NEXA_LOGO' => 'Nexa-logo (HTML, linksboven)',
            'COMPANY_LOGO' => 'Bedrijfslogo (HTML)',
        ];
    }

    /**
     * Dummywaarden voor admin-preview en testmail. Altijd het Business-pakket.
     *
     * @return array<string, string>
     */
    public function previewVariables(?Company $company = null): array
    {
        $package = app(NexaPricingService::class)->packageByKey('business') ?? [];
        $features = is_array($package['features'] ?? null) ? $package['features'] : [];
        $packageName = (string) ($package['name'] ?? 'Business');
        $companyName = $company?->name ?: 'Horizon Taxi';

        return array_merge(
            [
                'USER_NAME' => 'Lisa Vermeer',
                'USER_EMAIL' => 'lisa@horizontaxi.nl',
                'COMPANY_NAME' => e($companyName),
                'PACKAGE_NAME' => e($packageName),
                'PACKAGE_FEATURES_HTML' => self::featuresHtml($features),
                'PACKAGE_FEATURES_TEXT' => self::featuresText($features),
                'ADMIN_LOGIN_URL' => self::ADMIN_LOGIN_URL,
                'HANDLEIDING_URL' => self::HANDLEIDING_URL,
                'ACTION_URL' => self::ADMIN_LOGIN_URL,
            ],
            app(CompanyEmailLogoService::class)->templateVariable($company?->id, $companyName),
            NexaBranding::emailLogoTemplateVariable()
        );
    }

    /**
     * @param  list<mixed>  $features
     */
    public static function featuresHtml(array $features): string
    {
        $features = self::normalizedFeatures($features);
        if ($features === []) {
            return '<p>Zie de handleiding voor de onderdelen van je pakket.</p>';
        }

        return '<ul style="margin:0;padding-left:20px;">'
            .implode('', array_map(static fn (string $item) => '<li>'.e($item).'</li>', $features))
            .'</ul>';
    }

    /**
     * @param  list<mixed>  $features
     */
    public static function featuresText(array $features): string
    {
        $features = self::normalizedFeatures($features);
        if ($features === []) {
            return 'Zie de handleiding voor de onderdelen van je pakket.';
        }

        return implode("\n", array_map(static fn (string $item) => '- '.$item, $features));
    }

    /**
     * @param  list<mixed>  $features
     * @return list<string>
     */
    private static function normalizedFeatures(array $features): array
    {
        return array_values(array_filter(
            array_map(static fn ($item) => trim((string) $item), $features),
            static fn (string $item) => $item !== ''
        ));
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

            $this->ensureNexaLogoPlaceholder($existing);
            $this->upgradeStoredTemplates();

            return $existing->fresh() ?? $existing;
        }

        return EmailTemplate::query()->create([
            'type' => self::TYPE,
            'company_id' => null,
            'name' => self::TEMPLATE_NAME,
            'subject' => 'Welkom bij NEXA Suite — toegang tot {{ COMPANY_NAME }}',
            'description' => 'Algemene welkomstmail voor nieuwe company-admins na het afnemen van een abonnement. Bevat inloginstructie via een eenmalige code (geen wachtwoord) en knoppen naar de admin.',
            'html_content' => $this->html(),
            'text_content' => $this->text(),
            'is_active' => true,
            'recipient_type' => 'email',
            'recipient_email' => null,
        ]);
    }

    public function resolveActive(): ?EmailTemplate
    {
        return $this->ensureExists();
    }

    /**
     * Voeg {{ NEXA_LOGO }} toe aan bestaande templates die nog de oude koptekst hebben.
     */
    private function ensureNexaLogoPlaceholder(EmailTemplate $template): void
    {
        $html = (string) $template->html_content;
        if ($html === '') {
            return;
        }

        $updated = $html;
        if (! str_contains($updated, 'NEXA_LOGO') && ! str_contains($updated, 'NEXA_BRAND_LOGO')) {
            $updated = preg_replace(
                '/<p[^>]*>\s*NEXA Suite\s*<\/p>/i',
                '{{ NEXA_LOGO }}',
                $updated,
                1
            ) ?? $updated;
        }

        if (! str_contains($updated, 'Open de admin</span>')) {
            $updated = preg_replace(
                '/>(\s*)Open de admin(\s*)<\/a>/',
                '>$1<span style="color: #ffffff;">Open de admin</span>$2</a>',
                $updated
            ) ?? $updated;
        }
        if (! str_contains($updated, 'Open de handleiding</span>')) {
            $updated = preg_replace(
                '/>(\s*)Open de handleiding(\s*)<\/a>/',
                '>$1<span style="color: #ffffff;">Open de handleiding</span>$2</a>',
                $updated
            ) ?? $updated;
        }

        if ($updated === $html) {
            return;
        }

        $template->html_content = $updated;
        $template->save();
    }

    /**
     * Ronde hoeken op het inlogkader (border-collapse:collapse blokkeert radius) en
     * tijdelijk wachtwoord vervangen door instructie voor een eenmalige code.
     */
    public function upgradeStoredTemplates(): void
    {
        EmailTemplate::query()
            ->where('type', self::TYPE)
            ->get()
            ->each(function (EmailTemplate $template): void {
                $html = (string) $template->html_content;
                $text = (string) ($template->text_content ?? '');
                $description = (string) ($template->description ?? '');
                $updatedHtml = $this->roundBorderedTables($html);
                $updatedHtml = $this->replacePasswordLoginBox($updatedHtml);
                $updatedHtml = $this->informalizeCopy($this->upgradeFirstStepsHtml($updatedHtml));
                $updatedText = $this->informalizeCopy($this->upgradeLoginText($text));
                $updatedDescription = $description;
                if (str_contains(mb_strtolower($description), 'tijdelijk wachtwoord')) {
                    $updatedDescription = 'Algemene welkomstmail voor nieuwe company-admins na het afnemen van een abonnement. Bevat inloginstructie via een eenmalige code (geen wachtwoord) en knoppen naar de admin.';
                }

                $dirty = false;
                if ($updatedHtml !== $html) {
                    $template->html_content = $updatedHtml;
                    $dirty = true;
                }
                if ($updatedText !== $text) {
                    $template->text_content = $updatedText;
                    $dirty = true;
                }
                if ($updatedDescription !== $description) {
                    $template->description = $updatedDescription;
                    $dirty = true;
                }
                if ($dirty) {
                    $template->save();
                }
            });
    }

    private function loginBoxHtml(): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">'
            .'<tr>'
            .'<td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">'
            .'<p style="margin:0 0 8px;font-size:12px;color:#0f172a;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Inloggen</p>'
            .'<p style="margin:0 0 6px;font-size:14px;"><strong>Gebruikersnaam:</strong> {{ USER_EMAIL }}</p>'
            .'<p style="margin:0 0 6px;font-size:14px;"><strong>Eerste login:</strong> vraag op het inlogscherm een eenmalige code aan.</p>'
            .'<p style="margin:0;font-size:13px;color:#334155;">Je ontvangt de code in een aparte e-mail. Die is 15 minuten geldig. Daarna kies je zelf een wachtwoord. Er gaat geen wachtwoord mee in deze welkomstmail.</p>'
            .'</td>'
            .'</tr>'
            .'</table>';
    }

    private function roundBorderedTables(string $html): string
    {
        $updated = preg_replace_callback(
            '/<table\b[^>]*>/i',
            static function (array $match): string {
                $tag = $match[0];
                if (! preg_match('/border-radius\s*:/i', $tag)) {
                    return $tag;
                }
                if (preg_match('/border-collapse\s*:\s*collapse/i', $tag)) {
                    $tag = preg_replace(
                        '/border-collapse\s*:\s*collapse\s*;?/i',
                        'border-collapse:separate;border-spacing:0;',
                        $tag
                    ) ?? $tag;
                } elseif (preg_match('/border-collapse\s*:\s*separate/i', $tag) && ! preg_match('/border-spacing\s*:/i', $tag)) {
                    $tag = preg_replace(
                        '/border-collapse\s*:\s*separate\s*;?/i',
                        'border-collapse:separate;border-spacing:0;',
                        $tag
                    ) ?? $tag;
                }
                $tag = preg_replace('/border-radius\s*:\s*\d+px/i', 'border-radius:12px', $tag) ?? $tag;

                return $tag;
            },
            $html
        );

        return is_string($updated) ? $updated : $html;
    }

    private function replacePasswordLoginBox(string $html): string
    {
        if (! str_contains($html, 'TEMP_PASSWORD') && ! str_contains($html, 'Tijdelijk wachtwoord')) {
            return $html;
        }

        $replaced = preg_replace(
            '/<table\b[^>]*>[\s\S]{0,900}?Inloggen[\s\S]{0,1600}?(?:TEMP_PASSWORD|Tijdelijk wachtwoord)[\s\S]{0,900}?<\/table>/i',
            $this->loginBoxHtml(),
            $html,
            1
        );
        if (is_string($replaced) && $replaced !== $html) {
            return $replaced;
        }

        $stripped = preg_replace('/<p\b[^>]*>[\s\S]*?(?:TEMP_PASSWORD|Tijdelijk wachtwoord)[\s\S]*?<\/p>/i', '', $html) ?? $html;

        return str_replace('{{ TEMP_PASSWORD }}', '', $stripped);
    }

    private function upgradeFirstStepsHtml(string $html): string
    {
        $html = str_replace(
            'Log in met uw e-mailadres als gebruikersnaam en het tijdelijke wachtwoord.',
            'Klik op «Eerste keer inloggen» en vraag een eenmalige code aan voor dit e-mailadres.',
            $html
        );

        return str_replace(
            'Kies direct een eigen wachtwoord (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).',
            'Vul de code uit de volgende e-mail in en kies een eigen wachtwoord (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).',
            $html
        );
    }

    private function upgradeLoginText(string $text): string
    {
        if ($text === '' || (! str_contains($text, 'TEMP_PASSWORD') && ! str_contains($text, 'Tijdelijk wachtwoord'))) {
            return $text;
        }

        $text = preg_replace('/^Tijdelijk wachtwoord:.*$/m', 'Eerste login: vraag op het inlogscherm een eenmalige code aan.', $text) ?? $text;
        $text = str_replace('{{ TEMP_PASSWORD }}', '', $text);
        $text = str_replace(
            'Dit wachtwoord mag één keer worden gebruikt. Direct na het inloggen moet u een eigen wachtwoord kiezen. U kunt het scherm niet verlaten totdat dat is gebeurd.',
            'Je ontvangt de code in een aparte e-mail. Die is 15 minuten geldig. Daarna kies je zelf een wachtwoord. Er gaat geen wachtwoord mee in deze welkomstmail.',
            $text
        );
        $text = str_replace(
            'Log in met uw e-mailadres als gebruikersnaam en het tijdelijke wachtwoord.',
            'Klik op «Eerste keer inloggen» en vraag een eenmalige code aan voor dit e-mailadres.',
            $text
        );
        $text = str_replace(
            'Kies direct een eigen wachtwoord (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).',
            'Vul de code uit de volgende e-mail in en kies een eigen wachtwoord (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).',
            $text
        );

        return $text;
    }

    private function informalizeCopy(string $content): string
    {
        return str_replace(
            [
                'Hieronder staan uw inloggegevens en de eerste stappen.',
                'Hieronder ziet u hoe u voor het eerst inlogt.',
                'U ontvangt de code in een aparte e-mail. Die is 15 minuten geldig. Daarna kiest u zelf een wachtwoord.',
                'Daarna kiest u zelf een wachtwoord.',
                'onderdelen van uw pakket',
                'Wat zit er in uw pakket',
                'Heeft u vragen over uw abonnement of inloggen?',
                'Zie de handleiding voor de onderdelen van uw pakket.',
            ],
            [
                'Hieronder zie je hoe je voor het eerst inlogt.',
                'Hieronder zie je hoe je voor het eerst inlogt.',
                'Je ontvangt de code in een aparte e-mail. Die is 15 minuten geldig. Daarna kies je zelf een wachtwoord.',
                'Daarna kies je zelf een wachtwoord.',
                'onderdelen van je pakket',
                'Wat zit er in je pakket',
                'Heb je vragen over je abonnement of inloggen?',
                'Zie de handleiding voor de onderdelen van je pakket.',
            ],
            $content
        );
    }

    private function html(): string
    {
        $loginBox = $this->loginBoxHtml();

        return str_replace('<!--LOGIN_BOX-->', $loginBox, <<<'HTML'
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Welkom bij NEXA Suite</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; color: #111827;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 24px 12px;">
                <table role="presentation" width="100%" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    <tr>
                        <td bgcolor="#0f172a" style="padding: 28px 32px; background-color: #0f172a;">
                            {{ NEXA_LOGO }}
                            <h1 style="margin: 0; font-size: 22px; line-height: 1.3; color: #ffffff;">Welkom bij NEXA</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 32px;">
                            <p style="margin: 0 0 16px; font-size: 16px;">Beste {{ USER_NAME }},</p>
                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">
                                Bedankt voor het afnemen van een abonnement. We hebben een beheeraccount aangemaakt voor
                                <strong>{{ COMPANY_NAME }}</strong> (pakket <strong>{{ PACKAGE_NAME }}</strong>).
                                Hieronder zie je hoe je voor het eerst inlogt.
                            </p>

                            <!--LOGIN_BOX-->

                            <p style="margin: 0 0 18px; text-align: center;">
                                <a href="{{ ADMIN_LOGIN_URL }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px; padding: 12px 22px; border-radius: 6px;">
                                    <span style="color: #ffffff;">Open de admin</span>
                                </a>
                            </p>
                            <p style="margin: 0 0 22px; text-align: center; font-size: 12px; color: #64748b;">
                                Of ga naar <a href="{{ ADMIN_LOGIN_URL }}" style="color: #2563eb;">nexasuite.nl/admin</a>
                            </p>

                            <h2 style="margin: 0 0 10px; font-size: 16px;">Eerste stappen</h2>
                            <ol style="margin: 0 0 20px; padding-left: 20px; font-size: 14px; line-height: 1.7;">
                                <li>Open de admin via de knop hierboven.</li>
                                <li>Klik op «Eerste keer inloggen» en vraag een eenmalige code aan voor dit e-mailadres.</li>
                                <li>Vul de code uit de volgende e-mail in en kies een eigen wachtwoord (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).</li>
                                <li>Bekijk daarna de welkomsthandleiding. Die toont precies de onderdelen van je pakket.</li>
                            </ol>

                            <h2 style="margin: 0 0 10px; font-size: 16px;">Wat zit er in je pakket</h2>
                            <div style="margin: 0 0 20px; font-size: 14px; line-height: 1.7;">{{ PACKAGE_FEATURES_HTML }}</div>

                            <p style="margin: 0 0 18px; text-align: center;">
                                <a href="{{ HANDLEIDING_URL }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 11px 20px; border-radius: 6px;">
                                    <span style="color: #ffffff;">Open de handleiding</span>
                                </a>
                            </p>

                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.6;">
                                Heb je vragen over je abonnement of inloggen? Neem contact op via
                                <a href="mailto:info@nexasuite.nl" style="color: #2563eb;">info@nexasuite.nl</a>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 32px 24px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
                            Dit bericht is automatisch verstuurd door NEXA Suite.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML
        );
    }

    private function text(): string
    {
        return <<<'TEXT'
Welkom bij NEXA Suite

Beste {{ USER_NAME }},

Bedankt voor het afnemen van een abonnement. We hebben een beheeraccount aangemaakt voor {{ COMPANY_NAME }} (pakket {{ PACKAGE_NAME }}).

Inloggen
Gebruikersnaam: {{ USER_EMAIL }}
Eerste login: vraag op het inlogscherm een eenmalige code aan.

Je ontvangt de code in een aparte e-mail. Die is 15 minuten geldig. Daarna kies je zelf een wachtwoord. Er gaat geen wachtwoord mee in deze welkomstmail.

Admin openen: {{ ADMIN_LOGIN_URL }}
Handleiding: {{ HANDLEIDING_URL }}

Eerste stappen
1. Open de admin via nexasuite.nl/admin.
2. Klik op «Eerste keer inloggen» en vraag een eenmalige code aan voor dit e-mailadres.
3. Vul de code uit de volgende e-mail in en kies een eigen wachtwoord (minimaal 8 tekens, met hoofdletter, kleine letter en cijfer).
4. Bekijk daarna de welkomsthandleiding. Die toont precies de onderdelen van je pakket.

Wat zit er in je pakket
{{ PACKAGE_FEATURES_TEXT }}

Vragen? Mail info@nexasuite.nl.
TEXT;
    }
}
