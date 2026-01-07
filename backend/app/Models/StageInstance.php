<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageInstance extends Model
{
    protected $fillable = [
        'application_id',
        'match_id',
        'pipeline_template_id',
        'stage_type_key',
        'label',
        'sequence',
        'status',
        'outcome',
        'scheduled_at',
        'started_at',
        'completed_at',
        'artifacts',
        'notes',
        'created_by',
        'updated_by',
        'type',
        'duration',
        'location_type',
        'company_location_id',
        'location',
        'scheduled_time',
        'interviewer_id',
        'interviewer_name',
        'interviewer_email',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'artifacts' => 'array',
        'duration' => 'integer',
        'company_location_id' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(JobMatch::class, 'match_id');
    }

    public function pipelineTemplate(): BelongsTo
    {
        return $this->belongsTo(PipelineTemplate::class);
    }

    public function stageType()
    {
        return StageType::where('key', $this->stage_type_key)->first();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
