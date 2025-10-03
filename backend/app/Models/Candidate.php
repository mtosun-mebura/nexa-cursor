<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\GeoHelper;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'address',
        'city',
        'postal_code',
        'country',
        'cv_path',
        'cover_letter',
        'linkedin_url',
        'website_url',
        'experience_years',
        'education_level',
        'current_position',
        'desired_position',
        'salary_expectation',
        'availability',
        'preferred_work_type',
        'preferred_location',
        'skills',
        'languages',
        'status',
        'notes',
        'source',
        'consent_gdpr',
        'consent_marketing',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'experience_years' => 'integer',
        'salary_expectation' => 'decimal:2',
        'consent_gdpr' => 'boolean',
        'consent_marketing' => 'boolean',
        'skills' => 'array',
        'languages' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($candidate) {
            $candidate->reference_number = 'CAN-' . strtoupper(Str::random(8));
            
            // Automatisch coordinaten instellen op basis van stad
            if (!empty($candidate->city) && (empty($candidate->latitude) || empty($candidate->longitude))) {
                $coordinates = GeoHelper::getCityCoordinates($candidate->city);
                if ($coordinates) {
                    $candidate->latitude = $coordinates['latitude'];
                    $candidate->longitude = $coordinates['longitude'];
                }
            }
        });

        static::updating(function ($candidate) {
            // Automatisch coordinaten bijwerken als stad verandert
            if ($candidate->isDirty('city') && !empty($candidate->city)) {
                $coordinates = GeoHelper::getCityCoordinates($candidate->city);
                if ($coordinates) {
                    $candidate->latitude = $coordinates['latitude'];
                    $candidate->longitude = $coordinates['longitude'];
                }
            }
        });
    }

    /**
     * Get the candidate's full name
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the candidate's age
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Scope for active candidates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending candidates
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for rejected candidates
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for hired candidates
     */
    public function scopeHired($query)
    {
        return $query->where('status', 'hired');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            'hired' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get experience level
     */
    public function getExperienceLevelAttribute()
    {
        if ($this->experience_years < 1) {
            return 'Junior';
        } elseif ($this->experience_years < 3) {
            return 'Medior';
        } elseif ($this->experience_years < 7) {
            return 'Senior';
        } else {
            return 'Expert';
        }
    }

    /**
     * Get education level display
     */
    public function getEducationLevelDisplayAttribute()
    {
        return match($this->education_level) {
            'high_school' => 'Middelbare School',
            'vocational' => 'MBO',
            'bachelor' => 'HBO/Bachelor',
            'master' => 'WO/Master',
            'phd' => 'PhD/Doctoraat',
            default => 'Onbekend'
        };
    }

    /**
     * Get availability display
     */
    public function getAvailabilityDisplayAttribute()
    {
        return match($this->availability) {
            'immediate' => 'Direct beschikbaar',
            '2_weeks' => 'Binnen 2 weken',
            '1_month' => 'Binnen 1 maand',
            '3_months' => 'Binnen 3 maanden',
            'custom' => 'Op afspraak',
            default => 'Onbekend'
        };
    }

    /**
     * Get work type display
     */
    public function getWorkTypeDisplayAttribute()
    {
        return match($this->preferred_work_type) {
            'full_time' => 'Volledig',
            'part_time' => 'Deeltijd',
            'freelance' => 'Freelance',
            'contract' => 'Contract',
            'hybrid' => 'Hybride',
            'remote' => 'Remote',
            default => 'Flexibel'
        };
    }
}









