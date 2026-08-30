<?php

namespace App\Services\CompanySearch;

class ContactNameParser
{
    /** @var list<string> */
    private const TUSSENVOEGSELS = [
        'van der', 'van den', 'van de', 'van het', "van 't",
        'in het', 'in den', 'in de', "in 't",
        'aan den', 'aan der', 'aan de', 'aan het', "aan 't",
        'op den', 'op der', 'op de', 'op het', "op 't",
        'uit den', 'uit de', 'uit het', "uit 't",
        'voor de', 'over de',
        'van', 'de', 'den', 'der', 'ter', 'ten', 'te', 't', "'t", 'het',
        'op', 'in', 'aan', 'uit', 'tot', 'over', 'onder',
    ];

    /** @var list<string> */
    private const GENERIC_LOCAL_PARTS = [
        'info', 'contact', 'sales', 'verkoop', 'office', 'administratie',
        'service', 'support', 'hello', 'hallo', 'klantenservice', 'boekhouding',
        'mail', 'admin', 'kantoor', 'planning', 'dispatch', 'rit', 'taxi',
    ];

    /** @var list<string> */
    private const COMPANY_NEEDLES = [
        'taxi', 'bv', 'b.v', 'nv', 'vof', 'holding', 'bedrijf', 'company',
        'ltd', 'gmbh', 'transport', 'vervoer', 'installatie', 'onderneming',
        'kantoor', 'office', 'mail', 'contact', 'info', 'team', 'welkom',
        'home', 'bel',
    ];

    /**
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}|null
     */
    public function parse(?string $raw): ?array
    {
        $raw = $this->normalize($raw);
        if ($raw === '') {
            return null;
        }
        $raw = preg_replace('/^(dhr\.?|mevr\.?|mw\.?|mr\.?|mrs\.?|heer|mevrouw)\s+/iu', '', $raw) ?? $raw;
        $raw = trim($raw, " \t-–|");
        if ($raw === '' || $this->looksLikeCompany($raw)) {
            return null;
        }
        if (str_contains($raw, ',')) {
            [$last, $first] = array_pad(array_map('trim', explode(',', $raw, 2)), 2, '');

            return $this->fromParts($first, $last);
        }

        $tokens = preg_split('/\s+/u', $raw) ?: [];
        $tokens = array_values(array_filter($tokens, fn (string $token) => $token !== ''));
        if ($tokens === []) {
            return null;
        }
        if (count($tokens) === 1) {
            if ($this->looksLikeGivenName($tokens[0])) {
                return $this->pack($tokens[0], null, null);
            }

            return null;
        }

        $first = array_shift($tokens);
        if (! $this->looksLikeGivenName($first)) {
            return null;
        }
        [$middle, $lastTokens] = $this->splitTussenvoegsel($tokens);
        $last = trim(implode(' ', $lastTokens));
        if ($last === '' || $this->looksLikeCompany($last)) {
            return $this->pack($first, null, null);
        }

        return $this->pack($first, $middle, $last);
    }

    /**
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}|null
     */
    public function fromParts(?string $first, ?string $last): ?array
    {
        $first = $this->normalize($first);
        $last = $this->normalize($last);
        $first = preg_replace('/^(dhr\.?|mevr\.?|mw\.?|mr\.?|mrs\.?|heer|mevrouw)\s+/iu', '', $first) ?? $first;
        if ($last !== '' && str_contains($last, ' ')) {
            [$middle, $lastTokens] = $this->splitTussenvoegsel(preg_split('/\s+/u', $last) ?: []);
            $last = trim(implode(' ', $lastTokens));
            if ($first === '' && $last === '') {
                return null;
            }

            return $this->pack($first !== '' ? $first : null, $middle, $last !== '' ? $last : null);
        }
        if ($first === '' && $last === '') {
            return null;
        }
        if ($last !== '' && $this->looksLikeCompany($last)) {
            $last = '';
        }
        if ($first !== '' && ! $this->looksLikeGivenName($first) && $last === '') {
            return null;
        }

        return $this->pack($first !== '' ? $first : null, null, $last !== '' ? $last : null);
    }

    /**
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}|null
     */
    public function fromEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        $local = strstr($email, '@', true) ?: '';
        $local = preg_replace('/\+.*$/', '', $local) ?? $local;
        if ($local === '' || in_array($local, self::GENERIC_LOCAL_PARTS, true)) {
            return null;
        }
        if (! preg_match('/[a-z]/', $local) || preg_match('/\d{3,}/', $local)) {
            return null;
        }
        $local = str_replace(['.', '_', '-'], ' ', $local);
        $local = preg_replace('/\s+/', ' ', $local) ?? $local;

        return $this->parse($local);
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: ?string, 1: list<string>}
     */
    private function splitTussenvoegsel(array $tokens): array
    {
        $joined = mb_strtolower(implode(' ', $tokens), 'UTF-8');
        foreach (self::TUSSENVOEGSELS as $prefix) {
            $needle = $prefix.' ';
            if (str_starts_with($joined, $needle)) {
                $rest = trim(mb_substr($joined, mb_strlen($needle)));
                $lastTokens = $rest === '' ? [] : (preg_split('/\s+/u', $rest) ?: []);

                return [$prefix, $lastTokens];
            }
            if ($joined === $prefix) {
                return [$prefix, []];
            }
        }

        return [null, $tokens];
    }

    /**
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}
     */
    private function pack(?string $first, ?string $middle, ?string $last): array
    {
        return [
            'first_name' => $this->titleCase($first),
            'middle_name' => $this->lowerMiddle($middle),
            'last_name' => $this->titleCase($last),
        ];
    }

    private function looksLikeCompany(string $value): bool
    {
        $lower = mb_strtolower($value, 'UTF-8');
        foreach (self::COMPANY_NEEDLES as $needle) {
            if ($lower === $needle || str_contains($lower, ' '.$needle) || str_starts_with($lower, $needle.' ')) {
                return true;
            }
        }

        return (bool) preg_match('/\d/', $value)
            || str_contains($value, '@')
            || str_contains($value, 'http')
            || mb_strlen($value) > 60;
    }

    private function looksLikeGivenName(string $value): bool
    {
        $value = trim($value, ".'");
        if ($value === '' || mb_strlen($value) < 2 || mb_strlen($value) > 30) {
            return false;
        }
        if ($this->looksLikeCompany($value)) {
            return false;
        }

        return (bool) preg_match("/^[\p{L}][\p{L}'\-]{1,}$/u", $value);
    }

    private function titleCase(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private function lowerMiddle(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_strtolower($value, 'UTF-8');
    }

    private function normalize(?string $value): string
    {
        $value = html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
