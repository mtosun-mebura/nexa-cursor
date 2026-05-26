<?php

namespace App\Support;

/**
 * Nederlandse telefoonnummers voor o.a. WhatsApp-instellingen: zelfde basisregel als {@see \App\Http\Requests\StoreUserRequest} (0 of +31, daarna 9 cijfers).
 */
final class DutchPhoneNumber
{
    /**
     * Lege invoer → lege string. Anders normaliseren naar +31 + 9 cijfers, of null bij ongeldige niet-lege invoer.
     */
    public static function normalizeOptionalNlToInternational(string $value): ?string
    {
        $trim = trim($value);
        if ($trim === '') {
            return '';
        }
        if (! preg_match('/^[+.\d\s()\-]+$/', $trim)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $trim);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (preg_match('/^0[1-9]\d{8}$/', $digits)) {
            return '+31'.substr($digits, 1);
        }
        if (preg_match('/^31[1-9]\d{8}$/', $digits)) {
            return '+'.$digits;
        }

        return null;
    }
}
