<?php

namespace App\Services\Payout;

use App\Models\RidePlatformSettlement;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDriverInboxPushService;
use Illuminate\Support\Facades\DB;

/**
 * Betaalt het nettobedrag (bruto minus platformfee) direct uit aan de chauffeur
 * van de rit. Eigen-website-ritten: 0% fee. Geen stub-succes.
 */
class DriverRidePayoutService
{
    public function __construct(
        protected MollieConnectBalanceTransferClient $transfers,
        protected PayoutIdentityService $payoutIdentities,
        protected PlatformRideSettlementService $settlements,
    ) {}

    public function payoutCompletedRide(RideRequest $ride): ?RidePlatformSettlement
    {
        if (! $this->settlements->isEnabled()) {
            return null;
        }

        if ($ride->status !== RideRequest::STATUS_COMPLETED || ! $ride->isSettlementPayable()) {
            return null;
        }

        $settlement = $this->ensureLedger($ride);
        if (! $settlement) {
            return null;
        }

        return $this->processExisting($settlement, $ride);
    }

    public function processExisting(RidePlatformSettlement $settlement, ?RideRequest $ride = null): RidePlatformSettlement
    {
        $justPaidDriverId = 0;

        $result = DB::transaction(function () use ($settlement, $ride, &$justPaidDriverId) {
            $locked = RidePlatformSettlement::query()->whereKey($settlement->id)->lockForUpdate()->first();
            if (! $locked) {
                return $settlement;
            }

            if ($locked->status === RidePlatformSettlement::STATUS_PAID_OUT) {
                return $locked;
            }

            $ride ??= $this->findRide($locked);
            $driverId = (int) ($ride->driver_id ?? data_get($locked->meta, 'driver_user_id') ?? 0);
            $this->rememberDriver($locked, $driverId);

            if (! $ride || $ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID) {
                return $this->fail($locked, 'Betaling is niet afgerond.');
            }

            if ($driverId <= 0) {
                return $this->fail($locked, 'Geen chauffeur op de rit.');
            }

            $net = round((float) $locked->net_amount, 2);
            if ($net <= 0) {
                return $this->fail($locked, 'Geen uit te betalen bedrag.');
            }

            $identity = $this->payoutIdentities->forIndependentDriver($driverId);
            if (! $identity || ! $identity->isEnabled()) {
                return $this->fail($locked, 'Chauffeur heeft geen actieve Mollie-uitbetalingsrekening.');
            }

            $transfer = $this->transfers->transfer(
                $identity,
                $net,
                (string) ($locked->currency ?: 'EUR'),
                'ride-'.$locked->ride_request_id.'-driver',
            );

            if (! empty($transfer['stub']) || ! ($transfer['ok'] ?? false) || ! filled($transfer['transfer_id'] ?? null)) {
                return $this->fail($locked, (string) ($transfer['error'] ?? 'Mollie-overboeking mislukt.'));
            }

            $message = $this->successMessage((int) $locked->ride_request_id, $net);
            $meta = is_array($locked->meta) ? $locked->meta : [];
            $meta['payout_to'] = 'driver';
            $meta['driver_user_id'] = $driverId;
            $meta['driver_message'] = $message;
            $meta['driver_message_at'] = now()->toIso8601String();
            $meta['transfer_id'] = $transfer['transfer_id'];

            $locked->forceFill([
                'payout_attempts' => (int) $locked->payout_attempts + 1,
                'status' => RidePlatformSettlement::STATUS_PAID_OUT,
                'last_error' => null,
                'paid_out_at' => now(),
                'payout_lines' => [[
                    'role' => 'driver',
                    'driver_user_id' => $driverId,
                    'payout_identity_id' => $identity->id,
                    'amount' => $net,
                    'ok' => true,
                    'transfer_id' => $transfer['transfer_id'],
                ]],
                'meta' => $meta,
            ])->save();

            $this->markRideSettled($ride);
            $justPaidDriverId = $driverId;

            return $locked->fresh() ?? $locked;
        });

        if ($justPaidDriverId > 0) {
            try {
                app(TaxiDriverInboxPushService::class)->notifyDriver($justPaidDriverId, (int) $result->ride_request_id);
            } catch (\Throwable) {
                // Inbox-signaal is best-effort; het bericht staat op de settlement.
            }
        }

        return $result;
    }

