<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TaxiRideRating extends Model
{
    protected $table = 'taxi_ride_ratings';

    protected $fillable = [
        'ride_request_id',
        'driver_id',
        'stars',
        'comment',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];
}
