<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPassenger extends Model
{
    protected $table = 'transport_passengers';

    protected $fillable = [
        'company_id',
        'transport_contract_id',
        'first_name',
        'last_name',
        'phone',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'notes',
        'active',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'active' => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function groupMemberships()
    {
        return $this->hasMany(TransportGroupMember::class, 'transport_passenger_id');
    }
}

