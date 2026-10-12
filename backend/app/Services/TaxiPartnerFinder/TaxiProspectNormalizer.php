<?php

namespace App\Services\TaxiPartnerFinder;

use App\Services\CompanySearch\DomainService;

class TaxiProspectNormalizer
{
    public function __construct(
        protected DomainService $domains,
    ) {}

    public function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $name = preg_replace('/\b(b\.?\s*v\.?|n\.?\s*v\.?|v\.?\s*o\.?\s*f\.?|eenmanszaak)\b/u', '', $name) ?? $name;
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    public function normalizePostalCode(?string $postalCode): ?string
    {
        if ($postalCode === null) {
            return null;
        }
        $raw = strtoupper(preg_replace('/\s+/', '', trim($postalCode)) ?? '');
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^([0-9]{4})([A-Z]{2})$/', $raw, $m)) {
            return $m[1].$m[2];
        }

        return $raw;
    }

    public function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    public function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        $digits = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if (strlen(preg_replace('/\D/', '', $digits) ?? '') < 8) {
            return null;
        }

        return $phone;
    }

    public function normalizeWebsite(?string $website): ?string
    {
        $website = trim((string) $website);
        if ($website === '') {
            return null;
        }
        if (! str_starts_with($website, 'http://') && ! str_starts_with($website, 'https://')) {
            $website = 'https://'.$website;
        }

        return $website;
    }

    public function websiteDomain(?string $website): ?string
    {
        $website = $this->normalizeWebsite($website);
        if ($website === null) {
            return null;
        }

        $host = $this->domains->host($website);

        return $host !== '' ? $host : null;
    }

    public function normalizeKvk(?string $kvk): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $kvk) ?? '';
        if (strlen($digits) !== 8) {
            return null;
        }

        return $digits;
    }

    /**
     * @return array{street: ?string, house_number: ?string}
     */
    public function splitStreetLine(?string $line): array
    {
        $line = html_entity_decode(str_replace("\xc2\xa0", ' ', trim((string) $line)), ENT_QUOTES | ENT_HTML5);
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
        if ($line === '') {
            return ['street' => null, 'house_number' => null];
        }
        if (preg_match('/^(.*?)[\s,]+(\d+\s*[A-Za-z]?)$/u', $line, $m)) {
            return [
                'street' => trim($m[1]) !== '' ? trim($m[1]) : null,
                'house_number' => trim($m[2]),
            ];
        }

        return ['street' => $line, 'house_number' => null];
    }

    /**
     * @return array{postal_code: ?string, city: ?string}
     */
    public function splitPostalCity(?string $line): array
    {
        $line = html_entity_decode(str_replace("\xc2\xa0", ' ', trim((string) $line)), ENT_QUOTES | ENT_HTML5);
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
        if (preg_match('/^([0-9]{4}\s*[A-Za-z]{2})\s+(.+)$/u', $line, $m)) {
            return [
                'postal_code' => $this->normalizePostalCode($m[1]),
                'city' => trim($m[2]) !== '' ? trim($m[2]) : null,
            ];
        }

        return ['postal_code' => null, 'city' => $line !== '' ? $line : null];
    }
}
