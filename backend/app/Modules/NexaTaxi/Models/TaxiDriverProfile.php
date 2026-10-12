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
    ];

    protected $casts = [
        'rating_count' => 'integer',
        'rating_sum' => 'integer',
        'rating_avg' => 'float',
    ];
}
