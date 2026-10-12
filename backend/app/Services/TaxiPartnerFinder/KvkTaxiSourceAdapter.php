<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\TaxiProspectSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fase 2: gerichte KvK-zoekopdrachten (geen landelijke SBI-dump).
 * Alleen actief met API-key; zoekt op naam/plaats binnen ondersteunde endpoints.
 */
class KvkTaxiSourceAdapter
{
    public function isEnabled(): bool
    {
        return (bool) config('taxi-partner-finder.sources.kvk.enabled', false)
            && filled(config('taxi-partner-finder.sources.kvk.api_key'));
    }

    /**
     * @return list<TaxiProspectCandidate>
     */
    public function searchByName(string $name, ?string $city = null): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('KvK-bron is uitgeschakeld of mist API-key.');
        }

        $base = rtrim((string) config('taxi-partner-finder.sources.kvk.base_url'), '/');
        $key = (string) config('taxi-partner-finder.sources.kvk.api_key');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'apikey' => $key,
                    'Accept' => 'application/json',
                ])
                ->get($base.'/zoeken', array_filter([
                    'naam' => $name,
                    'plaats' => $city,
                    'type' => 'hoofdvestiging',
                ]));
        } catch (\Throwable $e) {
            Log::warning('KvK search failed', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('KvK search HTTP error', ['status' => $response->status()]);

            return [];
        }

        $results = $response->json('resultaten') ?? $response->json('data') ?? [];
        if (! is_array($results)) {
            return [];
        }

        $candidates = [];
        foreach ($results as $row) {
            if (! is_array($row)) {
                continue;
            }
            $company = trim((string) ($row['naam'] ?? $row['handelsnaam'] ?? ''));
            $kvk = preg_replace('/\D/', '', (string) ($row['kvkNummer'] ?? $row['kvk_number'] ?? '')) ?: null;
            if ($company === '') {
                continue;
            }
            $candidates[] = new TaxiProspectCandidate(
                companyName: $company,
                sourceType: TaxiProspectSource::TYPE_KVK,
                externalReference: $kvk ? 'kvk-'.$kvk : null,
                kvkNumber: $kvk,
                city: $row['plaats'] ?? $city,
                postalCode: $row['postcode'] ?? null,
                street: $row['straatnaam'] ?? null,
                houseNumber: isset($row['huisnummer']) ? (string) $row['huisnummer'] : null,
                confidenceScore: 70,
                rawMetadata: [
                    'consulted_at' => now()->toIso8601String(),
                    'source_label' => 'KvK Handelsregister',
                    'payload' => $row,
                ],
            );
        }

        return $candidates;
    }
}
