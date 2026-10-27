<?php

namespace App\Services\Payout;

use App\Services\NexaSuiteMollieService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Haalt de tarieven van het eigen Mollie-account op: GET /v2/methods/all?include=pricing
 * (https://docs.mollie.com/reference/list-all-methods). Alleen `/methods/all` documenteert
 * include=pricing; `/methods` en `/methods/{id}` kennen alleen include=issuers.
 * Gebruikt dezelfde sleutel als de ritbetalingen (NexaSuiteMollieService). Schrijft niets weg.
 * Uitbetalingskosten / Connect-overboekingen zijn niet via de API op te halen.
 */
class MollieMethodPricingFetcher
{
    /** Methodes die de calculator kent. */
    private const METHODS = ['ideal', 'creditcard', 'applepay', 'bancontact'];

    public function __construct(private NexaSuiteMollieService $mollie) {}

    /**
     * @return array{methods: array<string, array{fixed: float, percent: float}>, skipped: list<string>}
     *
     * @throws RuntimeException met een Nederlandse melding
     */
    public function fetch(): array
    {
        try {
            $key = $this->mollie->apiKey();
        } catch (RuntimeException) {
            throw new RuntimeException(__('Er is geen Mollie API-sleutel ingesteld. Stel die eerst in bij Mollie op deze pagina.'));
        }

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(10)
                ->get('https://api.mollie.com/v2/methods/all', ['include' => 'pricing']);
        } catch (ConnectionException) {
            throw new RuntimeException(__('Mollie is niet bereikbaar. Probeer het later opnieuw; er is niets gewijzigd.'));
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException(__('Mollie weigert de aanvraag (geen toegang met deze API-sleutel). Er is niets gewijzigd.'));
        }
        if ($response->failed()) {
            throw new RuntimeException(__('Mollie gaf een fout terug (HTTP :status). Er is niets gewijzigd.', ['status' => $response->status()]));
        }

        $byId = [];
        foreach ((array) $response->json('_embedded.methods', []) as $method) {
            if (is_array($method) && isset($method['id'])) {
                $byId[(string) $method['id']] = (array) ($method['pricing'] ?? []);
            }
        }

        $methods = [];
        $skipped = [];
        foreach (self::METHODS as $id) {
            $rates = $this->pick($id, $byId[$id] ?? []);
            if ($rates === null) {
                $skipped[] = $id;

                continue;
            }
            $methods[$id] = $rates;
        }

        // Apple Pay wordt per onderliggende kaart verrekend: zonder eigen tarief = kaarttarief.
        if (! isset($methods['applepay']) && isset($methods['creditcard'])) {
            $methods['applepay'] = $methods['creditcard'];
            $skipped = array_values(array_diff($skipped, ['applepay']));
        }
        // 'default' (onbekende methode) = voorzichtig het kaarttarief.
        if (isset($methods['creditcard'])) {
            $methods['default'] = $methods['creditcard'];
        }

        if ($methods === []) {
            throw new RuntimeException(__('Mollie gaf geen prijzen terug voor dit account. Er is niets gewijzigd.'));
        }

        return ['methods' => $methods, 'skipped' => $skipped];
    }

    /**
     * Deterministische keuze van één consumententarief (EEA/binnenland), commercieel en niet-EEA genegeerd:
     * - creditcard: feeRegion `intra-eu`, anders `domestic`, anders omschrijving die op consumentenkaarten
     *   in Europa lijkt; commercial/corporate/non-EEA/American Express/Maestro/Carte Bancaire/overig nooit.
     * - overige methodes: één entry; bij meerdere de entry voor Nederland/binnenland; anders niets (overslaan).
     *
     * @param  list<array<string, mixed>>  $pricing
     * @return array{fixed: float, percent: float}|null
     */
    private function pick(string $id, array $pricing): ?array
    {
        $pricing = array_values(array_filter($pricing, fn ($p) => is_array($p) && isset($p['fixed']['value'], $p['variable'])));
        if ($pricing === []) {
            return null;
        }

        $chosen = null;
        if ($id === 'creditcard') {
            foreach (['intra-eu', 'domestic'] as $region) {
                foreach ($pricing as $p) {
                    if (($p['feeRegion'] ?? null) === $region) {
                        $chosen = $p;
                        break 2;
                    }
                }
            }
            if ($chosen === null) {
                foreach ($pricing as $p) {
                    $d = strtolower((string) ($p['description'] ?? ''));
                    if (($p['feeRegion'] ?? null) === null
                        && preg_match('/consumer|europe|eea/', $d)
                        && ! preg_match('/commercial|corporate|non-|american|amex|maestro|bancaire/', $d)) {
                        $chosen = $p;
                        break;
                    }
                }
            }
        } elseif (count($pricing) === 1) {
            $chosen = $pricing[0];
        } else {
            foreach ($pricing as $p) {
                if (preg_match('/netherlands|nederland|domestic/i', (string) ($p['description'] ?? ''))) {
                    $chosen = $p;
                    break;
                }
            }
        }

        if ($chosen === null || ! is_numeric($chosen['fixed']['value']) || ! is_numeric($chosen['variable'])) {
            return null;
        }

        return ['fixed' => round((float) $chosen['fixed']['value'], 2), 'percent' => round((float) $chosen['variable'], 2)];
    }
}
