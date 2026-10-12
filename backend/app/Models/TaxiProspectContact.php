<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiProspectContact extends Model
{
    protected $fillable = [
        'taxi_prospect_id',
        'channel',
        'direction',
        'subject',
        'body',
        'created_by',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(TaxiProspect::class, 'taxi_prospect_id');
    }
}
