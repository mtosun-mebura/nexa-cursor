<?php

namespace App\Modules\Skillmatching\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id', 'company_id', 'type', 'scheduled_at', 'duration', 'status', 'location', 
        'company_location_id', 'interviewer_name', 'interviewer_email', 'interviewer_user_id', 'user_id', 'notes', 'feedback'
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
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(\App\Models\CompanyLocation::class, 'company_location_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'interviewer_user_id');
    }
}


