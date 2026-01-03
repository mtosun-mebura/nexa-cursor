<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'action',
        'title',
        'description',
        'icon',
        'color',
        'match_id',
        'application_id',
        'interview_id',
        'metadata',
        'user_id',
        'action_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'action_at' => 'datetime',
    ];

    /**
     * Get the candidate that owns this activity
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the vacancy associated with this activity
     */
    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }

    /**
     * Get the match associated with this activity
     */
    public function match()
    {
        return $this->belongsTo(JobMatch::class, 'match_id');
    }

    /**
     * Get the application associated with this activity
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the interview associated with this activity
     */
    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    /**
     * Get the user who performed this activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
