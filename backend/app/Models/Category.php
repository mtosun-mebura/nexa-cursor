<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Category model - uses branches table for backward compatibility.
 * Branches replaced categories in the schema; this alias keeps existing code working.
 */
class Category extends Model
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

    /**
     * Get the vacancies for this category (branch).
     */
    public function vacancies()
    {
        return $this->hasMany(Vacancy::class, 'branch_id');
    }
}
