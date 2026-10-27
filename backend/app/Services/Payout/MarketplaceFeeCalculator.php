<?php

namespace App\Services\Payout;

use App\Models\NexaSuiteMarketplaceSetting;
use App\Support\NexaMarketplaceFeeCopy;
use Throwable;

/**
 * Enige plek waar de NEXA-fee per rit wordt berekend:
 * fee = max(percentage-fee, minimum uit Mollie-kosten), nooit meer dan het ritbedrag.
 * Bedragen excl. btw (btw op de fee blijft zoals voorheen bij de factuur).
 */
class MarketplaceFeeCalculator
{
    /**
     * @return array{
     *   gross: float,
     *   fee: float,
     *   percent: int,
     *   percent_fee: float,
     *   minimum_fee: float,
     *   mollie_cost: float,
     *   minimum_applied: bool,
     *   capped: bool
     * }
     */
    public function calculate(float $gross, ?string $method = null, ?int $percent = null): array
    {
        $gross = round(max(0.0, $gross), 2);
        $configured = max(0, min(100, $percent ?? NexaMarketplaceFeeCopy::percent()));
        $percentFee = round($gross * ($configured / 100), 2);

        $cfg = $this->config();
        $mollieCost = 0.0;
        $minimum = 0.0;
        if ($gross > 0) {
            $m = $this->methodRates($cfg['methods'], $method);
            $mollieCost = $m['fixed'] + $gross * $m['percent'] / 100 + $cfg['payout_fixed'];
            if ($cfg['enabled']) {
                $minimum = $this->ceilCents($mollieCost + $cfg['margin_fixed'] + $gross * $cfg['margin_percent'] / 100);
            }
        }

        $fee = max($percentFee, $minimum);
        $capped = $fee > $gross;
        $fee = round(min($fee, $gross), 2);
        $minimumApplied = $fee > $percentFee;

        return [
            'gross' => $gross,
            'fee' => $fee,
            // Bij toegepast minimum: effectief percentage, zodat "bedrag (x%)" klopt.
            'percent' => $minimumApplied && $gross > 0 ? (int) round($fee / $gross * 100) : $configured,
            'percent_fee' => $percentFee,
            'minimum_fee' => $minimum,
            'mollie_cost' => round($mollieCost, 2),
            'minimum_applied' => $minimumApplied,
            'capped' => $capped,
        ];
    }

    /**
     * @return array{enabled: bool, methods: array<string, array{fixed: float, percent: float}>, payout_fixed: float, margin_fixed: float, margin_percent: float}
     */
    public function config(): array
    {
        $cfg = (array) config('nexa_suite_marketplace.fee_minimum', []);
        try {
            $stored = NexaSuiteMarketplaceSetting::current()->fee_minimum_settings;
            if (is_array($stored)) {
                $cfg = array_replace_recursive($cfg, $stored);
            }
        } catch (Throwable) {
            // Zonder instellingenrij/kolom: config-defaults.
        }

        $methods = [];
        foreach ((array) ($cfg['methods'] ?? []) as $key => $rates) {
            $methods[(string) $key] = [
                'fixed' => max(0.0, (float) ($rates['fixed'] ?? 0)),
                'percent' => max(0.0, (float) ($rates['percent'] ?? 0)),
            ];
        }
        $methods['default'] ??= ['fixed' => 0.25, 'percent' => 1.8];

        return [
            'enabled' => (bool) ($cfg['enabled'] ?? true),
            'methods' => $methods,
            'payout_fixed' => max(0.0, (float) ($cfg['payout_fixed'] ?? 0)),
            'margin_fixed' => max(0.0, (float) ($cfg['margin_fixed'] ?? 0)),
            'margin_percent' => max(0.0, (float) ($cfg['margin_percent'] ?? 0)),
        ];
    }

    /**
     * @param  array<string, array{fixed: float, percent: float}>  $methods
     * @return array{fixed: float, percent: float}
     */
    private function methodRates(array $methods, ?string $method): array
    {
        $key = strtolower(trim((string) $method));

        return $methods[$key] ?? $methods['default'];
    }

    private function ceilCents(float $amount): float
    {
        return ceil(round($amount * 100, 6)) / 100;
    }
}
