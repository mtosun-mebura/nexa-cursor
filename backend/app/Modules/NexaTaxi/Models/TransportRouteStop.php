<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRouteStop extends Model
{
    protected $table = 'transport_route_stops';

    protected $fillable = [
        'transport_route_template_id',
        'sequence',
        'stop_type',
        'transport_passenger_id',
        'address',
        'lat',
        'lng',
        'planned_at_time',
    ];

    protected $casts = [
        'transport_passenger_id' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'planned_at_time' => 'string',
        'sequence' => 'integer',
    ];

    public const STOP_TYPE_PICKUP = 'pickup';

    public const STOP_TYPE_DESTINATION = 'destination';

    public function template()
    {
        return $this->belongsTo(TransportRouteTemplate::class, 'transport_route_template_id');
    }

    public function passenger()
    {
        return $this->belongsTo(TransportPassenger::class, 'transport_passenger_id');
    }
}

