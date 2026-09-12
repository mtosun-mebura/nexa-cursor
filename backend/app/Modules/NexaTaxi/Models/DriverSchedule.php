<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverSchedule extends Model
{
    protected $table = 'driver_schedules';

    protected $fillable = [
        'company_id',
        'driver_id',
        'vehicle_id',
        'starts_at',
        'ends_at',
        'series_id',
        'weekdays',
        'repeat_weekly',
        'repeat_until',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'repeat_weekly' => 'boolean',
        'repeat_until' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
