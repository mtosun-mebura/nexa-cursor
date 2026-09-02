<?php

namespace App\Support;

/**
 * Tenant-configuraties die standaard alleen super-admin mag beheren.
 * Super-admin kan per tenant-gebruiker gerichte toegang geven.
 */
final class TenantConfigCapability
{
    public const DOMAIN = 'domain';

    public const MODULES = 'modules';

    public const WEBSITE = 'website';

    public const MAIL = 'mail';

    public const GOOGLE_SEO = 'google_seo';

    public const WHATSAPP = 'whatsapp';

    public const MOLLIE = 'mollie';

    /**
     * @return list<array{key: string, label: string, hint: string, wizard_step: int}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::DOMAIN,
                'label' => 'Domein',
                'hint' => 'Tenant-domeinen koppelen in de wizard.',
                'wizard_step' => 3,
            ],
            [
                'key' => self::MODULES,
                'label' => 'Modules',
                'hint' => 'Modules koppelen en activeren voor deze tenant.',
                'wizard_step' => 4,
            ],
            [
                'key' => self::WEBSITE,
                'label' => 'Website',
                'hint' => 'Website-builder: pagina’s, thema-blokken en componenten.',
                'wizard_step' => 6,
            ],
            [
                'key' => self::MAIL,
                'label' => 'Mailserver',
                'hint' => 'SMTP en afzender van deze tenant.',
                'wizard_step' => 7,
            ],
            [
                'key' => self::GOOGLE_SEO,
                'label' => 'Google SEO',
                'hint' => 'Google Analytics, Search Console en reviews.',
                'wizard_step' => 8,
            ],
            [
                'key' => self::WHATSAPP,
                'label' => 'WhatsApp',
                'hint' => 'Widget, click-to-chat en boekingsnummer.',
                'wizard_step' => 9,
            ],
            [
                'key' => self::MOLLIE,
                'label' => 'Mollie',
                'hint' => 'Mollie API-sleutel en betaalinstellingen van deze tenant.',
                'wizard_step' => 9,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'key');
    }

    public static function isValid(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    /**
     * Wizardstappen die een configuratie-toestemming nodig hebben.
     *
     * @return list<int>
     */
    public static function wizardConfigSteps(): array
    {
        return [3, 4, 6, 7, 8, 9];
    }

    /**
     * @return list<string>
     */
    public static function keysForWizardStep(int $step): array
    {
        $keys = [];
        foreach (self::definitions() as $definition) {
            if ((int) $definition['wizard_step'] === $step) {
                $keys[] = $definition['key'];
            }
        }

        return $keys;
    }

    public static function label(string $key): string
    {
        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition['label'];
            }
        }

        return $key;
    }

    public static function wizardStepFor(string $key): ?int
    {
        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return (int) $definition['wizard_step'];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function labelsFor(array $keys): array
    {
        $labels = [];
        foreach ($keys as $key) {
            if (is_string($key) && self::isValid($key)) {
                $labels[] = self::label($key);
            }
        }

        return $labels;
    }
}
