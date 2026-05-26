<?php

namespace App\Support;

/**
 * Client-side hint + test helpers aligned with {@see \App\Http\Controllers\Admin\AdminCompanyWizardController}
 * stap 1 validatieregels. Houd evaluateWizardStep1Field() synchroon met de JS in resources/js/admin-field-hints.js.
 */
final class AdminFieldValidationPatterns
{
    /** @return array<string, array<string, mixed>> */
    public static function wizardStep1Hints(): array
    {
        return [
            'name' => [
                'kind' => 'regex',
                'regex' => '^.{2,255}$',
                'flags' => 'u',
                'required' => true,
                'valid' => 'Bedrijfsnaam ziet er goed uit.',
                'invalid' => 'Minimaal 2 en maximaal 255 tekens.',
            ],
            'email' => [
                'kind' => 'email',
                'required' => true,
                'valid' => 'E-mailadres is geldig.',
                'invalid' => 'Gebruik een geldig adres met @ en een domein met een punt (bijv. naam@voorbeeld.nl).',
            ],
            'phone' => [
                'kind' => 'regex',
                'regex' => '^(\\+31|0)[1-9][0-9]{8}$',
                'flags' => '',
                'required' => true,
                'normalize' => 'nl_phone',
                'valid' => 'Telefoonnummer heeft een geldige opmaak.',
                'invalid' => 'Gebruik een Nederlands nummer: 10 cijfers, begin met 0 of +31 (bijv. 0612345678 of +31612345678). Spaties worden genegeerd.',
            ],
            'postal_code' => [
                'kind' => 'regex',
                'regex' => '^[1-9][0-9]{3}\\s?[A-Za-z]{2}$',
                'flags' => 'i',
                'required' => true,
                'normalize' => 'nl_postal',
                'valid' => 'Postcode is geldig.',
                'invalid' => 'Gebruik een Nederlandse postcode (bijv. 1234 AB).',
            ],
            'house_number' => [
                'kind' => 'regex',
                'regex' => '^.{1,20}$',
                'flags' => 'u',
                'required' => true,
                'valid' => 'Huisnummer is ingevuld.',
                'invalid' => 'Maximaal 20 tekens.',
            ],
            'street' => [
                'kind' => 'regex',
                'regex' => '^.{2,255}$',
                'flags' => 'u',
                'required' => true,
                'valid' => 'Straat is ingevuld.',
                'invalid' => 'Minimaal 2 tekens.',
            ],
            'city' => [
                'kind' => 'regex',
                'regex' => '^.{2,255}$',
                'flags' => 'u',
                'required' => true,
                'valid' => 'Plaats is ingevuld.',
                'invalid' => 'Minimaal 2 tekens.',
            ],
            'kvk_number' => [
                'kind' => 'regex',
                'regex' => '^$|^[0-9]{8}$',
                'required' => false,
                'valid' => 'KVK-nummer is geldig (8 cijfers).',
                'invalid' => 'KVK moet 8 cijfers zijn of leeg blijven.',
            ],
            'website' => [
                'kind' => 'url',
                'required' => false,
                'valid' => 'URL is geldig.',
                'invalid' => 'Voer een geldige URL in (https://…).',
            ],
        ];
    }

    /**
     * @return 'empty'|'neutral'|'valid'|'invalid'
     */
    public static function evaluateWizardStep1Field(string $field, string $value): string
    {
        $hints = self::wizardStep1Hints();
        if (! isset($hints[$field])) {
            return 'neutral';
        }

        $cfg = $hints[$field];
        $required = (bool) ($cfg['required'] ?? false);
        $v = self::normalizeValue($field, $value, $cfg);

        if ($v === '') {
            return $required ? 'empty' : 'neutral';
        }

        return match ($cfg['kind'] ?? 'regex') {
            'email' => filter_var($v, FILTER_VALIDATE_EMAIL) !== false ? 'valid' : 'invalid',
            'url' => self::isValidHttpUrl($v) ? 'valid' : 'invalid',
            'regex' => self::matchesRegex($v, (string) $cfg['regex'], (string) ($cfg['flags'] ?? ''))
                ? 'valid'
                : 'invalid',
            default => 'neutral',
        };
    }

    private static function normalizeValue(string $field, string $value, array $cfg): string
    {
        $v = trim($value);
        if (($cfg['normalize'] ?? '') === 'nl_postal') {
            return strtoupper(preg_replace('/\s+/', '', $v) ?? '');
        }
        if (($cfg['normalize'] ?? '') === 'nl_phone') {
            $v = preg_replace('/\s+/', '', $v) ?? '';
            $v = str_replace(['-', '.'], '', $v);

            return $v;
        }

        return $v;
    }

    private static function matchesRegex(string $value, string $pattern, string $flags): bool
    {
        $delim = '#';
        $regex = $delim.str_replace($delim, '\\'.$delim, $pattern).$delim.$flags;

        return (bool) preg_match($regex, $value);
    }

    private static function isValidHttpUrl(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        if (! preg_match('#^https?://#i', $value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
