<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportOccurrence extends Model
{
    protected $table = 'transport_occurrences';

    protected $fillable = [
        'company_id',
        'transport_contract_id',
        'occurrence_type',
        'transport_route_template_id',
        'transport_individual_booking_id',
        'scheduled_date',
        'scheduled_at',
        'status',
        'ride_request_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_at' => 'datetime',
    ];

    public function rideRequest()
    {
        return $this->belongsTo(RideRequest::class, 'ride_request_id');
    }

    public function contract()
    {
        return $this->belongsTo(TransportContract::class, 'transport_contract_id');
    }

    public function routeTemplate()
    {
        return $this->belongsTo(TransportRouteTemplate::class, 'transport_route_template_id');
    }

    public function individualBooking()
    {
        return $this->belongsTo(TransportIndividualBooking::class, 'transport_individual_booking_id');
    }
}

