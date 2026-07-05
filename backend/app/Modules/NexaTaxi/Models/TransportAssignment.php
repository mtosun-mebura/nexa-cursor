<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportAssignment extends Model
{
    protected $table = 'transport_assignments';

    protected $fillable = [
        'company_id',
        'assignable_type',
        'assignable_id',
        'driver_id',
        'vehicle_id',
        'valid_from',
        'valid_until',
        'active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'active' => 'boolean',
    ];

    public const ASSIGNABLE_ROUTE_TEMPLATE = 'route_template';

    public const ASSIGNABLE_INDIVIDUAL_BOOKING = 'individual_booking';

    public function driver()
    {
        return $this->belongsTo(\App\Models\User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}

