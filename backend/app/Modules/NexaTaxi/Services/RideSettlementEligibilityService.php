<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Support\Carbon;

/**
 * Settlement gate: driver "complete" is only a completion claim.
 * Payable settlement requires an eligible outcome from this service.
 */
class RideSettlementEligibilityService
{
    public const MIN_ELAPSED_SECONDS = 60;

    public const HOLD_HOURS_DEFAULT = 24;

    public const HOLD_HOURS_HIGH_RISK = 72;

    public const DISTANCE_RATIO_MIN = 0.35;

    public const DISTANCE_RATIO_MAX = 2.75;

    /**
     * Evaluate a fully completed ride and persist settlement fields.
     * Never sets settlement_eligible immediately — hold first unless already past hold.
     */
    public function evaluateAfterCompletion(string $conn, RideRequest $ride): RideRequest
    {
        TaxiDispatchSchema::ensureSettlementColumns($conn);

        $ride = RideRequest::on($conn)->whereKey($ride->id)->first() ?? $ride;
        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            return $ride;
        }

        $flags = $this->collectRiskFlags($ride);
        $highRisk = $this->requiresManualReview($flags);
        $holdHours = $highRisk ? self::HOLD_HOURS_HIGH_RISK : (int) config('taxi-dispatch.settlement_hold_hours', self::HOLD_HOURS_DEFAULT);
        $holdUntil = now()->addHours(max(1, $holdHours));

        // Strong rejection: payment not settled when payment was required.
        if (in_array('payment_incomplete', $flags, true)) {
            $ride->forceFill([
                'settlement_status' => RideRequest::SETTLEMENT_REJECTED,
                'settlement_risk_flags' => $flags,
                'settlement_evaluated_at' => now(),
                'settlement_hold_until' => null,
                'settlement_eligible_at' => null,
            ])->save();

            return $ride->fresh();
        }

        if ($highRisk) {
            $ride->forceFill([
                'settlement_status' => RideRequest::SETTLEMENT_REVIEW,
                'settlement_risk_flags' => $flags,
                'settlement_evaluated_at' => now(),
                'settlement_hold_until' => $holdUntil,
                'settlement_eligible_at' => null,
            ])->save();

            return $ride->fresh();
        }

