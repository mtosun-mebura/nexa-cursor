<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\PaymentProviderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxiRidePaymentService
{
    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected TaxiMolliePaymentService $mollie,
        protected PaymentProviderService $paymentProviders
    ) {}

    public function requiresPaymentBeforeComplete(RideRequest $ride): bool
    {
        if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            return false;
        }

        if ($ride->payment_method === RideRequest::PAYMENT_METHOD_BOOKING) {
            return true;
        }

        if ($ride->payment_method === RideRequest::PAYMENT_METHOD_DRIVER) {
            $companyId = (int) ($ride->company_id ?? 0);
            if (! $this->dispatchSettings->paymentDriverEnabled($companyId > 0 ? $companyId : null)) {
                return false;
            }

            return $ride->payment_status !== RideRequest::PAYMENT_STATUS_PAID;
        }

        return false;
    }

    public function canCompleteRide(RideRequest $ride): bool
    {
        return ! $this->requiresPaymentBeforeComplete($ride);
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentSummaryForRide(RideRequest $ride, ?int $companyId = null): array
    {
        $companyId = $companyId ?? (int) ($ride->company_id ?? 0);
        $options = $this->dispatchSettings->paymentOptionsForTenant($companyId > 0 ? $companyId : null);

        return [
            'method' => $ride->payment_method,
            'status' => $ride->payment_status,
            'amount_due' => $ride->chargeableAmount(),
            'quoted_price' => $ride->quoted_price !== null ? (float) $ride->quoted_price : null,
            'final_price' => $ride->final_price !== null ? (float) $ride->final_price : null,
            'can_complete' => $this->canCompleteRide($ride),
            'requires_payment_before_complete' => $this->requiresPaymentBeforeComplete($ride),
            'driver_payment_enabled' => $options['driver'],
            'booking_payment_enabled' => $options['booking'],
            'payment_error' => $this->driverPaymentErrorMessage($ride),
        ];
    }

    public function driverPaymentErrorMessage(RideRequest $ride): ?string
    {
        if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            return null;
        }

        if ($ride->payment_method !== RideRequest::PAYMENT_METHOD_DRIVER) {
            return null;
        }

        $payment = RidePayment::on($ride->getConnectionName())
            ->where('ride_request_id', $ride->id)
            ->where('channel', RidePayment::CHANNEL_DRIVER)
            ->whereIn('status', [
                RidePayment::STATUS_FAILED,
                RidePayment::STATUS_CANCELED,
                RidePayment::STATUS_EXPIRED,
            ])
            ->orderByDesc('id')
            ->first();

        if (! $payment) {
            return null;
        }

        return match ($payment->status) {
            RidePayment::STATUS_FAILED => 'Betaling is mislukt. Probeer opnieuw te betalen.',
            RidePayment::STATUS_CANCELED => 'Betaling is geannuleerd. Probeer opnieuw te betalen.',
            RidePayment::STATUS_EXPIRED => 'Betaling is verlopen. Probeer opnieuw te betalen.',
            default => 'Betaling is niet gelukt. Probeer opnieuw te betalen.',
        };
    }

    public function validatePaymentMethodChoice(?string $method, ?int $companyId): ?string
    {
        $options = $this->dispatchSettings->paymentOptionsForTenant($companyId);
        $booking = $options['booking'];
        $driver = $options['driver'];

        if (! $booking && ! $driver) {
            return null;
        }

        if ($booking && ! $driver) {
            return RideRequest::PAYMENT_METHOD_BOOKING;
        }

        if ($driver && ! $booking) {
            return RideRequest::PAYMENT_METHOD_DRIVER;
        }

        if (! in_array($method, [RideRequest::PAYMENT_METHOD_BOOKING, RideRequest::PAYMENT_METHOD_DRIVER], true)) {
            throw ValidationException::withMessages([
                'payment_method' => ['Kies een betaalmethode.'],
            ]);
        }

        return $method;
    }

    public function markDriverCashPaid(string $conn, RideRequest $ride, ?float $amount = null): RideRequest
    {
        $companyId = (int) ($ride->company_id ?? 0);
        if (! $this->dispatchSettings->paymentDriverEnabled($companyId > 0 ? $companyId : null)) {
            throw ValidationException::withMessages([
                'payment' => ['Betaling via de chauffeur-app is niet ingeschakeld.'],
            ]);
        }

        if ((int) $ride->driver_id <= 0) {
            throw ValidationException::withMessages([
                'ride' => ['Geen actieve chauffeur op deze rit.'],
            ]);
        }

        if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            throw ValidationException::withMessages([
                'payment' => ['Deze rit is al betaald.'],
            ]);
        }

        $amount = $amount ?? $ride->chargeableAmount();
        if ($amount === null || $amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Geen geldig bedrag voor contante betaling.'],
            ]);
        }

        return DB::connection($conn)->transaction(function () use ($conn, $ride, $amount, $companyId) {
            $ride = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->firstOrFail();

            if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
                throw ValidationException::withMessages([
                    'payment' => ['Deze rit is al betaald.'],
                ]);
            }

            RidePayment::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('channel', RidePayment::CHANNEL_DRIVER)
                ->where('status', RidePayment::STATUS_OPEN)
                ->update(['status' => RidePayment::STATUS_CANCELED]);

            $ridePayment = RidePayment::on($conn)->create([
                'ride_request_id' => $ride->id,
                'company_id' => $companyId > 0 ? $companyId : null,
                'channel' => RidePayment::CHANNEL_CASH,
                'amount' => round($amount, 2),
                'currency' => 'EUR',
                'status' => RidePayment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            return $this->markRidePaid($conn, $ride, $ridePayment);
        });
    }

    public function createDriverPayment(
        string $conn,
        RideRequest $ride,
        float $amount
    ): array {
        $companyId = (int) ($ride->company_id ?? 0);
        if (! $this->dispatchSettings->paymentDriverEnabled($companyId > 0 ? $companyId : null)) {
            throw ValidationException::withMessages([
                'payment' => ['Betaling via de chauffeur-app is niet ingeschakeld.'],
            ]);
        }

        if ((int) $ride->driver_id <= 0) {
            throw ValidationException::withMessages([
                'ride' => ['Geen actieve chauffeur op deze rit.'],
            ]);
        }

        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Het bedrag moet minimaal € 0,01 zijn.'],
            ]);
        }

        return $this->createMolliePaymentForRide(
            $conn,
            $ride,
            $amount,
            RidePayment::CHANNEL_DRIVER,
            route('taxi.chauffeur.payment.return', ['ride' => $ride->id])
        );
    }

    public function createBookingPayment(
        string $conn,
        RideRequest $ride,
        float $amount
    ): array {
        return $this->createMolliePaymentForRide(
            $conn,
            $ride,
            $amount,
            RidePayment::CHANNEL_BOOKING,
            route('nexataxi.booking.payment.return', ['ride' => $ride->id])
        );
    }

    /**
     * @return array{payment: RidePayment, checkout_url: string, qr_url: string}
     */
    protected function createMolliePaymentForRide(
        string $conn,
        RideRequest $ride,
        float $amount,
        string $channel,
        string $redirectUrl
    ): array {
        $companyId = $this->resolveRideCompanyId($ride);
        $apiKey = $this->paymentProviders->mollieApiKeyForCompany($companyId);
        if (! $apiKey) {
            $hint = $this->paymentProviders->allowMollieTestProviders()
                ? ' Stel onder Betalingsproviders een Mollie-provider in voor dit bedrijf (test_-sleutel en testmodus zijn toegestaan in deze omgeving).'
                : ' Stel een actieve Mollie-provider in onder Betalingsproviders.';

            throw ValidationException::withMessages([
                'payment' => ['Mollie is niet geconfigureerd voor dit bedrijf.'.$hint],
            ]);
        }

        return DB::connection($conn)->transaction(function () use ($conn, $ride, $amount, $channel, $redirectUrl, $apiKey, $companyId) {
            $ride = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->firstOrFail();

            $ridePayment = RidePayment::on($conn)->create([
                'ride_request_id' => $ride->id,
                'company_id' => $companyId > 0 ? $companyId : null,
                'channel' => $channel,
                'amount' => round($amount, 2),
                'currency' => 'EUR',
                'status' => RidePayment::STATUS_OPEN,
            ]);

            $description = 'Taxi rit #'.$ride->id;
            if ($ride->customer_name) {
                $description .= ' – '.$ride->customer_name;
            }

            $webhookUrl = $this->paymentProviders->mollieWebhookUrlForPayment($companyId);

            $molliePayment = $this->mollie->createPayment(
                $apiKey,
                $amount,
                $description,
                $redirectUrl,
                $webhookUrl,
                [
                    'ride_payment_id' => (string) $ridePayment->id,
                    'ride_request_id' => (string) $ride->id,
                    'conn' => $conn,
                    'channel' => $channel,
                ]
            );

            $checkoutUrl = $this->mollie->checkoutUrl($molliePayment);
            if (! $checkoutUrl) {
                throw ValidationException::withMessages([
                    'payment' => ['Mollie-betaallink kon niet worden aangemaakt.'],
                ]);
            }

            $ridePayment->update([
                'mollie_payment_id' => $molliePayment['id'] ?? null,
                'checkout_url' => $checkoutUrl,
                'mollie_payload' => $molliePayment,
            ]);

            $ride->update([
                'final_price' => round($amount, 2),
                'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            ]);

            return [
                'payment' => $ridePayment->fresh(),
                'checkout_url' => $checkoutUrl,
                'qr_url' => $this->qrImageUrl($checkoutUrl),
            ];
        });
    }

    public function qrImageUrl(string $checkoutUrl): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&margin=10&data='.rawurlencode($checkoutUrl);
    }

    public function syncRidePaymentFromMollie(string $conn, RidePayment $ridePayment): RidePayment
    {
        $ride = RideRequest::on($conn)->find($ridePayment->ride_request_id);
        $companyId = $ride
            ? $this->resolveRideCompanyId($ride, $ridePayment)
            : (((int) ($ridePayment->company_id ?? 0)) > 0 ? (int) $ridePayment->company_id : null);
        $apiKey = $this->paymentProviders->mollieApiKeyForCompany($companyId);
        if (! $apiKey || ! $ridePayment->mollie_payment_id) {
            return $ridePayment;
        }

        $molliePayment = $this->mollie->fetchPayment($apiKey, $ridePayment->mollie_payment_id);
        if (! $molliePayment) {
            return $ridePayment;
        }

        return $this->applyMollieStatus($conn, $ridePayment, $molliePayment);
    }

    public function applyMollieStatus(string $conn, RidePayment $ridePayment, array $molliePayment): RidePayment
    {
        $status = $this->mollie->mapMollieStatus((string) ($molliePayment['status'] ?? 'open'));

        return DB::connection($conn)->transaction(function () use ($conn, $ridePayment, $molliePayment, $status) {
            $ridePayment = RidePayment::on($conn)->whereKey($ridePayment->id)->lockForUpdate()->firstOrFail();
            $ride = RideRequest::on($conn)->whereKey($ridePayment->ride_request_id)->lockForUpdate()->first();

            $updates = [
                'status' => $status,
                'mollie_payload' => $molliePayment,
            ];

            if ($status === RidePayment::STATUS_PAID && ! $ridePayment->paid_at) {
                $updates['paid_at'] = now();
            }

            $ridePayment->update($updates);

            if ($ride && $status === RidePayment::STATUS_PAID) {
                $this->markRidePaid($conn, $ride, $ridePayment);
            }

            return $ridePayment->fresh();
        });
    }

    public function markRidePaid(string $conn, RideRequest $ride, RidePayment $ridePayment): RideRequest
    {
        $ride->update([
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'final_price' => $ridePayment->amount,
        ]);

        if ($ride->status === RideRequest::STATUS_PENDING_PAYMENT) {
            $ride->update(['status' => RideRequest::STATUS_PENDING_DISPATCH]);
            $companyId = (int) ($ride->company_id ?? 0);
            if ($companyId > 0) {
                $freshRide = $ride->fresh();
                try {
                    app(RideDispatchService::class)->startDispatch($conn, $freshRide, $companyId);
                } catch (\Throwable) {
                    // dispatch failure logged elsewhere
                }
                try {
                    app(TaxiBookingNotificationService::class)->notifyNewRide($conn, $freshRide);
                } catch (\Throwable) {
                    // notification failure logged elsewhere
                }
            }
        }

        $fresh = $ride->fresh();

        try {
            app(TaxiRideInvoiceService::class)->ensureInvoiceForPaidRide($conn, $fresh);
        } catch (\Throwable $e) {
            report($e);
        }

        return $fresh;
    }

    /**
     * Bepaal tenant voor Mollie: rit → openstaande betaling → ingelogde chauffeur.
     */
    protected function resolveRideCompanyId(RideRequest $ride, ?RidePayment $ridePayment = null): ?int
    {
        $companyId = (int) ($ride->company_id ?? 0);
        if ($companyId > 0) {
            return $companyId;
        }

        if ($ridePayment !== null) {
            $fromPayment = (int) ($ridePayment->company_id ?? 0);
            if ($fromPayment > 0) {
                return $fromPayment;
            }
        }

        $user = auth()->user();
        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        return null;
    }

    public function handleWebhookPaymentId(string $molliePaymentId): void
    {
        $conn = app(\App\Services\ModuleDatabaseService::class)->getModuleConnectionName('taxi');

        $ridePayment = RidePayment::on($conn)
            ->where('mollie_payment_id', $molliePaymentId)
            ->first();

        if (! $ridePayment) {
            return;
        }

        $this->syncRidePaymentFromMollie($conn, $ridePayment);
    }
}
