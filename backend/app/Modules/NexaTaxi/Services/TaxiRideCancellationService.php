<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\WhatsAppBookingMessageComposer;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Annuleren van niet-geaccepteerde boekingen (klant of automatisch) + Mollie-terugbetaling.
 */
class TaxiRideCancellationService
{
    public const REASON_CUSTOMER = 'customer';

    public const REASON_AUTO_UNACCEPTED = 'auto_unaccepted';

    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected TaxiRidePaymentService $payments,
        protected TaxiCustomerRideStatusNotificationService $statusNotifications,
        protected TaxiDriverInboxPushService $driverPush,
    ) {}

    public function isCancellableByCustomer(RideRequest $ride): bool
    {
        return $this->isUnacceptedOpenBooking($ride);
    }

    public function isDueForAutoCancel(RideRequest $ride, ?CarbonInterface $now = null): bool
    {
        if (! $this->isUnacceptedOpenBooking($ride)) {
            return false;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        $cancelAt = $this->dispatchSettings->unacceptedAutoCancelAt(
            $ride,
            $companyId > 0 ? $companyId : null
        );
        if (! $cancelAt) {
            return false;
        }

        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        return $cancelAt->lte($base);
    }

    public function customerCancelUrl(RideRequest $ride): ?string
    {
        if (! $this->isCancellableByCustomer($ride)) {
            return null;
        }

        $expiresAt = $this->signedCancelUrlExpiresAt($ride);

        return URL::temporarySignedRoute(
            'nexataxi.booking.cancel.show',
            $expiresAt,
            ['ride' => (int) $ride->id]
        );
    }

    /**
     * @return array{ride: RideRequest, refunded: bool, refund_error: ?string}
     */
    public function cancelByCustomer(string $conn, RideRequest $ride): array
    {
        if (! $this->isCancellableByCustomer($ride)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit kan niet meer worden geannuleerd. Neem contact op met de taxi.'],
            ]);
        }

        return $this->cancelUnacceptedRide($conn, $ride, self::REASON_CUSTOMER, notifyCustomer: true);
    }

    /**
     * @return array{ride: RideRequest, refunded: bool, refund_error: ?string}|null
     */
    public function cancelIfDue(string $conn, RideRequest $ride, ?CarbonInterface $now = null): ?array
    {
        $ride = RideRequest::on($conn)->find($ride->id) ?? $ride;
        if (! $this->isDueForAutoCancel($ride, $now)) {
            return null;
        }

        return $this->cancelUnacceptedRide($conn, $ride, self::REASON_AUTO_UNACCEPTED, notifyCustomer: true);
    }

    /**
     * @return array{cancelled: int, refunded: int, failed_refunds: int}
     */
    public function processDueAutoCancels(string $conn, ?CarbonInterface $now = null, int $limit = 50): array
    {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $stats = ['cancelled' => 0, 'refunded' => 0, 'failed_refunds' => 0];
        $now = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        $candidates = RideRequest::on($conn)
            ->whereIn('status', [
                RideRequest::STATUS_PENDING_DISPATCH,
                RideRequest::STATUS_OFFERED,
            ])
            ->whereNull('driver_id')
            ->whereNotNull('pickup_at')
            ->where('pickup_at', '<=', ContractTransportTimezone::naiveUtcForWallClockQuery($now))
            ->where(function ($q) {
                $q->whereNull('ride_type')
                    ->orWhereNotIn('ride_type', [
                        RideRequest::RIDE_TYPE_CONTRACT_GROUP,
                        RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
                    ]);
            })
            ->orderBy('pickup_at')
            ->limit($limit)
            ->get();

        foreach ($candidates as $ride) {
            try {
                $result = $this->cancelIfDue($conn, $ride, $now);
                if ($result === null) {
                    continue;
                }
                $stats['cancelled']++;
                if ($result['refunded']) {
                    $stats['refunded']++;
                } elseif ($result['refund_error']) {
                    $stats['failed_refunds']++;
                }
            } catch (\Throwable $e) {
                Log::warning('Auto-annuleren rit mislukt', [
                    'ride_request_id' => $ride->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * @return array{ride: RideRequest, refunded: bool, refund_error: ?string}
     */
    public function cancelUnacceptedRide(
        string $conn,
        RideRequest $ride,
        string $reason,
        bool $notifyCustomer = true
    ): array {
        $driverIds = [];

        $fresh = DB::connection($conn)->transaction(function () use ($conn, $ride, $reason, &$driverIds) {
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->firstOrFail();

            if (! $this->isUnacceptedOpenBooking($locked)) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze rit kan niet meer worden geannuleerd.'],
                ]);
            }

            $driverIds = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $locked->id)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->pluck('driver_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $locked->id)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->update([
                    'status' => RideDispatchOffer::STATUS_SUPERSEDED,
                    'responded_at' => now(),
                ]);

            $payload = is_array($locked->booking_payload) ? $locked->booking_payload : [];
            $payload['cancellation'] = [
                'reason' => $reason,
                'cancelled_at' => now()->toIso8601String(),
            ];

            $locked->update([
                'status' => RideRequest::STATUS_CANCELLED,
                'booking_payload' => $payload,
            ]);

            return $locked->fresh() ?? $locked;
        });

        $refunded = false;
        $refundError = null;
        if (in_array($fresh->payment_status, [
            RideRequest::PAYMENT_STATUS_PAID,
            RideRequest::PAYMENT_STATUS_REFUND_FAILED,
            RideRequest::PAYMENT_STATUS_REFUND_PENDING,
        ], true)) {
            $refund = $this->payments->refundPaidRidePayment($conn, $fresh);
            $refunded = (bool) $refund['refunded'];
            $refundError = $refund['error'];
            $fresh = $fresh->fresh() ?? $fresh;
            if ($refundError) {
                Log::warning('Terugbetaling na annulering mislukt', [
                    'ride_request_id' => $fresh->id,
                    'reason' => $reason,
                    'error' => $refundError,
                ]);
            }
        }

        foreach ($driverIds as $driverId) {
            try {
                $this->driverPush->notifyDriver($driverId, (int) $fresh->id);
            } catch (\Throwable) {
                // push is best-effort
            }
        }

        if ($notifyCustomer) {
            $extra = $reason === self::REASON_AUTO_UNACCEPTED
                ? ['Er is binnen de beschikbare tijd geen chauffeur gevonden.']
                : ['U heeft deze rit geannuleerd.'];
            if ($refunded) {
                $extra[] = 'Het vooraf betaalde bedrag wordt teruggestort.';
            } elseif ($refundError) {
                $extra[] = 'De terugbetaling kon niet automatisch worden afgerond. Neem contact op met de taxi.';
            }

            try {
                $this->statusNotifications->notify(
                    $conn,
                    $fresh,
                    WhatsAppBookingMessageComposer::EVENT_CANCELLED,
                    ['extra_lines' => $extra],
                    force: true
                );
            } catch (\Throwable $e) {
                Log::warning('Klantmelding na annulering mislukt', [
                    'ride_request_id' => $fresh->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'ride' => $fresh,
            'refunded' => $refunded,
            'refund_error' => $refundError,
        ];
    }

    private function isUnacceptedOpenBooking(RideRequest $ride): bool
    {
        if ($ride->isContractRide()) {
            return false;
        }

        if ((int) ($ride->driver_id ?? 0) > 0) {
            return false;
        }

        return in_array($ride->status, [
            RideRequest::STATUS_PENDING_DISPATCH,
            RideRequest::STATUS_OFFERED,
        ], true);
    }

    private function signedCancelUrlExpiresAt(RideRequest $ride): CarbonInterface
    {
        $companyId = (int) ($ride->company_id ?? 0);
        $cancelAt = $this->dispatchSettings->unacceptedAutoCancelAt(
            $ride,
            $companyId > 0 ? $companyId : null
        );

        $fallback = now()->addDays(14);
        if (! $cancelAt) {
            return $fallback;
        }

        // Link blijft geldig tot na het auto-annuleervenster (+ buffer).
        $until = $cancelAt->copy()->addDay();

        return $until->gt($fallback) ? $until : $fallback;
    }
}
