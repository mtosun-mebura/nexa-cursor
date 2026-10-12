<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiProspectSource extends Model
{
    public const TYPE_KNV = 'knv';

    public const TYPE_KNV_CSV = 'knv_csv';

    public const TYPE_KVK = 'kvk';

    public const TYPE_GOOGLE_PLACES = 'google_places';

    public const TYPE_WEBSITE = 'website';

    public const TYPE_TTO = 'tto';

    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'taxi_prospect_id',
        'source_type',
        'source_url',
        'external_reference',
        'discovered_at',
        'last_checked_at',
        'raw_metadata',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'raw_metadata' => 'array',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(TaxiProspect::class, 'taxi_prospect_id');
    }
}
