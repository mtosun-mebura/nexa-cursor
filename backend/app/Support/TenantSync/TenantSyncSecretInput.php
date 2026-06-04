<?php

namespace App\Support\TenantSync;

final class TenantSyncSecretInput
{
    /**
     * Normaliseert wachtwoord-invoer uit formulieren (HTML-entities en optionele URL-encoding).
     */
    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/%[0-9A-Fa-f]{2}/', $decoded) === 1) {
            $decoded = rawurldecode($decoded);
        }

        return $decoded;
    }

    /**
     * URL-encode voor invoervelden (bijv. Memmo@Mdb! → Memmo%40Mdb%21). Alleen als er speciale tekens zijn.
     */
    public static function toUrlEncodedForm(?string $value): string
    {
        $plain = self::normalize($value);
        if ($plain === '') {
            return '';
        }

        if (preg_match('/[^A-Za-z0-9\-._~]/', $plain) !== 1) {
            return $plain;
        }

        return rawurlencode($plain);
    }
}
