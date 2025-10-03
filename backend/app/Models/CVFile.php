<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CVFile extends Model
{
    protected $table = 'cv_files';
    
    protected $fillable = [
        'user_id',
        'original_name',
        'file_path',
        'file_type',
        'file_size'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
