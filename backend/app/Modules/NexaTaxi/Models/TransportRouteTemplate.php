<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRouteTemplate extends Model
{
    protected $table = 'transport_route_templates';

    protected $fillable = [
        'company_id',
        'transport_group_id',
        'label',
        'recurrence_days',
        'driver_start_mode',
        'driver_start_address',
        'driver_start_lat',
        'driver_start_lng',
        'buffer_seconds',
        'route_locked',
        'active',
    ];

    protected $casts = [
        'recurrence_days' => 'array',
        'driver_start_lat' => 'decimal:7',
        'driver_start_lng' => 'decimal:7',
        'buffer_seconds' => 'integer',
        'route_locked' => 'boolean',
        'active' => 'boolean',
    ];

    public const DRIVER_START_DEPOT = 'depot';

    public const DRIVER_START_FIRST_STOP = 'first_stop';

    public const ASSIGNABLE_TYPE = 'route_template';

    public function group()
    {
        return $this->belongsTo(TransportGroup::class, 'transport_group_id');
    }

    public function stops()
    {
        return $this->hasMany(TransportRouteStop::class, 'transport_route_template_id')->orderBy('sequence');
    }

    public function assignment()
    {
        return $this->hasOne(TransportAssignment::class, 'assignable_id')
            ->where('assignable_type', self::ASSIGNABLE_TYPE)
            ->where('active', true);
    }

    /** @return list<int> */
    public static function defaultRecurrenceDays(): array
    {
        return [1, 2, 3, 4, 5];
    }

    /** @return array<int, string> */
    public static function weekdayLabels(): array
    {
        return [
            1 => 'Ma',
            2 => 'Di',
            3 => 'Wo',
            4 => 'Do',
            5 => 'Vr',
            6 => 'Za',
            7 => 'Zo',
        ];
    }
}

