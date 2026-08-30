<?php

namespace App\Services\CompanySearch;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HunterService
{
    private const DOMAIN_SEARCH_URL = 'https://api.hunter.io/v2/domain-search';

    /** @var array<string, array{email: ?string, first_name: ?string, middle_name: ?string, last_name: ?string}> */
    private array $cache = [];

    public function __construct(
        protected EmailExtractorService $emails,
        protected DomainService $domains,
        protected ContactNameParser $names,
    ) {}

    public function isEnabled(): bool
    {
        if (! (bool) config('company-enrichment.hunter.enabled', false)) {
            return false;
        }

        return $this->apiKey() !== '';
    }

    public function findEmail(string $website): ?string
    {
        return $this->lookup($website)['email'] ?? null;
    }

    /**
     * @return array{email: ?string, first_name: ?string, middle_name: ?string, last_name: ?string}
     */
    public function lookup(string $website): array
    {
        $empty = ['email' => null, 'first_name' => null, 'middle_name' => null, 'last_name' => null];
        if (! $this->isEnabled()) {
            return $empty;
        }
        $domain = $this->domains->host($website);
        if ($domain === '') {
            return $empty;
        }
        if (isset($this->cache[$domain])) {
            return $this->cache[$domain];
        }

        try {
            $response = Http::timeout(12)
                ->get(self::DOMAIN_SEARCH_URL, [
                    'domain' => $domain,
                    'api_key' => $this->apiKey(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Hunter lookup failed', ['domain' => $domain, 'error' => $e->getMessage()]);

            return $this->cache[$domain] = $empty;
        }

        if (! $response->successful()) {
            Log::warning('Hunter lookup failed', ['domain' => $domain, 'status' => $response->status()]);

            return $this->cache[$domain] = $empty;
        }

        $emails = $response->json('data.emails');
        if (! is_array($emails)) {
            return $this->cache[$domain] = $empty;
        }

        $generic = [];
        $personal = [];
        $bestPerson = null;
        $bestScore = -1;
        foreach ($emails as $row) {
            if (! is_array($row)) {
                continue;
            }
            $value = strtolower(trim((string) ($row['value'] ?? '')));
            if ($value === '' || $this->emails->isJunk($value)) {
                continue;
            }
            if (($row['type'] ?? '') === 'generic') {
                $generic[] = $value;
            } else {
                $personal[] = $value;
                $score = $this->personScore($row);
                if ($score > $bestScore) {
                    $parsed = $this->names->fromParts(
                        (string) ($row['first_name'] ?? ''),
                        (string) ($row['last_name'] ?? '')
                    );
                    if ($parsed === null) {
                        $parsed = $this->names->fromEmail($value);
                    }
                    if ($parsed !== null) {
                        $bestPerson = $parsed;
                        $bestScore = $score;
                    }
                }
            }
        }

        $chosen = $generic[0] ?? $personal[0] ?? null;
        if ($chosen !== null) {
            Log::info('Company email discovered', [
                'domain' => $domain,
                'source' => 'hunter',
            ]);
        }

        return $this->cache[$domain] = [
            'email' => $chosen,
            'first_name' => $bestPerson['first_name'] ?? null,
            'middle_name' => $bestPerson['middle_name'] ?? null,
            'last_name' => $bestPerson['last_name'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function personScore(array $row): int
    {
        $score = 10;
        $position = mb_strtolower(trim((string) ($row['position'] ?? '')), 'UTF-8');
        foreach (['eigenaar', 'owner', 'directeur', 'director', 'ceo', 'founder', 'dga', 'zaakvoerder', 'managing'] as $needle) {
            if ($position !== '' && str_contains($position, $needle)) {
                $score += 20;
                break;
            }
        }
        if (($row['confidence'] ?? null) !== null) {
            $score += (int) $row['confidence'];
        }

        return $score;
    }

    private function apiKey(): string
    {
        return trim((string) (config('company-enrichment.hunter.api_key') ?: config('services.hunter.api_key', '')));
    }
}
