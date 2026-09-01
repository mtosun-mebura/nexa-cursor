<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscriptionChange extends Model
{
    public const TYPE_UPGRADE = 'upgrade';

    public const TYPE_DOWNGRADE = 'downgrade';

    public const TYPE_CANCEL = 'cancel';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'company_id',
        'company_billing_profile_id',
        'change_type',
        'status',
        'from_package_key',
        'to_package_key',
        'from_monthly_amount',
        'to_monthly_amount',
        'effective_on',
        'requested_at',
        'applied_at',
    ];

    protected $casts = [
        'from_monthly_amount' => 'decimal:2',
        'to_monthly_amount' => 'decimal:2',
        'effective_on' => 'date',
        'requested_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyBillingProfile::class, 'company_billing_profile_id');
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }
}
