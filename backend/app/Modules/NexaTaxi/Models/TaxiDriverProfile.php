<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TaxiDriverProfile extends Model
{
    protected $table = 'taxi_driver_profiles';

    protected $primaryKey = 'driver_id';

    public $incrementing = false;

    protected $fillable = [
        'driver_id',
        'company_logo_path',
        'vehicle_photo_path',
        'rating_count',
        'rating_sum',
        'rating_avg',
        'app_blocked_at',
        'app_blocked_reason',
        'app_blocked_by',
        'app_blocked_complaint_id',
    ];

    protected $casts = [
        'rating_count' => 'integer',
        'rating_sum' => 'integer',
        'rating_avg' => 'float',
        'app_blocked_at' => 'datetime',
    ];
}
