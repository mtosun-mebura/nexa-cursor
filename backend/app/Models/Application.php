<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relatie naar de kandidaat
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Relatie naar de vacature
     */
    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}









