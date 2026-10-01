<?php

namespace App\Services\Payout;

use App\Models\RidePlatformSettlement;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Support\NexaMarketplaceFeeCopy;
use Illuminate\Support\Facades\Schema;

/**
 * Bouwt platform-settlement ledgers voor marketplace- en network-ritten
 * zodra de settlement-gate eligible is. Tenant-only ritten worden overgeslagen.
 */
class PlatformRideSettlementService
{
    public function isEnabled(): bool
    {
        return (bool) config('nexa_payout.platform_collect_enabled', true)
            && Schema::hasTable('ride_platform_settlements');
    }

    /**
     * Create or return existing ledger for an eligible marketplace/network ride.
     */
    public function ensureFromEligibleRide(RideRequest $ride): ?RidePlatformSettlement
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            return null;
        }

        if (! $ride->isSettlementPayable() && $ride->settlement_status !== RideRequest::SETTLEMENT_SETTLED) {
            return null;
        }

        $model = $this->resolveModel($ride);
        if ($model === null) {
            return null;
        }

        $existing = RidePlatformSettlement::query()
            ->where('ride_request_id', $ride->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $breakdown = $this->computeBreakdown($ride, $model);

        return RidePlatformSettlement::query()->create([
            'ride_request_id' => (int) $ride->id,
            'model' => $model,
            'owner_company_id' => $breakdown['owner_company_id'],
            'fulfiller_company_id' => $breakdown['fulfiller_company_id'],
            'gross_amount' => $breakdown['gross_amount'],
            'nexa_fee_percent' => $breakdown['nexa_fee_percent'],
            'nexa_fee_amount' => $breakdown['nexa_fee_amount'],
            'net_amount' => $breakdown['net_amount'],
            'owner_share_percent' => $breakdown['owner_share_percent'],
            'owner_share_amount' => $breakdown['owner_share_amount'],
            'fulfiller_share_percent' => $breakdown['fulfiller_share_percent'],
            'fulfiller_share_amount' => $breakdown['fulfiller_share_amount'],
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
            'payout_attempts' => 0,
            'payout_lines' => [],
            'meta' => [
                'collect_model' => 'platform_collect',
                'statement_only_fee_invoice' => true,
                'created_from' => 'settlement_eligible',
            ],
        ]);
    }

    /**
     * @return array{
     *   owner_company_id: ?int,
     *   fulfiller_company_id: ?int,
     *   gross_amount: float,
     *   nexa_fee_percent: int,
     *   nexa_fee_amount: float,
     *   net_amount: float,
     *   owner_share_percent: ?int,
     *   owner_share_amount: float,
     *   fulfiller_share_percent: ?int,
     *   fulfiller_share_amount: float
     * }
     */
    public function computeBreakdown(RideRequest $ride, ?string $model = null): array
    {
        $model ??= $this->resolveModel($ride) ?? RidePlatformSettlement::MODEL_MARKETPLACE;
        $gross = $this->grossAmount($ride);
        $feePercent = max(0, min(100, NexaMarketplaceFeeCopy::percent()));
        $feeAmount = round($gross * ($feePercent / 100), 2);
        $net = round(max(0, $gross - $feeAmount), 2);

        $ownerId = (int) ($ride->company_id ?? 0) ?: null;
        $fulfillerId = (int) ($ride->executingCompanyId() ?? 0) ?: null;

        if ($model === RidePlatformSettlement::MODEL_MARKETPLACE) {
            // Claimer = owner = fulfiller: 100% van netto naar die centrale.
            return [
                'owner_company_id' => $ownerId,
                'fulfiller_company_id' => $fulfillerId ?: $ownerId,
                'gross_amount' => $gross,
                'nexa_fee_percent' => $feePercent,
                'nexa_fee_amount' => $feeAmount,
                'net_amount' => $net,
                'owner_share_percent' => 100,
                'owner_share_amount' => $net,
                'fulfiller_share_percent' => 0,
                'fulfiller_share_amount' => 0.0,
            ];
        }

        $ownerPct = max(0, min(100, (int) config('nexa_payout.network_owner_share_of_net_percent', 15)));
        $fulfillerPct = max(0, min(100, (int) config('nexa_payout.network_fulfiller_share_of_net_percent', 85)));
        if ($ownerPct + $fulfillerPct !== 100) {
            $fulfillerPct = max(0, 100 - $ownerPct);
        }

        $ownerShare = round($net * ($ownerPct / 100), 2);
        $fulfillerShare = round($net - $ownerShare, 2);

        return [
            'owner_company_id' => $ownerId,
            'fulfiller_company_id' => $fulfillerId,
            'gross_amount' => $gross,
            'nexa_fee_percent' => $feePercent,
            'nexa_fee_amount' => $feeAmount,
            'net_amount' => $net,
            'owner_share_percent' => $ownerPct,
            'owner_share_amount' => $ownerShare,
            'fulfiller_share_percent' => $fulfillerPct,
            'fulfiller_share_amount' => $fulfillerShare,
        ];
    }

    public function resolveModel(RideRequest $ride): ?string
    {
        if ($ride->isNetworkFulfilled()) {
            return RidePlatformSettlement::MODEL_NETWORK;
        }
        if ($ride->isNexaSuiteBooking()) {
            return RidePlatformSettlement::MODEL_MARKETPLACE;
        }

        return null;
    }

    public function grossAmount(RideRequest $ride): float
    {
        $final = $ride->final_price;
        if ($final !== null && (float) $final > 0) {
            return round((float) $final, 2);
        }
        $quoted = $ride->quoted_price;
        if ($quoted !== null && (float) $quoted > 0) {
            return round((float) $quoted, 2);
        }

        return 0.0;
    }

    /**
     * Scan eligible rides without a ledger and create settlements.
     *
     * @return array{created: int, skipped: int}
     */
    public function syncEligibleRides(string $conn, int $limit = 200): array
    {
        if (! $this->isEnabled()) {
            return ['created' => 0, 'skipped' => 0];
        }

        $created = 0;
        $skipped = 0;

        RideRequest::on($conn)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->whereIn('settlement_status', [
                RideRequest::SETTLEMENT_ELIGIBLE,
                RideRequest::SETTLEMENT_SETTLED,
            ])
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (RideRequest $ride) use (&$created, &$skipped) {
                if ($this->resolveModel($ride) === null) {
                    $skipped++;

                    return;
                }
                $before = RidePlatformSettlement::query()->where('ride_request_id', $ride->id)->exists();
                $this->ensureFromEligibleRide($ride);
                if (! $before && RidePlatformSettlement::query()->where('ride_request_id', $ride->id)->exists()) {
                    $created++;
                } else {
                    $skipped++;
                }
            });

        return compact('created', 'skipped');
    }
}
