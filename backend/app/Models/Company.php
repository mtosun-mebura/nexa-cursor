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
        'is_active',
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
     * Get the vacancies for the company.
     */
    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }
}


