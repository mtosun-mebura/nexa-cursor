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
}

