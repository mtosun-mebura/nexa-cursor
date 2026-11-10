<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiProspectServiceArea extends Model
{
    protected $fillable = [
        'taxi_prospect_id',
        'municipality_code',
        'municipality_name',
        'service_type',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(TaxiProspect::class, 'taxi_prospect_id');
    }
}
