<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'company_id',
        'job_match_id',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'invoice_date',
        'due_date',
        'paid_date',
        'is_partial',
        'parent_invoice_number',
        'partial_number',
        'line_items',
        'company_details',
        'notes',
        'pdf_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'is_partial' => 'boolean',
        'line_items' => 'array',
        'company_details' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function jobMatch(): BelongsTo
    {
        return $this->belongsTo(JobMatch::class, 'job_match_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date < now() && !$this->paid_date;
    }

    public function getPaymentLink(string $method = 'tikkie'): string
    {
        $baseUrl = config('app.url');
        
        switch ($method) {
            case 'tikkie':
                // Generate Tikkie link (would integrate with Tikkie API in production)
                return "https://tikkie.me/pay/{$this->invoice_number}";
            case 'qr':
                // Generate QR code payment link
                return "{$baseUrl}/pay/qr/{$this->invoice_number}";
            case 'bank':
                // Bank transfer details
                return "{$baseUrl}/pay/bank/{$this->invoice_number}";
            default:
                return "{$baseUrl}/pay/{$this->invoice_number}";
        }
    }
}
