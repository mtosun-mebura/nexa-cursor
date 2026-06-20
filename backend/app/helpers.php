<?php

use Carbon\Carbon;

if (! function_exists('admin_date_picker_display')) {
    /**
     * Waarde voor weergave in KT-datepicker (dd-mm-jjjj).
     */
    function admin_date_picker_display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str)) {
            try {
                return Carbon::parse(substr($str, 0, 10))->format('d-m-Y');
            } catch (\Exception) {
                return $str;
            }
        }

        return $str;
    }
}

if (! function_exists('parse_admin_date')) {
    /**
     * Parse admin-datepicker-waarde (dd-mm-jjjj of jjjj-mm-dd) naar Y-m-d.
     */
    function parse_admin_date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str)) {
            return substr($str, 0, 10);
        }

        foreach (['d-m-Y', 'd-m-y', 'd/m/Y', 'd/m/y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $str)->format('Y-m-d');
            } catch (\Exception) {
                continue;
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}

if (! function_exists('normalize_iban')) {
    /**
     * IBAN normaliseren: hoofdletters, zonder spaties.
     */
    function normalize_iban(mixed $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
    }
}

if (! function_exists('is_valid_iban')) {
    /**
     * IBAN valideren (ISO 13616 mod-97).
     */
    function is_valid_iban(mixed $value): bool
    {
        $iban = normalize_iban($value);
        $length = strlen($iban);

        if ($length < 15 || $length > 34) {
            return false;
        }

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $checksum = 0;

        foreach (str_split($rearranged) as $char) {
            $digits = ctype_alpha($char) ? (string) (ord($char) - 55) : $char;

            foreach (str_split($digits) as $digit) {
                $checksum = ($checksum * 10 + (int) $digit) % 97;
            }
        }

        return $checksum === 1;
    }
}

if (! function_exists('transport_admin_back_url')) {
    /**
     * Veilige terug-URL voor contractvervoer-admin (query ?return=).
     */
    function transport_admin_back_url(\Illuminate\Http\Request $request, string $defaultUrl): string
    {
        $return = $request->input('return', $request->query('return'));
        if (! is_string($return) || trim($return) === '') {
            return $defaultUrl;
        }

        $return = trim($return);
        $parsed = parse_url($return);
        $path = $parsed['path'] ?? '';

        if ($path === '' || ! str_starts_with($path, '/admin/taxi')) {
            return $defaultUrl;
        }

        if (isset($parsed['host']) && $parsed['host'] !== $request->getHost()) {
            return $defaultUrl;
        }

        return $return;
    }
}

if (! function_exists('transport_admin_url_with_return')) {
    /**
     * Voeg ?return= toe zodat Terug naar de vorige pagina kan.
     */
    function transport_admin_url_with_return(string $url, ?string $returnUrl = null): string
    {
        $return = $returnUrl ?? url()->current();

        return $url.(str_contains($url, '?') ? '&' : '?').'return='.urlencode($return);
    }
}
