<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class RideStop extends Model
{
    protected $table = 'ride_stops';

    protected $fillable = [
        'ride_request_id',
        'sequence',
        'stop_type',
        'transport_passenger_id',
        'passenger_name',
        'address',
        'lat',
        'lng',
        'planned_at',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'transport_passenger_id' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'planned_at' => 'datetime',
        'completed_at' => 'datetime',
        'sequence' => 'integer',
    ];

    public const STATUS_PLANNED = 'planned';

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_COMPLETED = 'completed';

    public const STOP_TYPE_PICKUP = 'pickup';

    public const STOP_TYPE_DESTINATION = 'destination';

    public function ride()
    {
        return $this->belongsTo(RideRequest::class, 'ride_request_id');
    }
}

