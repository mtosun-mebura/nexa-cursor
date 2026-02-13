<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property-read FrontendTheme|null $theme */

class Module extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'version',
        'description',
        'icon',
        'installed',
        'active',
        'configuration',
        'frontend_theme_id',
    ];

    protected $casts = [
        'installed' => 'boolean',
        'active' => 'boolean',
        'configuration' => 'array',
    ];

    public function theme()
    {
        return $this->belongsTo(FrontendTheme::class, 'frontend_theme_id');
    }
}
