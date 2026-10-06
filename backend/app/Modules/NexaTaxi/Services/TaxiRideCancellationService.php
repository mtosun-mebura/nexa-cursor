<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
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
 * Annuleren van boekingen (klant, automatisch, of chauffeur marketplace) + Mollie-terugbetaling.
 */
class TaxiRideCancellationService
{
    public const REASON_CUSTOMER = 'customer';

    public const REASON_AUTO_UNACCEPTED = 'auto_unaccepted';

    public const REASON_DRIVER = 'driver';

    public const CHOICE_WAIT = 'wait';

    public const PAYLOAD_DECISION_KEY = 'unaccepted_customer_decision';

    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected TaxiRidePaymentService $payments,
        protected TaxiCustomerRideStatusNotificationService $statusNotifications,
        protected TaxiDriverInboxPushService $driverPush,
    ) {}

    /**
     * Voorgedefinieerde annuleringsredenen voor marketplace-ritten (na acceptatie, vóór start).
     *
     * @return list<array{code: string, label: string, message: string}>
     */
    public static function driverCancelReasons(): array
    {
        return [
            [
                'code' => 'customer_no_show',
                'label' => 'Klant niet aanwezig',
                'message' => 'De chauffeur heeft je niet aangetroffen op het ophaaladres.',
            ],
            [
                'code' => 'customer_unreachable',
                'label' => 'Klant niet bereikbaar',
                'message' => 'De chauffeur kon telefonisch geen contact met je krijgen.',
            ],
            [
                'code' => 'wrong_address',
                'label' => 'Adres onjuist of onbereikbaar',
                'message' => 'Het ophaaladres klopte niet of was niet bereikbaar voor de chauffeur.',
            ],
            [
                'code' => 'vehicle_issue',
                'label' => 'Voertuigprobleem',
                'message' => 'Door een voertuigprobleem kan de rit helaas niet doorgaan.',
            ],
            [
                'code' => 'traffic_delay',
                'label' => 'Onverwachte vertraging',
                'message' => 'Door onverwachte vertraging kan de chauffeur de ophaaltijd niet meer halen.',
            ],
            [
                'code' => 'unsafe_situation',
                'label' => 'Onveilige situatie',
                'message' => 'De rit is geannuleerd vanwege een onveilige situatie.',
            ],
            [
                'code' => 'capacity',
                'label' => 'Geen passende capaciteit',
                'message' => 'Het voertuig past niet bij het aantal personen of de bagage van deze rit.',
            ],
            [
                'code' => 'other',
                'label' => 'Overige reden',
                'message' => 'De chauffeur heeft de rit moeten annuleren.',
            ],
        ];
    }

    public static function driverCancelMessage(string $reasonCode): ?string
    {
        foreach (self::driverCancelReasons() as $reason) {
            if ($reason['code'] === $reasonCode) {
                return $reason['message'];
            }
        }

        return null;
    }

    public function canCancelAcceptedByDriver(RideRequest $ride, User $driver): bool
    {
        if (! $ride->isNexaSuiteBooking() || $ride->isContractRide()) {
            return false;
        }

        if ((int) ($ride->driver_id ?? 0) !== (int) $driver->id) {
            return false;
        }

        // Alleen vóór start: geaccepteerd, nog niet onderweg.
        return $ride->status === RideRequest::STATUS_ACCEPTED;
    }

    public function isCancellableByCustomer(RideRequest $ride): bool
    {
        return $this->isUnacceptedOpenBooking($ride);
    }

    public function isDueForAutoCancel(RideRequest $ride, ?CarbonInterface $now = null): bool
    {
        if (! $this->isUnacceptedOpenBooking($ride)) {
            return false;
        }

        if ($this->customerChoseWait($ride)) {
            return false;
        }

        $timeoutAt = $this->decisionTimeoutAt($ride);
        if (! $timeoutAt) {
            return false;
        }

        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        return $timeoutAt->lte($base);
    }

    public function customerDecisionMinutes(?int $companyId = null): int
    {
        return $this->dispatchSettings->customerUnacceptedDecisionMinutes(
            $companyId !== null && $companyId > 0 ? $companyId : null
        );
    }

    public function customerDecisionMinutesForRide(RideRequest $ride): int
    {
        return $this->dispatchSettings->customerUnacceptedDecisionMinutesForRide(
            $ride,
            $this->settingsCompanyIdForRide($ride) ?: null
        );
    }

    public function searchDeadlineAt(RideRequest $ride): ?CarbonInterface
    {
        $companyId = $this->settingsCompanyIdForRide($ride);

        return $this->dispatchSettings->unacceptedAutoCancelAt(
            $ride,
            $companyId > 0 ? $companyId : null
        );
    }

    public function decisionTimeoutAt(RideRequest $ride): ?CarbonInterface
    {
        $minutes = $this->customerDecisionMinutesForRide($ride);
        if ($minutes <= 0) {
            return null;
        }

        $decision = $this->decisionPayload($ride);
        if (! empty($decision['prompt_at'])) {
            try {
                return Carbon::parse($decision['prompt_at'])
                    ->timezone(ContractTransportTimezone::TIMEZONE)
                    ->addMinutes($minutes);
            } catch (\Throwable) {
                // val terug op zoekdeadline
            }
        }

        $deadline = $this->searchDeadlineAt($ride);
        if (! $deadline) {
            return null;
        }

        return $deadline->copy()->addMinutes($minutes);
    }

    public function customerChoseWait(RideRequest $ride): bool
    {
        return ($this->decisionPayload($ride)['choice'] ?? null) === self::CHOICE_WAIT;
    }

    public function needsCustomerDecision(RideRequest $ride, ?CarbonInterface $now = null): bool
    {
        if (! $this->isUnacceptedOpenBooking($ride) || $this->customerChoseWait($ride)) {
            return false;
        }

        $deadline = $this->searchDeadlineAt($ride);
        if (! $deadline) {
            return false;
        }

        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        return $deadline->lte($base);
    }

    public function rememberDecisionPrompt(string $conn, RideRequest $ride, ?CarbonInterface $now = null): RideRequest
    {
        $ride = RideRequest::on($conn)->find($ride->id) ?? $ride;
        if (! $this->needsCustomerDecision($ride, $now)) {
            return $ride;
        }

        $decision = $this->decisionPayload($ride);
        if (! empty($decision['prompt_at'])) {
            return $ride;
        }

        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $payload[self::PAYLOAD_DECISION_KEY] = array_merge($decision, [
            'prompt_at' => $base->toIso8601String(),
        ]);
        $ride->update(['booking_payload' => $payload]);

        return $ride->fresh() ?? $ride;
    }

    /**
     * @return array{ride: RideRequest}
     */
    public function chooseToWait(string $conn, RideRequest $ride): array
    {
        if (! $this->isUnacceptedOpenBooking($ride)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit kan niet meer worden aangepast.'],
            ]);
        }

        $ride = RideRequest::on($conn)->find($ride->id) ?? $ride;
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $decision = $this->decisionPayload($ride);
        $payload[self::PAYLOAD_DECISION_KEY] = array_merge($decision, [
            'choice' => self::CHOICE_WAIT,
            'choice_at' => now()->toIso8601String(),
            'prompt_at' => $decision['prompt_at'] ?? now()->toIso8601String(),
        ]);
        $ride->update(['booking_payload' => $payload]);

        return ['ride' => $ride->fresh() ?? $ride];
    }

    /**
     * @return array<string, mixed>
     */
    public function decisionPayload(RideRequest $ride): array
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $decision = $payload[self::PAYLOAD_DECISION_KEY] ?? null;

        return is_array($decision) ? $decision : [];
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
                RideRequest::STATUS_PENDING_PAYMENT,
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
                $ride = $this->rememberDecisionPrompt($conn, $ride, $now);
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
     * Marketplace: chauffeur annuleert een geaccepteerde rit (vóór start) met vaste reden voor de klant.
     *
     * @return array{ride: RideRequest, refunded: bool, refund_error: ?string, message: string}
     */
    public function cancelAcceptedByDriver(
        string $conn,
        User $driver,
        int $rideId,
        string $reasonCode
    ): array {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);
        TaxiDispatchSchema::ensureOfferDeclineReasonColumn($conn);

        $message = self::driverCancelMessage($reasonCode);
        if ($message === null) {
            throw ValidationException::withMessages([
                'reason_code' => ['Kies een geldige annuleringsreden.'],
            ]);
        }

        $fresh = DB::connection($conn)->transaction(function () use (
            $conn,
            $driver,
            $rideId,
            $reasonCode,
            $message
        ) {
            $locked = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $locked || ! $this->canCancelAcceptedByDriver($locked, $driver)) {
                throw ValidationException::withMessages([
                    'ride' => ['Deze marketplace-rit kan nu niet worden geannuleerd.'],
                ]);
            }

            $now = now();

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $locked->id)
                ->where('driver_id', $driver->id)
                ->where('status', RideDispatchOffer::STATUS_ACCEPTED)
                ->update([
                    'status' => RideDispatchOffer::STATUS_DECLINED,
                    'responded_at' => $now,
                    'decline_reason' => $message,
                ]);

            RideDispatchOffer::on($conn)
                ->where('ride_request_id', $locked->id)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->update([
                    'status' => RideDispatchOffer::STATUS_SUPERSEDED,
                    'responded_at' => $now,
                ]);

            $payload = is_array($locked->booking_payload) ? $locked->booking_payload : [];
            $payload['cancellation'] = [
                'reason' => self::REASON_DRIVER,
                'reason_code' => $reasonCode,
                'message' => $message,
                'cancelled_by' => 'driver',
                'driver_id' => (int) $driver->id,
                'cancelled_at' => $now->toIso8601String(),
            ];

            $locked->update([
                'status' => RideRequest::STATUS_CANCELLED,
                'booking_payload' => $payload,
                'pickup_proposal_at' => null,
                'pickup_proposal_status' => null,
                'pickup_proposal_customer_remark' => null,
                'pickup_proposal_sent_at' => null,
                'pickup_proposal_responded_at' => null,
                'pickup_proposal_whatsapp_wamid' => null,
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
                Log::warning('Terugbetaling na chauffeur-annulering mislukt', [
                    'ride_request_id' => $fresh->id,
                    'reason_code' => $reasonCode,
                    'error' => $refundError,
                ]);
            }
        }

        $extra = [$message];
        if ($refunded) {
            $days = app(TaxiCustomerRideCancelledMailer::class)->refundBusinessDays();
            $extra[] = 'Het vooraf betaalde bedrag wordt teruggestort (doorgaans binnen '.$days.' werkdagen).';
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
            Log::warning('Klantmelding na chauffeur-annulering mislukt', [
                'ride_request_id' => $fresh->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(TaxiCustomerRideCancelledMailer::class)->send($conn, $fresh, [
                'reason' => self::REASON_DRIVER,
                'message' => $message,
                'refunded' => $refunded,
                'refund_error' => $refundError,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Annulatie-e-mail na chauffeur-annulering mislukt', [
                'ride_request_id' => $fresh->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->driverPush->notifyDriver((int) $driver->id, (int) $fresh->id);
        } catch (\Throwable) {
            // push is best-effort
        }

        return [
            'ride' => $fresh,
            'refunded' => $refunded,
            'refund_error' => $refundError,
            'message' => $message,
        ];
    }

    /**
     * @return array{reason?: string, reason_code?: string, message?: string}|null
     */
    public function cancellationPayload(RideRequest $ride): ?array
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $cancellation = $payload['cancellation'] ?? null;

        return is_array($cancellation) ? $cancellation : null;
    }

    public function cancellationMessageForCustomer(RideRequest $ride): ?string
    {
        $cancellation = $this->cancellationPayload($ride);
        if (! $cancellation) {
            return null;
        }

        $message = trim((string) ($cancellation['message'] ?? ''));
        if ($message !== '') {
            return $message;
        }

        $reason = (string) ($cancellation['reason'] ?? '');
        if ($reason === self::REASON_AUTO_UNACCEPTED) {
            return 'Er is binnen de beschikbare tijd geen chauffeur gevonden.';
        }
        if ($reason === self::REASON_CUSTOMER) {
            return 'Je hebt deze rit geannuleerd.';
        }

        return null;
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
                $days = app(TaxiCustomerRideCancelledMailer::class)->refundBusinessDays();
                $extra[] = 'Het vooraf betaalde bedrag wordt teruggestort (doorgaans binnen '.$days.' werkdagen).';
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

            try {
                app(TaxiCustomerRideCancelledMailer::class)->send($conn, $fresh, [
                    'reason' => $reason,
                    'refunded' => $refunded,
                    'refund_error' => $refundError,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Annulatie-e-mail na annulering mislukt', [
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

    public function settingsCompanyIdForRide(RideRequest $ride): int
    {
        $companyId = (int) ($ride->company_id ?? 0);
        if ($companyId > 0) {
            return $companyId;
        }

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $marketplace = is_array($payload['marketplace'] ?? null) ? $payload['marketplace'] : [];

        return (int) ($marketplace['settings_company_id'] ?? $marketplace['company_id'] ?? 0);
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
            RideRequest::STATUS_PENDING_PAYMENT,
            RideRequest::STATUS_PENDING_DISPATCH,
            RideRequest::STATUS_OFFERED,
        ], true);
    }

    private function signedCancelUrlExpiresAt(RideRequest $ride): CarbonInterface
    {
        $companyId = $this->settingsCompanyIdForRide($ride);
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
