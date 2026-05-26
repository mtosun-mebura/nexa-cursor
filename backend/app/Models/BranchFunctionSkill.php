<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchFunctionSkill extends Model
{
    use HasFactory;

    protected $table = 'branch_function_skills';

    protected $fillable = [
        'branch_function_id',
        'name',
    ];

    public function branchFunction()
    {
        return $this->belongsTo(BranchFunction::class, 'branch_function_id');
    }

    /**
     * Display name: convert underscores to spaces.
     */
    public function getDisplayNameAttribute(): string
    {
        return str_replace('_', ' ', (string) $this->name);
    }
}
