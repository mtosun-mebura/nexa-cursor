<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideDispatchOffer extends Model
{
    protected $table = 'ride_dispatch_offers';

    protected $fillable = [
        'ride_request_id',
        'company_id',
        'driver_id',
        'status',
        'wave',
        'offered_at',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'offered_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'wave' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUPERSEDED = 'superseded';

    public function rideRequest(): BelongsTo
    {
        return $this->belongsTo(RideRequest::class, 'ride_request_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function scopePendingForDriver($query, int $driverId)
    {
        return $query
            ->where('driver_id', $driverId)
            ->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    /**
     * Inbox: alle openstaande aanbiedingen voor deze chauffeur (ook net verlopen — UI toont wacht-status).
     */
    public function scopeInboxForDriver($query, int $driverId)
    {
        return $query
            ->where('driver_id', $driverId)
            ->where('status', self::STATUS_PENDING)
            ->whereHas('rideRequest', function ($q) {
                $q->whereNull('driver_id');
            });
    }
}
