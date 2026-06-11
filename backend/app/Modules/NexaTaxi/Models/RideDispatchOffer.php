<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\User;
use Carbon\CarbonInterface;
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
     * Rit hoort nog in de dispatch-wachtrij (pickup ontbreekt of ligt na het cutoff-moment).
     */
    public function scopeRidePickupWithinQueueWindow($query, CarbonInterface $pickupCutoff)
    {
        return $query->whereHas('rideRequest', function ($q) use ($pickupCutoff) {
            $q->where(function ($q2) use ($pickupCutoff) {
                $q2->whereNull('pickup_at')
                    ->orWhere('pickup_at', '>=', $pickupCutoff);
            });
        });
    }

    /**
     * Inbox: openstaande aanbiedingen voor deze chauffeur binnen het pickup-grace-venster.
     */
    public function scopeInboxForDriver($query, int $driverId, CarbonInterface $pickupCutoff)
    {
        return $query
            ->where('driver_id', $driverId)
            ->where('status', self::STATUS_PENDING)
            ->whereHas('rideRequest', fn ($q) => $q->whereNull('driver_id'))
            ->ridePickupWithinQueueWindow($pickupCutoff);
    }

    /**
     * Door deze chauffeur afgewezen ritten die nog geen chauffeur hebben.
     */
    public function scopeDeclinedForDriver($query, int $driverId, CarbonInterface $pickupCutoff)
    {
        return $query
            ->where('driver_id', $driverId)
            ->where('status', self::STATUS_DECLINED)
            ->whereHas('rideRequest', fn ($q) => $q->whereNull('driver_id'))
            ->ridePickupWithinQueueWindow($pickupCutoff);
    }
}
