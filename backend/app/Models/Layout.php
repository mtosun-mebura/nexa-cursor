<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'version', 'description', 'html_content', 'css_content',
        'header_color', 'footer_color', 'logo_url', 'footer_text', 'metadata', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the layouts for a specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get only active layouts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the latest version of a layout
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('version', 'desc');
    }
}
