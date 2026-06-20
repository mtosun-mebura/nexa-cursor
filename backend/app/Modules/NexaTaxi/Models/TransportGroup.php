<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportGroup extends Model
{
    protected $table = 'transport_groups';

    protected $fillable = [
        'company_id',
        'transport_contract_id',
        'name',
        'departure_address',
        'departure_lat',
        'departure_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'destination_arrival_time',
        'notes',
        'active',
    ];

    protected $casts = [
        'departure_lat' => 'decimal:7',
        'departure_lng' => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
        'destination_arrival_time' => 'string',
        'active' => 'boolean',
    ];

    public function members()
    {
        return $this->hasMany(TransportGroupMember::class, 'transport_group_id');
    }

    public function routeTemplates()
    {
        return $this->hasMany(TransportRouteTemplate::class, 'transport_group_id');
    }

    public function routeTemplate()
    {
        return $this->hasOne(TransportRouteTemplate::class, 'transport_group_id')
            ->where('active', true)
            ->latest('id');
    }
}

