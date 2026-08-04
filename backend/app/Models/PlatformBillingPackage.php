<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformBillingPackage extends Model
{
    protected $fillable = [
        'name',
        'description',
        'monthly_amount',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function billingProfiles(): HasMany
    {
        return $this->hasMany(CompanyBillingProfile::class, 'platform_billing_package_id');
    }
}
