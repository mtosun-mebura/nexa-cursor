<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportGroupMember extends Model
{
    protected $table = 'transport_group_members';

    protected $fillable = [
        'transport_group_id',
        'transport_passenger_id',
        'valid_from',
        'valid_until',
        'sort_hint',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'sort_hint' => 'integer',
    ];

    public function passenger()
    {
        return $this->belongsTo(TransportPassenger::class, 'transport_passenger_id');
    }

    public function group()
    {
        return $this->belongsTo(TransportGroup::class, 'transport_group_id');
    }
}

