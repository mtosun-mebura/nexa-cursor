<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $fillable = [
        'name', 'slug', 'description', 'color', 'icon', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($branch) {
            if (empty($branch->slug)) {
                $branch->slug = Str::slug($branch->name);
            }
        });

        static::updating(function ($branch) {
            if ($branch->isDirty('name') && empty($branch->slug)) {
                $branch->slug = Str::slug($branch->name);
            }
        });
    }

    /**
     * Get the vacancies for this branch.
     */
    public function vacancies()
    {
        return $this->hasMany(Vacancy::class, 'branch_id');
    }

    /**
     * Functions/job titles that belong to this branch.
     */
    public function functions()
    {
        return $this->hasMany(BranchFunction::class, 'branch_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}


