<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPassengerAbsence extends Model
{
    protected $table = 'transport_passenger_absences';

    protected $fillable = [
        'company_id',
        'transport_passenger_id',
        'absence_date',
        'reason',
        'created_by_user_id',
        'cancelled_at',
    ];

    protected $casts = [
        'absence_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function passenger()
    {
        return $this->belongsTo(TransportPassenger::class, 'transport_passenger_id');
    }

    public function isActive(): bool
    {
        return $this->cancelled_at === null;
    }
}
