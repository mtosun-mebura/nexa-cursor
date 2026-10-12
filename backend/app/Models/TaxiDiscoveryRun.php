<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiDiscoveryRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source_type',
        'status',
        'started_at',
        'finished_at',
        'discovered_count',
        'updated_count',
        'duplicate_count',
        'failed_count',
        'error_summary',
        'meta',
        'started_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }
}