    /**
     * @return list<array{ride_request_id: int, title: string, message: string, amount: float}>
     */
    public function noticesForDriver(int $driverId): array
    {
        if ($driverId <= 0 || ! $this->settlements->isEnabled()) {
            return [];
        }

        return RidePlatformSettlement::query()
            ->where('status', RidePlatformSettlement::STATUS_PAID_OUT)
            ->where('paid_out_at', '>=', now()->subDays(14))
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->filter(function (RidePlatformSettlement $row) use ($driverId) {
                return (int) data_get($row->meta, 'driver_user_id') === $driverId
                    && filled(data_get($row->meta, 'driver_message'));
            })
            ->map(fn (RidePlatformSettlement $row) => [
                'ride_request_id' => (int) $row->ride_request_id,
                'title' => 'Bedrag overgemaakt',
                'message' => (string) data_get($row->meta, 'driver_message'),
                'amount' => round((float) $row->net_amount, 2),
            ])
            ->values()
            ->all();
    }

    private function ensureLedger(RideRequest $ride): ?RidePlatformSettlement
    {
        $existing = RidePlatformSettlement::query()
            ->where('ride_request_id', $ride->id)
            ->first();

        if (! $existing) {
            $existing = $this->settlements->ensureFromEligibleRide($ride);
        }

        if (! $existing && $this->settlements->resolveModel($ride) === null) {
            $existing = $this->createOwnSiteLedger($ride);
        }

        if (! $existing) {
            return null;
        }

        $this->rememberDriver($existing, (int) ($ride->driver_id ?? 0));

        return $existing->fresh() ?? $existing;
    }

    private function createOwnSiteLedger(RideRequest $ride): ?RidePlatformSettlement
    {
        $gross = $this->settlements->grossAmount($ride);
        $driverId = (int) ($ride->driver_id ?? 0);

        try {
            return RidePlatformSettlement::query()->create([
                'ride_request_id' => (int) $ride->id,
                'model' => RidePlatformSettlement::MODEL_OWN_SITE,
                'owner_company_id' => (int) ($ride->company_id ?? 0) ?: null,
                'fulfiller_company_id' => (int) ($ride->executingCompanyId() ?? 0) ?: null,
                'gross_amount' => $gross,
                'nexa_fee_percent' => 0,
                'nexa_fee_amount' => 0,
                'net_amount' => $gross,
                'owner_share_percent' => 100,
                'owner_share_amount' => $gross,
                'fulfiller_share_percent' => 0,
                'fulfiller_share_amount' => 0,
                'currency' => 'EUR',
                'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
                'payout_attempts' => 0,
                'payout_lines' => [],
                'meta' => [
                    'payout_to' => 'driver',
                    'driver_user_id' => $driverId > 0 ? $driverId : null,
                    'collect_model' => 'platform_collect',
                    'created_from' => 'ride_completed',
                ],
            ]);
        } catch (\Throwable) {
            return RidePlatformSettlement::query()
                ->where('ride_request_id', $ride->id)
                ->first();
        }
    }

    private function rememberDriver(RidePlatformSettlement $settlement, int $driverId): void
    {
        $meta = is_array($settlement->meta) ? $settlement->meta : [];
        $meta['payout_to'] = 'driver';
        if ($driverId > 0) {
            $meta['driver_user_id'] = $driverId;
        }
        if ($settlement->meta !== $meta) {
            $settlement->meta = $meta;
            $settlement->save();
        }
    }

    private function fail(RidePlatformSettlement $settlement, string $error): RidePlatformSettlement
    {
        $attempts = (int) $settlement->payout_attempts + 1;
        $settlement->forceFill([
            'payout_attempts' => $attempts,
            'last_error' => $error,
            'status' => $attempts >= 3
                ? RidePlatformSettlement::STATUS_MANUAL_REQUIRED
                : RidePlatformSettlement::STATUS_FAILED,
        ])->save();

        return $settlement->fresh() ?? $settlement;
    }

    private function successMessage(int $rideId, float $amount): string
    {
        $formatted = number_format($amount, 2, ',', '.');

        return "Het bedrag van €{$formatted} voor rit #{$rideId} is overgemaakt.";
    }

    private function findRide(RidePlatformSettlement $settlement): ?RideRequest
    {
        try {
            $conn = app(\App\Services\ModuleDatabaseService::class)->getModuleConnectionName('taxi');
            $ride = RideRequest::on($conn)->find($settlement->ride_request_id);
            if ($ride) {
                return $ride;
            }
        } catch (\Throwable) {
            // Module connection optional in unit tests.
        }

        return RideRequest::query()->find($settlement->ride_request_id);
    }

    private function markRideSettled(?RideRequest $ride): void
    {
        if (! $ride) {
            return;
        }

        try {
            $fresh = RideRequest::on($ride->getConnectionName())->find($ride->id);
            if ($fresh && $fresh->settlement_status !== RideRequest::SETTLEMENT_SETTLED) {
                $fresh->forceFill([
                    'settlement_status' => RideRequest::SETTLEMENT_SETTLED,
                    'settlement_eligible_at' => $fresh->settlement_eligible_at ?? now(),
                ])->save();
            }
        } catch (\Throwable) {
            // Ride row optional when the ledger is the source of truth.
        }
    }
}
