<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPayment extends Model
{
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_MANDATE_VERIFICATION = 'mandate_verification';

    public const TYPE_PRORATION = 'proration';

    protected $fillable = [
        'company_id',
        'platform_invoice_id',
        'type',
        'mollie_payment_id',
        'amount',
        'currency',
        'status',
        'mollie_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'mollie_payload' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }
}
