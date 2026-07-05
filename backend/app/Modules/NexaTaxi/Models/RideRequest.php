<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\Company;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class RideRequest extends Model
{
    protected $table = 'ride_requests';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'status',
        'source',
        'ride_type',
        'transport_contract_id',
        'transport_occurrence_id',
        'transport_passenger_id',
        'pickup_address',
        'dropoff_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'distance_meters',
        'duration_seconds',
        'passengers',
        'pickup_at',
        'return_at',
        'outbound_completed_at',
        'outbound_driver_id',
        'return_started_at',
        'quoted_price',
        'payment_method',
        'payment_status',
        'final_price',
        'invoice_id',
        'customer_name',
        'customer_email',
        'customer_user_id',
        'customer_phone',
        'customer_note',
        'quote_expires_at',
        'booking_payload',
        'selected_offer_payload',
    ];

    protected $casts = [
        'pickup_at' => 'datetime',
        'return_at' => 'datetime',
        'outbound_completed_at' => 'datetime',
        'return_started_at' => 'datetime',
        'quote_expires_at' => 'datetime',
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'dropoff_lat' => 'decimal:7',
        'dropoff_lng' => 'decimal:7',
        'quoted_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'booking_payload' => 'array',
        'selected_offer_payload' => 'array',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_PENDING_DISPATCH = 'pending_dispatch';
    public const STATUS_OFFERED = 'offered';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const PAYMENT_METHOD_BOOKING = 'booking';

    public const PAYMENT_METHOD_DRIVER = 'driver';

    public const PAYMENT_METHOD_CONTRACT = 'contract';

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_NOT_REQUIRED = 'not_required';

    public const RIDE_TYPE_STANDARD = 'standard';

    public const RIDE_TYPE_CONTRACT_GROUP = 'contract_group';

    public const RIDE_TYPE_CONTRACT_INDIVIDUAL = 'contract_individual';

    public const SOURCE_BOOKING = 'booking';

    public const SOURCE_CONTRACT = 'contract';

    public const SOURCE_MANUAL = 'manual';

    public const RETURN_LEG_OUTBOUND = 'outbound';

    public const RETURN_LEG_WAITING = 'waiting';

    public const RETURN_LEG_RETURN = 'return';

    public const INVOICE_BILLING_HEEN = 'heen';

    public const INVOICE_BILLING_TERUG = 'terug';

    public const INVOICE_BILLING_TOTAAL = 'totaal';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Concept',
            self::STATUS_QUOTED => 'Offerte',
            self::STATUS_PENDING_DISPATCH => 'Wacht op chauffeur',
            self::STATUS_OFFERED => 'Aangeboden',
            self::STATUS_ACCEPTED => 'Geaccepteerd',
            self::STATUS_ASSIGNED => 'Toegewezen',
            self::STATUS_COMPLETED => 'Voltooid',
            self::STATUS_CANCELLED => 'Geannuleerd',
            self::STATUS_PENDING_PAYMENT => 'Wacht op betaling',
        ];
    }

    /**
     * @return list<string>
     */
    public static function validStatusValues(): array
    {
        return array_keys(self::statusLabels());
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RidePayment::class, 'ride_request_id')->orderByDesc('id');
    }

    public function dispatchOffers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RideDispatchOffer::class, 'ride_request_id');
    }

    public function rideStops(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RideStop::class, 'ride_request_id')->orderBy('sequence');
    }

    public function notificationLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RideRequestNotificationLog::class, 'ride_request_id')
            ->orderByDesc('created_at');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Ophaalmoment ligt nog binnen het grace-venster voor de chauffeur-wachtrij.
     */
    public function scopeDispatchPickupWithinQueueWindow($query, CarbonInterface $pickupCutoff)
    {
        return $query->where(function ($q) use ($pickupCutoff) {
            $q->whereNull('pickup_at')
                ->orWhere('pickup_at', '>=', $pickupCutoff);
        });
    }

    /**
     * Mag de rit opnieuw naar chauffeurs (dispatch) worden gestuurd?
     */
    public function canRedispatchToDrivers(): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        if ($this->status === self::STATUS_PENDING_PAYMENT
            && $this->payment_status !== self::PAYMENT_STATUS_PAID) {
            return false;
        }

        return (int) ($this->company_id ?? 0) > 0;
    }

    public function isContractRide(): bool
    {
        if ($this->payment_method === self::PAYMENT_METHOD_CONTRACT) {
            return true;
        }

        if ($this->source === self::SOURCE_CONTRACT) {
            return true;
        }

        if ((int) ($this->transport_contract_id ?? 0) > 0) {
            return true;
        }

        return in_array($this->ride_type, [
            self::RIDE_TYPE_CONTRACT_GROUP,
            self::RIDE_TYPE_CONTRACT_INDIVIDUAL,
        ], true);
    }

    /** Rit duur in minuten (afgerond). */
    public function getDurationMinutesAttribute(): ?int
    {
        return $this->duration_seconds !== null ? (int) round($this->duration_seconds / 60) : null;
    }

    /** Afstand in km. */
    public function getDistanceKmAttribute(): ?float
    {
        return $this->distance_meters !== null ? round($this->distance_meters / 1000, 2) : null;
    }

    /**
     * Tussenstop-adressen uit booking_payload (A = ophalen, B… = stops, laatste letter = afzetten).
     *
     * @return list<string>
     */
    public function getStopoverAddressesAttribute(): array
    {
        return $this->resolveStopoverAddresses();
    }

    /**
     * @return list<string>
     */
    public function resolveStopoverAddresses(): array
    {
        $payload = $this->booking_payload;
        if (! is_array($payload)) {
            return [];
        }

        $step = is_array($payload['step_data'] ?? null) ? $payload['step_data'] : [];

        foreach ([
            $payload['stopovers'] ?? null,
            $step['stopovers'] ?? null,
            $payload['route']['stopovers'] ?? null,
        ] as $raw) {
            $parsed = self::normalizeStopoverList($raw);
            if ($parsed !== []) {
                return $parsed;
            }
        }

        foreach ([
            $payload['route_addresses'] ?? null,
            $step['route_addresses'] ?? null,
        ] as $route) {
            $fromRoute = self::stopoversFromRouteAddresses($route);
            if ($fromRoute !== []) {
                return $fromRoute;
            }
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private static function normalizeStopoverList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($s) => is_string($s) ? trim($s) : '',
            $raw
        )));
    }

    /**
     * @return list<string>
     */
    private static function stopoversFromRouteAddresses(mixed $route): array
    {
        if (! is_array($route)) {
            return [];
        }

        $addresses = array_values(array_filter(array_map(
            static fn ($s) => is_string($s) ? trim($s) : '',
            $route
        )));

        if (count($addresses) < 3) {
            return [];
        }

        return array_slice($addresses, 1, -1);
    }

    public function chargeableAmount(): ?float
    {
        if ($this->requiresPerLegDriverPayment()) {
            if ($this->payment_status === self::PAYMENT_STATUS_PAID) {
                return null;
            }

            return $this->legChargeableAmount();
        }

        $amount = $this->final_price ?? $this->quoted_price;

        return $amount !== null ? (float) $amount : null;
    }

    public function requiresPerLegDriverPayment(): bool
    {
        return $this->isReturnTrip()
            && $this->payment_method === self::PAYMENT_METHOD_DRIVER;
    }

    public function returnPriceMultiplier(): float
    {
        $payload = $this->booking_payload;
        if (is_array($payload)) {
            $logic = is_array($payload['logic'] ?? null) ? $payload['logic'] : [];
            if (isset($logic['return_price_multiplier']) && is_numeric($logic['return_price_multiplier'])) {
                return max(1.0, (float) $logic['return_price_multiplier']);
            }
        }

        return 2.0;
    }

    /**
     * Verdeelt het retourtotaal over heen- en terugrit (zelfde logica als boekingsmodule).
     *
     * @return array{outbound: float, return: float}
     */
    public function splitReturnTripLegAmounts(): array
    {
        $total = $this->quoted_price !== null ? (float) $this->quoted_price : null;
        if ($total === null || $total < 0.01) {
            return ['outbound' => 0.0, 'return' => 0.0];
        }

        $outbound = round($total / $this->returnPriceMultiplier(), 2);
        $return = round($total - $outbound, 2);

        return [
            'outbound' => $outbound,
            'return' => $return,
        ];
    }

    public function legChargeableAmount(): ?float
    {
        $total = $this->quoted_price !== null ? (float) $this->quoted_price : null;
        if ($total === null || $total < 0.01) {
            return null;
        }

        if (! $this->isReturnTrip()) {
            return round($total, 2);
        }

        $amounts = $this->splitReturnTripLegAmounts();
        $leg = $this->currentReturnLeg();

        if ($leg === self::RETURN_LEG_OUTBOUND) {
            return $amounts['outbound'];
        }

        if ($leg === self::RETURN_LEG_RETURN || $leg === self::RETURN_LEG_WAITING) {
            return $amounts['return'];
        }

        return $amounts['outbound'];
    }

    public function currentPaymentLegLabel(): ?string
    {
        if (! $this->requiresPerLegDriverPayment()) {
            return null;
        }

        $leg = $this->currentReturnLeg();
        if ($leg === self::RETURN_LEG_OUTBOUND) {
            return 'Heenrit';
        }
        if ($leg === self::RETURN_LEG_RETURN) {
            return 'Retourrit';
        }

        if ($leg === self::RETURN_LEG_WAITING) {
            return 'Terugrit';
        }

        return null;
    }

    public function isReturnTrip(): bool
    {
        $payload = $this->booking_payload;
        if (is_array($payload)) {
            $step = is_array($payload['step_data'] ?? null) ? $payload['step_data'] : [];
            if (! empty($step['return_trip']) || ! empty($payload['return_trip'])) {
                return true;
            }
        }

        return $this->return_at !== null;
    }

    public function hasOutboundCompleted(): bool
    {
        return $this->outbound_completed_at !== null;
    }

    public function hasReturnLegStarted(): bool
    {
        return $this->return_started_at !== null;
    }

    public function currentReturnLeg(): ?string
    {
        if (! $this->isReturnTrip()) {
            return null;
        }

        if (! $this->hasOutboundCompleted()) {
            return self::RETURN_LEG_OUTBOUND;
        }

        if (! $this->hasReturnLegStarted()) {
            return self::RETURN_LEG_WAITING;
        }

        return self::RETURN_LEG_RETURN;
    }

    /**
     * Assigned ride blocks accepting/starting other rides unless waiting for return leg.
     */
    public function blocksDriverFromOtherRides(): bool
    {
        if ($this->status !== self::STATUS_ASSIGNED) {
            return false;
        }

        if ($this->isReturnTrip() && $this->hasOutboundCompleted() && ! $this->hasReturnLegStarted()) {
            return false;
        }

        return true;
    }

    /**
     * @return \Illuminate\Support\Collection<int, RidePayment>
     */
    public function paidDriverPayments(): \Illuminate\Support\Collection
    {
        return $this->payments()
            ->where('status', RidePayment::STATUS_PAID)
            ->whereIn('channel', [
                RidePayment::CHANNEL_DRIVER,
                RidePayment::CHANNEL_CASH,
                RidePayment::CHANNEL_BOOKING,
            ])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();
    }

    public function outboundPaidAmount(): ?float
    {
        if (! $this->requiresPerLegDriverPayment()) {
            return null;
        }

        $first = $this->paidDriverPayments()->first();
        if (! $first) {
            return null;
        }

        return round((float) $first->amount, 2);
    }

    public function returnPaidAmount(): ?float
    {
        if (! $this->requiresPerLegDriverPayment()) {
            return null;
        }

        $payments = $this->paidDriverPayments();
        if ($payments->count() >= 2) {
            return round((float) $payments->get(1)->amount, 2);
        }

        if ($this->payment_status === self::PAYMENT_STATUS_PAID && $this->hasReturnLegStarted()) {
            return $this->splitReturnTripLegAmounts()['return'] ?: null;
        }

        return null;
    }

    /**
     * @return array{outbound: float, return: float, total: float}|null
     */
    public function returnTripLegAmountsPayload(): ?array
    {
        if (! $this->isReturnTrip() || $this->quoted_price === null) {
            return null;
        }

        $amounts = $this->splitReturnTripLegAmounts();

        return [
            'outbound' => $amounts['outbound'],
            'return' => $amounts['return'],
            'total' => round((float) $this->quoted_price, 2),
        ];
    }

    public function invoiceLegLabelForBillingPeriod(?string $billingPeriod): ?string
    {
        return match ($billingPeriod) {
            self::INVOICE_BILLING_HEEN => 'Heenrit',
            self::INVOICE_BILLING_TERUG => 'Terugrit',
            self::INVOICE_BILLING_TOTAAL => 'Totaalfactuur',
            default => null,
        };
    }

    public function resolveReturnAt(): ?CarbonInterface
    {
        if ($this->return_at) {
            return $this->return_at->copy();
        }

        $payload = $this->booking_payload;
        if (! is_array($payload)) {
            return null;
        }

        $step = is_array($payload['step_data'] ?? null) ? $payload['step_data'] : [];
        $raw = $step['return_at'] ?? $payload['return_at'] ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public function effectivePickupAt(): ?CarbonInterface
    {
        if ($this->isReturnTrip() && $this->hasOutboundCompleted()) {
            return $this->resolveReturnAt() ?? $this->pickup_at?->copy();
        }

        return $this->pickup_at?->copy();
    }

    public function driverLegPickupAddress(): string
    {
        if ($this->isReturnTrip() && $this->hasOutboundCompleted()) {
            return trim((string) $this->dropoff_address);
        }

        return trim((string) $this->pickup_address);
    }

    public function driverLegDropoffAddress(): string
    {
        if ($this->isReturnTrip() && $this->hasOutboundCompleted()) {
            return trim((string) $this->pickup_address);
        }

        return trim((string) $this->dropoff_address);
    }

    public function canReleaseReturnLeg(?int $driverId = null): bool
    {
        if (! $this->isReturnTrip()
            || ! $this->hasOutboundCompleted()
            || $this->hasReturnLegStarted()
            || $this->status !== self::STATUS_ASSIGNED) {
            return false;
        }

        if ($driverId !== null && (int) ($this->driver_id ?? 0) !== $driverId) {
            return false;
        }

        return (int) ($this->driver_id ?? 0) > 0;
    }
}
