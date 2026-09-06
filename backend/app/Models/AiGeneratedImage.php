<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneratedImage extends Model
{
    protected $fillable = [
        'website_media_uuid',
        'prompt',
        'created_by',
    ];

    public function media()
    {
        return $this->belongsTo(WebsiteMedia::class, 'website_media_uuid', 'uuid');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
