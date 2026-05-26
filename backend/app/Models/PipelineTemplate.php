<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'key',
        'version',
        'is_default',
        'is_active',
        'stages',
        'terminal_stages',
        'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'version' => 'integer',
        'stages' => 'array',
        'terminal_stages' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stageInstances()
    {
        return $this->hasMany(StageInstance::class);
    }
}
