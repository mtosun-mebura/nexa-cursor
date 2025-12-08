<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'department', 'industry', 'kvk_number',
        'street', 'house_number', 'house_number_extension', 'postal_code', 'city', 'country',
        'website', 'email', 'phone',
        'contact_first_name', 'contact_middle_name', 'contact_last_name', 'contact_email',
        'is_active', 'is_intermediary', 'is_main', 'logo_path', 'logo_blob', 'logo_mime_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_intermediary' => 'boolean',
        'is_main' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Generate a secure token to view a candidate's photo
     */
    public function getCandidatePhotoToken($userId): string
    {
        $user = User::find($userId);
        if (!$user) {
            return '';
        }
        
        return $user->getCompanyPhotoToken($this->id);
    }

    /**
     * Get the vacancies for the company.
     */
    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * Get the email templates for the company.
     */
    public function emailTemplates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Get the locations for the company.
     */
    public function locations()
    {
        return $this->hasMany(CompanyLocation::class);
    }

    /**
     * Get the main location (hoofdkantoor) for the company.
     */
    public function mainLocation()
    {
        return $this->hasOne(CompanyLocation::class)->where('is_main', true);
    }
}


