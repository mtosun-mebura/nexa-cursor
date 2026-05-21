<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RidePayment extends Model
{
    protected $table = 'ride_payments';

    protected $fillable = [
        'ride_request_id',
        'company_id',
        'channel',
        'mollie_payment_id',
        'amount',
        'currency',
        'status',
        'checkout_url',
        'paid_at',
        'mollie_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'mollie_payload' => 'array',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_EXPIRED = 'expired';

    public const CHANNEL_BOOKING = 'booking';

    public const CHANNEL_DRIVER = 'driver';

    public const CHANNEL_CASH = 'cash';

    public function rideRequest(): BelongsTo
    {
        return $this->belongsTo(RideRequest::class, 'ride_request_id');
    }
}
