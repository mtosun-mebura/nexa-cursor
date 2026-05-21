<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideRequest extends Model
{
    protected $table = 'ride_requests';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'status',
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
        'quoted_price',
        'payment_method',
        'payment_status',
        'final_price',
        'invoice_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_note',
        'quote_expires_at',
        'booking_payload',
        'selected_offer_payload',
    ];

    protected $casts = [
        'pickup_at' => 'datetime',
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

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_NOT_REQUIRED = 'not_required';

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

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RidePayment::class, 'ride_request_id')->orderByDesc('id');
    }

    public function dispatchOffers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RideDispatchOffer::class, 'ride_request_id');
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

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
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

    public function chargeableAmount(): ?float
    {
        $amount = $this->final_price ?? $this->quoted_price;

        return $amount !== null ? (float) $amount : null;
    }
}
