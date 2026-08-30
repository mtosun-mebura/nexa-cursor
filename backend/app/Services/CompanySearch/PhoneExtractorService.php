<?php

namespace App\Services\CompanySearch;

class PhoneExtractorService
{
    public function fromHtml(string $html): ?string
    {
        if (preg_match_all('/href=["\']tel:([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $raw) {
                $phone = $this->normalize(urldecode($raw));
                if ($phone) {
                    return $phone;
                }
            }
        }
        if (preg_match_all('/(?:\+|00)?31[\s\-\.(]*[1-9](?:[\s\-\.(]*\d){8}|0[1-9](?:[\s\-\.(]*\d){8}/', $html, $matches)) {
            foreach ($matches[0] as $raw) {
                $phone = $this->normalize($raw);
                if ($phone) {
                    return $phone;
                }
            }
        }

        return null;
    }

    public function normalize(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '31') && strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) < 10 || strlen($digits) > 11) {
            return null;
        }
        if (! str_starts_with($digits, '0')) {
            return null;
        }
        $prefix = substr($digits, 0, 4);
        if (in_array($prefix, ['0800', '0900', '0906', '0909'], true)) {
            return null;
        }

        return $digits;
    }
}
