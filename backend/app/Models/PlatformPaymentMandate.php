<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPaymentMandate extends Model
{
    protected $fillable = [
        'company_id',
        'mollie_customer_id',
        'mollie_mandate_id',
        'status',
        'verification_mollie_payment_id',
        'signed_at',
        'last_requested_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'last_requested_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->mollie_mandate_id;
    }
}
