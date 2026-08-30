<?php

namespace App\Services\CompanySearch;

class ContactNameExtractor
{
    public function __construct(
        protected ContactNameParser $parser,
    ) {}

    /**
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}|null
     */
    public function fromHtml(string $html, string $email = ''): ?array
    {
        foreach ($this->jsonLdNames($html) as $name) {
            $parsed = $this->parser->parse($name);
            if ($parsed !== null && filled($parsed['last_name'] ?? null)) {
                return $parsed;
            }
        }
        foreach ($this->mailtoNames($html) as $name) {
            $parsed = $this->parser->parse($name);
            if ($parsed !== null && filled($parsed['last_name'] ?? null)) {
                return $parsed;
            }
        }
        foreach ($this->labeledNames($html) as $name) {
            $parsed = $this->parser->parse($name);
            if ($parsed !== null && filled($parsed['last_name'] ?? null)) {
                return $parsed;
            }
        }
        if ($email !== '') {
            return $this->parser->fromEmail($email);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function jsonLdNames(string $html): array
    {
        if (! preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return [];
        }
        $names = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode(trim((string) $json), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($decoded)) {
                continue;
            }
            $this->collectJsonLdNames($decoded, $names);
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  array<mixed>  $node
     * @param  list<string>  $names
     */
    private function collectJsonLdNames(array $node, array &$names): void
    {
        $type = $node['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];
        $isPerson = false;
        foreach ($types as $entry) {
            if (is_string($entry) && strcasecmp($entry, 'Person') === 0) {
                $isPerson = true;
                break;
            }
        }
        if ($isPerson) {
            $given = trim((string) ($node['givenName'] ?? ''));
            $family = trim((string) ($node['familyName'] ?? ''));
            if ($given !== '' || $family !== '') {
                $names[] = trim($given.' '.$family);
            }
            $name = trim((string) ($node['name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
        foreach (['founder', 'employee', 'contactPoint', 'member', 'alumni'] as $key) {
            if (! isset($node[$key])) {
                continue;
            }
            $children = $node[$key];
            if (isset($children[0]) || $children === []) {
                foreach (is_array($children) ? $children : [] as $child) {
                    if (is_array($child)) {
                        $this->collectJsonLdNames($child, $names);
                    } elseif (is_string($child) && $child !== '') {
                        $names[] = $child;
                    }
                }
            } elseif (is_array($children)) {
                $this->collectJsonLdNames($children, $names);
            } elseif (is_string($children) && $children !== '') {
                $names[] = $children;
            }
        }
        if (isset($node['@graph']) && is_array($node['@graph'])) {
            foreach ($node['@graph'] as $child) {
                if (is_array($child)) {
                    $this->collectJsonLdNames($child, $names);
                }
            }
        }
        foreach ($node as $value) {
            if (is_array($value) && (isset($value['@type']) || isset($value['@graph']))) {
                $this->collectJsonLdNames($value, $names);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function mailtoNames(string $html): array
    {
        $names = [];
        if (preg_match_all('/<a[^>]+href=["\']mailto:([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $text = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($text !== '') {
                    $names[] = $text;
                }
                $href = urldecode($match[1]);
                $email = strtolower(trim(strstr($href, '?', true) ?: $href));
                $fromEmail = $this->parser->fromEmail($email);
                if ($fromEmail && filled($fromEmail['last_name'] ?? null)) {
                    $names[] = trim(($fromEmail['first_name'] ?? '').' '.($fromEmail['middle_name'] ?? '').' '.($fromEmail['last_name'] ?? ''));
                }
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function labeledNames(string $html): array
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if (! preg_match_all('/(?:eigenaar|directeur|contactpersoon|zaakvoerder|dga|oprichter|founder|owner|ceo)\s*[:\-–]\s*([A-ZÀ-ÖØ-Þ][\p{L}\'\-]+(?:\s+[\p{L}\'\-]+){0,4})/u', $text, $matches)) {
            return [];
        }

        return array_map('trim', $matches[1]);
    }
}
