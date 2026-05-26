<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyLocation extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'street',
        'house_number',
        'house_number_extension',
        'postal_code',
        'city',
        'country',
        'phone',
        'email',
        'is_main',
        'is_active',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company this location belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
