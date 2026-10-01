<?php

namespace App\Services\Payout;

use App\Models\Company;
use App\Models\PayoutIdentity;
use App\Models\RidePlatformSettlement;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\Schema;

/**
 * Betaalt netto-bedragen uit vanaf het platform naar payout identities.
 * Automatisch via cron; handmatig via admin-retry.
 */
class PlatformRidePayoutService
{
    public function __construct(
        protected PlatformSettlementTransferClient $transfers,
        protected PayoutIdentityService $payoutIdentities,
        protected ModuleDatabaseService $moduleDb,
    ) {}

    /**
     * @return array{processed: int, paid: int, failed: int, manual: int}
     */
    public function processPending(int $limit = 100): array
    {
        $stats = ['processed' => 0, 'paid' => 0, 'failed' => 0, 'manual' => 0];
        if (! Schema::hasTable('ride_platform_settlements')) {
            return $stats;
        }

        RidePlatformSettlement::query()
            ->whereIn('status', [
                RidePlatformSettlement::STATUS_PENDING_PAYOUT,
                RidePlatformSettlement::STATUS_FAILED,
            ])
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (RidePlatformSettlement $settlement) use (&$stats) {
                $stats['processed']++;
                $result = $this->processOne($settlement);
                if ($result === RidePlatformSettlement::STATUS_PAID_OUT) {
                    $stats['paid']++;
                } elseif ($result === RidePlatformSettlement::STATUS_MANUAL_REQUIRED) {
                    $stats['manual']++;
                } else {
                    $stats['failed']++;
                }
            });

        return $stats;
    }

    public function processOne(RidePlatformSettlement $settlement, bool $forceManualSucceed = false): string
    {
        if ($settlement->status === RidePlatformSettlement::STATUS_PAID_OUT) {
            return RidePlatformSettlement::STATUS_PAID_OUT;
        }

        $settlement->payout_attempts = (int) $settlement->payout_attempts + 1;
        $lines = [];
        $errors = [];

        $recipients = $this->recipientPlan($settlement);
        foreach ($recipients as $row) {
            if ($row['amount'] <= 0) {
                continue;
            }

            $company = Company::query()->find($row['company_id']);
            if (! $company) {
                $errors[] = "Bedrijf #{$row['company_id']} ontbreekt.";
                continue;
            }

            $identity = $this->payoutIdentities->forCompany($company)
                ?? $this->payoutIdentities->bootstrapCompanyIdentity($company, null, null);

            if ($forceManualSucceed) {
                $transfer = [
                    'ok' => true,
                    'transfer_id' => 'manual_'.substr(sha1((string) $settlement->id.'|'.$row['company_id']), 0, 12),
                    'stub' => true,
                    'manual' => true,
                ];
            } else {
                $transfer = $this->transfers->transfer(
                    $identity,
                    (float) $row['amount'],
                    (string) $settlement->currency,
                    'ride-'.$settlement->ride_request_id.'-'.$row['role'],
                );
            }

            $lines[] = [
                'role' => $row['role'],
                'company_id' => $row['company_id'],
                'amount' => $row['amount'],
                'ok' => (bool) ($transfer['ok'] ?? false),
                'transfer_id' => $transfer['transfer_id'] ?? null,
                'error' => $transfer['error'] ?? null,
                'payout_identity_id' => $identity->id,
            ];

            if (! ($transfer['ok'] ?? false)) {
                $errors[] = ($transfer['error'] ?? 'Transfer mislukt')." ({$row['role']})";
            }
        }

        $settlement->payout_lines = $lines;

        if ($errors === []) {
            $settlement->status = RidePlatformSettlement::STATUS_PAID_OUT;
            $settlement->last_error = null;
            $settlement->paid_out_at = now();
            $settlement->save();
            $this->markRideSettled($settlement);

            return RidePlatformSettlement::STATUS_PAID_OUT;
        }

        $settlement->last_error = implode(' | ', $errors);
        $settlement->status = $settlement->payout_attempts >= 3
            ? RidePlatformSettlement::STATUS_MANUAL_REQUIRED
            : RidePlatformSettlement::STATUS_FAILED;
        $settlement->save();

        return $settlement->status;
    }

    /**
     * @return list<array{role: string, company_id: int, amount: float}>
     */
    public function recipientPlan(RidePlatformSettlement $settlement): array
    {
        if ($settlement->model === RidePlatformSettlement::MODEL_MARKETPLACE) {
            $companyId = (int) ($settlement->owner_company_id ?: $settlement->fulfiller_company_id);

            return $companyId > 0
                ? [['role' => 'claimer', 'company_id' => $companyId, 'amount' => (float) $settlement->net_amount]]
                : [];
        }

        $plan = [];
        if ((float) $settlement->owner_share_amount > 0 && (int) $settlement->owner_company_id > 0) {
            $plan[] = [
                'role' => 'owner',
                'company_id' => (int) $settlement->owner_company_id,
                'amount' => (float) $settlement->owner_share_amount,
            ];
        }
        if ((float) $settlement->fulfiller_share_amount > 0 && (int) $settlement->fulfiller_company_id > 0) {
            $plan[] = [
                'role' => 'fulfiller',
                'company_id' => (int) $settlement->fulfiller_company_id,
                'amount' => (float) $settlement->fulfiller_share_amount,
            ];
        }

        return $plan;
    }

    private function markRideSettled(RidePlatformSettlement $settlement): void
    {
        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
            $ride = RideRequest::on($conn)->find($settlement->ride_request_id);
            if ($ride && $ride->settlement_status !== RideRequest::SETTLEMENT_SETTLED) {
                $ride->forceFill([
                    'settlement_status' => RideRequest::SETTLEMENT_SETTLED,
                    'settlement_eligible_at' => $ride->settlement_eligible_at ?? now(),
                ])->save();
            }
        } catch (\Throwable) {
            // Module DB optional in some unit contexts.
        }
    }
}
