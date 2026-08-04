<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_number',
        'billing_period',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'invoice_date',
        'due_date',
        'payment_terms_days',
        'paid_at',
        'sent_at',
        'line_items',
        'issuer_details',
        'recipient_details',
        'notes',
        'pdf_path',
        'mollie_payment_id',
        'collection_method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_terms_days' => 'integer',
        'paid_at' => 'datetime',
        'sent_at' => 'datetime',
        'line_items' => 'array',
        'issuer_details' => 'array',
        'recipient_details' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_at !== null;
    }
}
