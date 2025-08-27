<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id', 'company_id', 'type', 'scheduled_at', 'duration', 'status', 'location', 
        'interviewer_name', 'interviewer_email', 'notes', 'feedback'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration' => 'integer',
    ];

    public function match()
    {
        return $this->belongsTo(JobMatch::class, 'match_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}


