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
        'has_return_trip',
        'return_pickup_time',
        'return_boarding_delay_minutes',
        'notes',
        'active',
    ];

    protected $casts = [
        'departure_lat' => 'decimal:7',
        'departure_lng' => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
        'destination_arrival_time' => 'string',
        'has_return_trip' => 'boolean',
        'return_pickup_time' => 'string',
        'return_boarding_delay_minutes' => 'integer',
        'active' => 'boolean',
    ];

    public const DEFAULT_RETURN_BOARDING_DELAY_MINUTES = 15;

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
            ->where(function ($q) {
                $q->where('direction', TransportRouteTemplate::DIRECTION_OUTBOUND)
                    ->orWhereNull('direction');
            })
            ->latest('id');
    }

    public function returnRouteTemplate()
    {
        return $this->hasOne(TransportRouteTemplate::class, 'transport_group_id')
            ->where('active', true)
            ->where('direction', TransportRouteTemplate::DIRECTION_RETURN)
            ->latest('id');
    }
}

