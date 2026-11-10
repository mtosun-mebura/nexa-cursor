<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiProspectOptOut extends Model
{
    protected $fillable = [
        'taxi_prospect_id',
        'email',
        'phone',
        'reason',
        'opted_out_at',
    ];

    protected $casts = [
        'opted_out_at' => 'datetime',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(TaxiProspect::class, 'taxi_prospect_id');
    }
}
