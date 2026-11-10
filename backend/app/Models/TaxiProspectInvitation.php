<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiProspectInvitation extends Model
{
    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'taxi_prospect_id',
        'status',
        'channel',
        'prepared_by',
        'prepared_at',
        'sent_at',
        'notes',
    ];

    protected $casts = [
        'prepared_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(TaxiProspect::class, 'taxi_prospect_id');
    }
}
