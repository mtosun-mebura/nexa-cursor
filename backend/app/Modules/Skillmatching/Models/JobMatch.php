<?php

namespace App\Modules\Skillmatching\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'candidate_id', 'vacancy_id', 'match_score', 'status', 'ai_recommendation', 'application_date', 'notes', 'ai_analysis'
    ];

    protected $casts = [
        'application_date' => 'date',
        'match_score' => 'decimal:2',
    ];

    public function candidate()
    {
        return $this->belongsTo(\App\Models\Candidate::class);
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'match_id');
    }
}