        $ride->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_HOLD,
            'settlement_risk_flags' => $flags,
            'settlement_evaluated_at' => now(),
            'settlement_hold_until' => $holdUntil,
            'settlement_eligible_at' => null,
        ])->save();

        return $ride->fresh();
    }

    /**
     * Promote held rides past hold_until to settlement_eligible (cron/admin).
     * Review rides are never auto-promoted.
     */
    public function releaseDueHolds(string $conn, ?Carbon $now = null): int
    {
        TaxiDispatchSchema::ensureSettlementColumns($conn);
        $now = $now ?? now();
        $released = 0;

        RideRequest::on($conn)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->where('settlement_status', RideRequest::SETTLEMENT_HOLD)
            ->whereNotNull('settlement_hold_until')
            ->where('settlement_hold_until', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($rides) use (&$released, $now) {
                foreach ($rides as $ride) {
                    $ride->forceFill([
                        'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
                        'settlement_eligible_at' => $now,
                        'settlement_evaluated_at' => $now,
                    ])->save();
                    $this->enqueuePlatformSettlement($ride->fresh());
                    $released++;
                }
            });

        return $released;
    }

    /**
     * Manual admin release from hold/review → eligible.
     */
    public function markEligible(string $conn, RideRequest $ride, array $extraFlags = []): RideRequest
    {
        TaxiDispatchSchema::ensureSettlementColumns($conn);
        $flags = array_values(array_unique(array_merge(
            is_array($ride->settlement_risk_flags) ? $ride->settlement_risk_flags : [],
            $extraFlags,
            ['manual_release']
        )));

        $ride->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
            'settlement_eligible_at' => now(),
            'settlement_evaluated_at' => now(),
            'settlement_hold_until' => null,
            'settlement_risk_flags' => $flags,
        ])->save();

        $fresh = $ride->fresh();
        $this->enqueuePlatformSettlement($fresh);

        return $fresh;
    }

    private function enqueuePlatformSettlement(?RideRequest $ride): void
    {
        if ($ride === null) {
            return;
        }
        try {
            app(\App\Services\Payout\PlatformRideSettlementService::class)->ensureFromEligibleRide($ride);
        } catch (\Throwable) {
            // Ledger is best-effort; cron syncEligibleRides catches stragglers.
        }
    }

    public function isPayable(RideRequest $ride): bool
    {
        return in_array($ride->settlement_status, [
            RideRequest::SETTLEMENT_ELIGIBLE,
            RideRequest::SETTLEMENT_SETTLED,
        ], true);
    }

    /**
     * Customer one-tap confirmation — risk signal only; never sets settlement_eligible.
     */
    public function recordCustomerConfirmation(string $conn, RideRequest $ride): RideRequest
    {
        TaxiDispatchSchema::ensureSettlementColumns($conn);
        $this->assertCustomerSignalAllowed($ride);

        $flags = array_values(array_unique(array_merge(
            is_array($ride->settlement_risk_flags) ? $ride->settlement_risk_flags : [],
            ['customer_confirmed_completion']
        )));

        $ride->forceFill([
            'settlement_risk_flags' => $flags,
            'settlement_evaluated_at' => now(),
        ])->save();

        return $ride->fresh();
    }

    /**
     * Customer "probleem melden" — forces review; never sets settlement_eligible.
     */
    public function recordCustomerProblem(string $conn, RideRequest $ride, ?string $note = null): RideRequest
    {
        TaxiDispatchSchema::ensureSettlementColumns($conn);
        $this->assertCustomerSignalAllowed($ride);

        $flags = array_values(array_unique(array_merge(
            is_array($ride->settlement_risk_flags) ? $ride->settlement_risk_flags : [],
            ['customer_reported_problem']
        )));
        if ($note !== null && trim($note) !== '') {
            $flags[] = 'customer_problem_note';
        }

        $updates = [
            'settlement_risk_flags' => $flags,
            'settlement_evaluated_at' => now(),
        ];

        if (! in_array($ride->settlement_status, [
            RideRequest::SETTLEMENT_SETTLED,
            RideRequest::SETTLEMENT_REJECTED,
        ], true)) {
            $updates['settlement_status'] = RideRequest::SETTLEMENT_REVIEW;
            $updates['settlement_eligible_at'] = null;
            $updates['settlement_hold_until'] = now()->addHours(self::HOLD_HOURS_HIGH_RISK);
        }

        $ride->forceFill($updates)->save();

        return $ride->fresh();
    }

    public function customerCanSignal(RideRequest $ride): bool
    {
        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            return false;
        }

        $completedAt = $ride->trip_completed_at ?? $ride->updated_at;
        if (! $completedAt) {
            return false;
        }

        $windowHours = (int) config('taxi-dispatch.customer_settlement_signal_hours', 48);

        return $completedAt->gt(now()->subHours(max(1, $windowHours)));
    }

    private function assertCustomerSignalAllowed(RideRequest $ride): void
    {
        if (! $this->customerCanSignal($ride)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ride' => ['Deze rit kan nu niet meer bevestigd of gemeld worden.'],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function collectRiskFlags(RideRequest $ride): array
    {
        $flags = [];

        if (! $ride->trip_started_at) {
            $flags[] = 'missing_trip_start';
        } else {
            $end = $ride->trip_completed_at ?? $ride->updated_at ?? now();
            $elapsed = $ride->trip_started_at->diffInSeconds($end);
            if ($elapsed < self::MIN_ELAPSED_SECONDS) {
                $flags[] = 'completed_too_soon';
            }
        }

        $quoted = (int) ($ride->distance_meters ?? 0);
        $actual = (int) ($ride->actual_distance_meters ?? 0);
        if ($quoted > 500 && $actual > 0) {
            $ratio = $actual / $quoted;
            if ($ratio < self::DISTANCE_RATIO_MIN || $ratio > self::DISTANCE_RATIO_MAX) {
                $flags[] = 'distance_implausible';
            }
        } elseif ($quoted > 500 && $actual <= 0) {
            $flags[] = 'missing_gps_track';
        }

        if ($ride->payment_method === 'cash') {
            $flags[] = 'cash_requires_verification';
        }

        if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PENDING) {
            $flags[] = 'payment_incomplete';
        }

        return array_values(array_unique($flags));
    }

    /**
     * @param  list<string>  $flags
     */
    private function requiresManualReview(array $flags): bool
    {
        return count(array_intersect($flags, [
            'completed_too_soon',
            'distance_implausible',
            'cash_requires_verification',
            'payment_incomplete',
            'missing_trip_start',
        ])) > 0;
    }
}
