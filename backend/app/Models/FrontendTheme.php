<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property-read \App\Models\Module|null $activeModule */

class FrontendTheme extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'preview_path',
        'is_active',
        'active_module_id',
        'settings',
        'default_blocks',
    ];

    public function activeModule()
    {
        return $this->belongsTo(Module::class, 'active_module_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'default_blocks' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Eerste gepubliceerde (beschikbare) thema — fallback voor centraal domein zonder tenant.
     */
    public static function getActive(): ?self
    {
        return static::active()->orderBy('id')->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderBy('slug')->get();
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }
}
