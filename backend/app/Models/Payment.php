<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'company_id',
        'job_match_id',
        'invoice_id',
        'amount',
        'currency',
        'status',
        'payment_provider',
        'payment_provider_id',
        'paid_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function jobMatch(): BelongsTo
    {
        return $this->belongsTo(JobMatch::class, 'job_match_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Update related invoice if exists
        if ($this->invoice) {
            $this->invoice->update([
                'status' => 'paid',
                'paid_date' => now(),
            ]);
        }
    }
}
