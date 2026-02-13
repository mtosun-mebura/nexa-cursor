<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontendTheme extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'preview_path',
        'is_active',
        'settings',
        'default_blocks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'default_blocks' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getActive(): ?self
    {
        return static::active()->first();
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }
}
