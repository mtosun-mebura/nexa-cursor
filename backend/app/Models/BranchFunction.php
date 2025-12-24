<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchFunction extends Model
{
    use HasFactory;

    protected $table = 'branch_functions';

    protected $fillable = [
        'branch_id',
        'name',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function skills()
    {
        return $this->hasMany(BranchFunctionSkill::class, 'branch_function_id');
    }

    /**
     * Display name: convert underscores to spaces.
     */
    public function getDisplayNameAttribute(): string
    {
        return str_replace('_', ' ', (string) $this->name);
    }
}




