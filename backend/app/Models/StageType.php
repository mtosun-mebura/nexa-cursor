<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageType extends Model
{
    protected $fillable = [
        'key',
        'default_label',
        'category',
        'typical_duration_minutes',
        'can_schedule',
        'can_collect_feedback',
        'required_artifacts',
        'optional_artifacts',
        'outcomes',
        'allowed_next_stage_types',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'can_schedule' => 'boolean',
        'can_collect_feedback' => 'boolean',
        'is_active' => 'boolean',
        'typical_duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'required_artifacts' => 'array',
        'optional_artifacts' => 'array',
        'outcomes' => 'array',
        'allowed_next_stage_types' => 'array',
    ];
}
