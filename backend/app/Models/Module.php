<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'installed' => 'boolean',
        'active' => 'boolean',
        'configuration' => 'array',
    ];
}
