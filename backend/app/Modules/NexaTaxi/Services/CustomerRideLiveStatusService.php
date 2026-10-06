<?php

namespace App\Modules\NexaTaxi\Services;

use App\Helpers\GeoHelper;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideGpsPoint;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiCustomerAppSchema;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiRideTrackSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CustomerRideLiveStatusService
{
    /** Gemiddelde stadsrit-snelheid voor ETA (km/u). */
    public const ETA_AVG_SPEED_KMH = 28.0;

    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    public function connection(): string
    {
        return $this->moduleDb->getModuleConnectionName('taxi');
    }

    public function ensureSchema(): void
    {
        $conn = $this->connection();
        TaxiCustomerAppSchema::ensureTrackTokenColumn($conn);
        TaxiDispatchSchema::ensureFulfillingCompanyColumn($conn);
        TaxiRideTrackSchema::ensure($conn);
    }

    public function issueTrackToken(RideRequest $ride): string
    {
        $this->ensureSchema();
        $token = Str::random(48);
        $ride->customer_track_token = $token;
        $ride->save();

        return $token;
    }

    public function findByTrackToken(string $token): ?RideRequest
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 24) {
            return null;
        }

        $this->ensureSchema();
        $conn = $this->connection();
        if (! Schema::connection($conn)->hasColumn('ride_requests', 'customer_track_token')) {
            return null;
        }

        return RideRequest::on($conn)
            ->where('customer_track_token', $token)
            ->first();
    }

    /**
     * Sync open Mollie-betaling zodat pending_payment → searching na return/webhook.
     */
    public function syncPendingBookingPayment(RideRequest $ride): RideRequest
    {
        if ($ride->status !== RideRequest::STATUS_PENDING_PAYMENT) {
            return $ride;
        }
        if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            return $ride;
        }

        $conn = $ride->getConnectionName() ?: $this->connection();
        $payment = RidePayment::on($conn)
            ->where('ride_request_id', $ride->id)
            ->where('channel', RidePayment::CHANNEL_BOOKING)
            ->orderByDesc('id')
            ->first();

        if (! $payment || $payment->status !== RidePayment::STATUS_OPEN) {
            return $ride;
        }

        try {
            app(TaxiRidePaymentService::class)->syncRidePaymentFromMollie($conn, $payment);
        } catch (\Throwable) {
            return $ride;
        }

        return RideRequest::on($conn)->find($ride->id) ?? $ride;
    }

    /**
     * @return array<string, mixed>
     */
    public function livePayload(RideRequest $ride): array
    {
        $this->ensureSchema();
        $conn = $ride->getConnectionName() ?: $this->connection();
        $status = (string) $ride->status;
        $labels = RideRequest::statusLabels();
        $accepted = in_array($status, [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
        ], true);

        $companyId = (int) ($ride->fulfilling_company_id ?: $ride->company_id ?: 0);
        $company = $companyId > 0 ? Company::query()->find($companyId) : null;

        $vehicle = null;
        $vehicleId = (int) ($ride->vehicle_id ?? 0);
        if ($vehicleId > 0) {
            $vehicle = Vehicle::on($conn)->find($vehicleId);
        }

        $driver = null;
        $driverId = (int) ($ride->driver_id ?? 0);
        if ($driverId > 0) {
            $driver = User::query()->find($driverId);
        }

        $driverLoc = $this->resolveDriverLocation($conn, $ride, $driverId);
        $eta = null;
        if ($accepted && $driverLoc && $ride->pickup_lat !== null && $ride->pickup_lng !== null) {
            $eta = $this->estimateEtaMinutes(
                (float) $driverLoc['lat'],
                (float) $driverLoc['lng'],
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng
            );
        }

        $phase = match (true) {
            $status === RideRequest::STATUS_CANCELLED => 'cancelled',
            $status === RideRequest::STATUS_COMPLETED => 'completed',
            $accepted => 'accepted',
            $status === RideRequest::STATUS_PENDING_PAYMENT => 'awaiting_payment',
            in_array($status, [
                RideRequest::STATUS_PENDING_DISPATCH,
                RideRequest::STATUS_OFFERED,
            ], true) => 'searching',
            default => 'other',
        };

        $cancellation = app(TaxiRideCancellationService::class);
        $canCancel = $cancellation->isCancellableByCustomer($ride);
        $needsDecision = $canCancel && $cancellation->needsCustomerDecision($ride);
        $choseWait = $canCancel && $cancellation->customerChoseWait($ride);
        $autoCancelAt = null;
        $autoCancelLabel = null;
        $decisionDeadlineAt = null;
        $decisionDeadlineLabel = null;
        $decisionMinutes = $cancellation->customerDecisionMinutesForRide($ride);
        if ($canCancel) {
            $promptAt = $cancellation->searchDeadlineAt($ride);
            if ($promptAt) {
                $autoCancelAt = $promptAt->toIso8601String();
                $autoCancelLabel = $promptAt->timezone(\App\Modules\NexaTaxi\Support\ContractTransportTimezone::TIMEZONE)
                    ->format('H:i');
            }
            $timeoutAt = $cancellation->decisionTimeoutAt($ride);
            if ($timeoutAt && $needsDecision) {
                $decisionDeadlineAt = $timeoutAt->toIso8601String();
                $decisionDeadlineLabel = $timeoutAt->timezone(\App\Modules\NexaTaxi\Support\ContractTransportTimezone::TIMEZONE)
                    ->format('H:i');
            }
        }

        $refundDays = app(TaxiCustomerRideCancelledMailer::class)->refundBusinessDays();
        $canDownloadInvoice = $status === RideRequest::STATUS_COMPLETED;
        $paymentStatus = (string) ($ride->payment_status ?? '');
        $paymentPaid = $paymentStatus === RideRequest::PAYMENT_STATUS_PAID;
        $canRetryPayment = $status === RideRequest::STATUS_PENDING_PAYMENT && ! $paymentPaid;
        $latestBookingPayment = RidePayment::on($conn)
            ->where('ride_request_id', $ride->id)
            ->where('channel', RidePayment::CHANNEL_BOOKING)
            ->orderByDesc('id')
            ->first();
        $checkoutUrl = null;
        if ($canRetryPayment && $latestBookingPayment
            && $latestBookingPayment->status === RidePayment::STATUS_OPEN
            && is_string($latestBookingPayment->checkout_url)
            && $latestBookingPayment->checkout_url !== '') {
            $checkoutUrl = $latestBookingPayment->checkout_url;
        }

        $paymentFailureStatus = null;
        $paymentError = null;
        if ($canRetryPayment && $latestBookingPayment) {
            $payStatus = (string) $latestBookingPayment->status;
            if (in_array($payStatus, [
                RidePayment::STATUS_FAILED,
                RidePayment::STATUS_CANCELED,
                RidePayment::STATUS_EXPIRED,
            ], true)) {
                $paymentFailureStatus = $payStatus;
                $paymentError = match ($payStatus) {
                    RidePayment::STATUS_FAILED => 'De betaling is mislukt. Betaal opnieuw om de rit te activeren, of annuleer de rit.',
                    RidePayment::STATUS_CANCELED => 'De betaling is mislukt (afgebroken). Betaal opnieuw om de rit te activeren, of annuleer de rit.',
                    RidePayment::STATUS_EXPIRED => 'De betaling is mislukt (verlopen). Betaal opnieuw om de rit te activeren, of annuleer de rit.',
                    default => 'De betaling is mislukt. Betaal opnieuw om de rit te activeren, of annuleer de rit.',
                };
            } elseif ($payStatus === RidePayment::STATUS_OPEN) {
                $paymentError = 'De betaling is nog niet afgerond. Betaal om de rit te activeren, of annuleer de rit.';
            }
        }

        $statusLabel = $labels[$status] ?? $status;
        if ($canRetryPayment && $paymentFailureStatus) {
            $statusLabel = 'Betaling mislukt';
        } elseif ($canRetryPayment && $paymentError) {
            $statusLabel = 'Wacht op betaling';
        }

        return [
            'id' => (int) $ride->id,
            'status' => $status,
            'status_label' => $statusLabel,
            'phase' => $phase,
            'accepted' => $accepted,
            'can_cancel' => $canCancel,
            'needs_unaccepted_decision' => $needsDecision,
            'waiting_for_taxi' => $choseWait,
            'auto_cancel_at' => $autoCancelAt,
            'auto_cancel_label' => $autoCancelLabel,
            'decision_deadline_at' => $decisionDeadlineAt,
            'decision_deadline_label' => $decisionDeadlineLabel,
            'decision_minutes' => $decisionMinutes,
            'refund_business_days' => $refundDays,
            'can_download_invoice' => $canDownloadInvoice,
            'payment_status' => $paymentStatus,
            'payment_paid' => $paymentPaid,
            'can_retry_payment' => $canRetryPayment,
            'payment_failure_status' => $paymentFailureStatus,
            'payment_error' => $paymentError,
            'checkout_url' => $checkoutUrl,
            'cancellation_message' => $status === RideRequest::STATUS_CANCELLED
                ? $cancellation->cancellationMessageForCustomer($ride)
                : null,
            'cancellation_reason' => $status === RideRequest::STATUS_CANCELLED
                ? (($cancellation->cancellationPayload($ride)['reason'] ?? null) ?: null)
                : null,
            'pickup_address' => (string) ($ride->pickup_address ?? ''),
            'dropoff_address' => (string) ($ride->dropoff_address ?? ''),
            'pickup_lat' => $ride->pickup_lat !== null ? (float) $ride->pickup_lat : null,
            'pickup_lng' => $ride->pickup_lng !== null ? (float) $ride->pickup_lng : null,
            'dropoff_lat' => $ride->dropoff_lat !== null ? (float) $ride->dropoff_lat : null,
            'dropoff_lng' => $ride->dropoff_lng !== null ? (float) $ride->dropoff_lng : null,
            'pickup_at' => \App\Modules\NexaTaxi\Support\ContractTransportTimezone::toDriverIso8601($ride->pickup_at),
            'pickup_at_label' => \App\Modules\NexaTaxi\Support\ContractTransportTimezone::asAmsterdamWall($ride->pickup_at)?->format('d-m-Y H:i'),
            'passengers' => (int) ($ride->passengers ?? 1),
            'quoted_price' => $ride->quoted_price !== null ? (float) $ride->quoted_price : null,
            'company' => $company ? [
                'id' => (int) $company->id,
                'name' => (string) $company->name,
                'phone' => (string) ($company->phone ?? ''),
            ] : null,
            'vehicle' => $vehicle ? [
                'id' => (int) $vehicle->id,
                'name' => (string) ($vehicle->name ?? ''),
                'license_plate' => preg_replace('/\s+/', '', (string) ($vehicle->license_plate ?? '')) ?: null,
                'label' => $vehicle->fleetLabel(),
            ] : null,
            'driver' => $driver ? [
                'id' => (int) $driver->id,
                'name' => trim(($driver->first_name ?? '').' '.($driver->last_name ?? '')) ?: 'Chauffeur',
            ] : null,
            'vehicle_location' => $driverLoc,
            'eta_minutes' => $eta['minutes'] ?? null,
            'eta_label' => $eta['label'] ?? null,
            'distance_to_pickup_km' => $eta['distance_km'] ?? null,
            'poll_interval_ms' => $phase === 'awaiting_payment' ? 1200
                : ($phase === 'searching' ? 2500 : 4000),
        ];
    }

    /**
     * @return array{lat: float, lng: float, updated_at: ?string}|null
     */
    protected function resolveDriverLocation(string $conn, RideRequest $ride, int $driverId): ?array
    {
        if ($driverId <= 0) {
            return null;
        }

        if (TaxiRideTrackSchema::pointsTableExists($conn)) {
            $point = RideGpsPoint::on($conn)
                ->where('ride_request_id', $ride->id)
                ->orderByDesc('recorded_at')
                ->orderByDesc('id')
                ->first();
            if ($point && $point->lat !== null && $point->lng !== null) {
                return [
                    'lat' => (float) $point->lat,
                    'lng' => (float) $point->lng,
                    'updated_at' => $point->recorded_at?->toIso8601String(),
                ];
            }
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return null;
        }

        $availability = DriverAvailability::on($conn)
            ->where('driver_id', $driverId)
            ->first();
        if (! $availability || $availability->lat === null || $availability->lng === null) {
            return null;
        }

        return [
            'lat' => (float) $availability->lat,
            'lng' => (float) $availability->lng,
            'updated_at' => $availability->location_updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{minutes: int, label: string, distance_km: float}
     */
    protected function estimateEtaMinutes(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $km = (float) GeoHelper::calculateDistance($fromLat, $fromLng, $toLat, $toLng);
        $minutes = (int) max(1, (int) ceil(($km / self::ETA_AVG_SPEED_KMH) * 60));
        $label = $minutes === 1
            ? 'ongeveer 1 minuut'
            : 'ongeveer '.$minutes.' minuten';

        return [
            'minutes' => $minutes,
            'label' => $label,
            'distance_km' => round($km, 2),
        ];
    }
}
