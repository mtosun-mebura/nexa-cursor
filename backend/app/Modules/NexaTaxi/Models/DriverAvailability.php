<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAvailability extends Model
{
    protected $table = 'driver_availability';

    protected $primaryKey = 'driver_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'driver_id',
        'company_id',
        'is_online',
        'lat',
        'lng',
        'location_updated_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'location_updated_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
