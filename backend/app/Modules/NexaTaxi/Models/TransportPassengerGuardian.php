<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPassengerGuardian extends Model
{
    protected $table = 'transport_passenger_guardians';

    protected $fillable = [
        'company_id',
        'transport_passenger_id',
        'user_id',
    ];

    public function passenger()
    {
        return $this->belongsTo(TransportPassenger::class, 'transport_passenger_id');
    }
}
