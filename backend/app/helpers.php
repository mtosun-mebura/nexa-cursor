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

if (! function_exists('parse_admin_month')) {
    /**
     * Parse admin-maandpicker-waarde (mm-jjjj of jjjj-mm) naar Y-m.
     */
    function parse_admin_month(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})(?:-\d{2})?$/', $str, $m)) {
            $month = (int) $m[2];
            if ($month < 1 || $month > 12) {
                return null;
            }

            return sprintf('%04d-%02d', (int) $m[1], $month);
        }

        if (preg_match('/^(\d{1,2})[-\/](\d{4})$/', $str, $m)) {
            $month = (int) $m[1];
            if ($month < 1 || $month > 12) {
                return null;
            }

            return sprintf('%04d-%02d', (int) $m[2], $month);
        }

        $date = parse_admin_date($str);
        if ($date !== null) {
            return substr($date, 0, 7);
        }

        return null;
    }
}

if (! function_exists('admin_month_picker_display')) {
    /**
     * Waarde voor weergave in KT-maandpicker (mm-jjjj).
     */
    function admin_month_picker_display(mixed $value): string
    {
        $parsed = parse_admin_month($value);
        if ($parsed === null) {
            return '';
        }

        [$year, $month] = explode('-', $parsed);

        return sprintf('%02d-%04d', (int) $month, (int) $year);
    }
}

if (! function_exists('admin_datetime_picker_display')) {
    /**
     * Waarde voor weergave in KT-datepicker met tijd (dd-mm-jjjj uu:mm).
     */
    function admin_datetime_picker_display(mixed $value): string
    {
        $parsed = parse_admin_datetime($value);

        if ($parsed === null) {
            return '';
        }

        return Carbon::parse($parsed)->format('d-m-Y H:i');
    }
}

if (! function_exists('parse_admin_datetime')) {
    /**
     * Parse admin-datetimepicker-waarde naar Y-m-d H:i:s.
     */
    function parse_admin_datetime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $str, $m)) {
            return Carbon::create(
                (int) $m[3],
                (int) $m[2],
                (int) $m[1],
                (int) ($m[4] ?? 0),
                (int) ($m[5] ?? 0),
                (int) ($m[6] ?? 0),
            )->format('Y-m-d H:i:s');
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?/', $str, $m)) {
            return Carbon::create(
                (int) $m[1],
                (int) $m[2],
                (int) $m[3],
                (int) ($m[4] ?? 0),
                (int) ($m[5] ?? 0),
                (int) ($m[6] ?? 0),
            )->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse($str)->format('Y-m-d H:i:s');
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

if (! function_exists('iban_country_length')) {
    /**
     * Officiële IBAN-lengte per land (ISO 13616-register).
     */
    function iban_country_length(string $country): ?int
    {
        static $lengths = [
            'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16,
            'BG' => 22, 'BH' => 22, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28,
            'CZ' => 24, 'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24,
            'FI' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18,
            'GR' => 27, 'GT' => 28, 'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IQ' => 23,
            'IS' => 26, 'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LC' => 32,
            'LI' => 21, 'LT' => 20, 'LU' => 20, 'LV' => 21, 'MC' => 27, 'MD' => 24, 'ME' => 22,
            'MK' => 19, 'MR' => 27, 'MT' => 31, 'MU' => 30, 'NL' => 18, 'NO' => 15, 'PK' => 24,
            'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22, 'SA' => 24,
            'SC' => 31, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27, 'ST' => 25, 'TL' => 23,
            'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22, 'VG' => 24, 'XK' => 20,
        ];

        return $lengths[strtoupper($country)] ?? null;
    }
}

if (! function_exists('is_valid_iban')) {
    /**
     * IBAN valideren: landlengte uit het register plus ISO 13616 mod-97.
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

        $expected = iban_country_length(substr($iban, 0, 2));
        if ($expected === null || $length !== $expected) {
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

if (! function_exists('nexa_hero_highlight_phrases')) {
    /**
     * Highlightwoorden uit het builder-veld, gescheiden met |.
     *
     * @return list<string>
     */
    function nexa_hero_highlight_phrases(mixed $highlight): array
    {
        $phrases = array_map('trim', explode('|', (string) $highlight));

        return array_values(array_filter($phrases, static fn (string $phrase): bool => $phrase !== ''));
    }
}

if (! function_exists('nexa_hero_title_html')) {
    /**
     * Hero-titel met Enter-regels en highlightwoorden (scheid met |).
     */
    function nexa_hero_title_html(
        mixed $title,
        mixed $highlight = '',
        string $highlightColor = '',
        string $highlightClass = ''
    ): string {
        $title = str_replace(["\r\n", "\r"], "\n", (string) $title);
        $phrases = nexa_hero_highlight_phrases($highlight);
        usort($phrases, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $colorOk = $highlightColor !== '' && (bool) preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $highlightColor);
        $withBreaks = static fn (string $text): string => nl2br(e($text), false);

        $spanOpen = '<span';
        if ($highlightClass !== '') {
            $spanOpen .= ' class="'.e($highlightClass).'"';
        }
        if ($colorOk) {
            $spanOpen .= ' style="color: '.$highlightColor.';"';
        }
        $spanOpen .= '>';

        $matches = [];
        foreach ($phrases as $phrase) {
            $offset = 0;
            $phraseLen = mb_strlen($phrase);
            while (($pos = mb_strpos($title, $phrase, $offset)) !== false) {
                $before = $pos > 0 ? mb_substr($title, $pos - 1, 1) : '';
                $after = mb_substr($title, $pos + $phraseLen, 1);
                $letter = '/\p{L}/u';
                $bounded = ($before === '' || ! preg_match($letter, $before))
                    && ($after === '' || ! preg_match($letter, $after));
                $overlaps = false;
                foreach ($matches as $existing) {
                    if ($pos < $existing['end'] && ($pos + $phraseLen) > $existing['start']) {
                        $overlaps = true;
                        break;
                    }
                }
                if ($bounded && ! $overlaps) {
                    $matches[] = [
                        'start' => $pos,
                        'end' => $pos + $phraseLen,
                        'text' => mb_substr($title, $pos, $phraseLen),
                    ];
                }
                $offset = $pos + max(1, $phraseLen);
            }
        }

        if ($matches === []) {
            return $withBreaks($title);
        }

        usort($matches, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $html = '';
        $cursor = 0;
        foreach ($matches as $match) {
            if ($match['start'] > $cursor) {
                $html .= $withBreaks(mb_substr($title, $cursor, $match['start'] - $cursor));
            }
            $html .= $spanOpen.e($match['text']).'</span>';
            $cursor = $match['end'];
        }
        if ($cursor < mb_strlen($title)) {
            $html .= $withBreaks(mb_substr($title, $cursor));
        }

        return $html;
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
