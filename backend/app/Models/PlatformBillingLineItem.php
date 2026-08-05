<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformBillingLineItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function billingProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            CompanyBillingProfile::class,
            'company_billing_profile_line_item',
            'platform_billing_line_item_id',
            'company_billing_profile_id',
        );
    }

    public function invoiceLineDescription(): string
    {
        $description = trim((string) ($this->description ?? ''));

        return $description !== '' ? $this->name.' — '.$description : $this->name;
    }
}
