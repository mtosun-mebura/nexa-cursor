<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportIndividualBooking extends Model
{
    protected $table = 'transport_individual_bookings';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'transport_contract_id',
        'transport_passenger_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'pickup_at',
        'driver_id',
        'vehicle_id',
        'price_override',
        'status',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'dropoff_lat' => 'decimal:7',
        'dropoff_lng' => 'decimal:7',
        'pickup_at' => 'datetime',
        'price_override' => 'decimal:2',
        'status' => 'string',
    ];

    public function passenger()
    {
        return $this->belongsTo(TransportPassenger::class, 'transport_passenger_id');
    }

    public function contract()
    {
        return $this->belongsTo(TransportContract::class, 'transport_contract_id');
    }

    public function occurrence()
    {
        return $this->hasOne(TransportOccurrence::class, 'transport_individual_booking_id');
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PLANNED => 'Gepland',
            self::STATUS_CANCELLED => 'Geannuleerd',
        ];
    }
}
