<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NexaSuiteBookingInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_number',
        'billing_period',
        'ride_count',
        'rides_subtotal',
        'fee_percent',
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
        'first_reminder_sent_at',
        'second_reminder_sent_at',
        'line_items',
        'issuer_details',
        'recipient_details',
        'notes',
        'pdf_path',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'draft' => 'Concept',
        'sent' => 'Verzonden',
        'paid' => 'Betaald',
        'cancelled' => 'Geannuleerd',
    ];

    protected $casts = [
        'ride_count' => 'integer',
        'rides_subtotal' => 'decimal:2',
        'fee_percent' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_terms_days' => 'integer',
        'paid_at' => 'datetime',
        'sent_at' => 'datetime',
        'first_reminder_sent_at' => 'datetime',
        'second_reminder_sent_at' => 'datetime',
        'line_items' => 'array',
        'issuer_details' => 'array',
        'recipient_details' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(NexaSuiteBookingInvoiceRide::class);
    }

    public static function statusLabelFor(?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        return self::STATUS_LABELS[$status] ?? ucfirst($status);
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public function statusBadgeClass(): string
    {
        if ($this->isPaid()) {
            return 'kt-badge-success';
        }

        return match ($this->status) {
            'sent' => $this->first_reminder_sent_at ? 'kt-badge-destructive' : 'kt-badge-warning',
            'cancelled' => 'kt-badge-secondary',
            default => 'kt-badge-yellow',
        };
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_at !== null;
    }

    public function isOpen(): bool
    {
        return ! $this->isPaid() && $this->status !== 'draft' && $this->status !== 'cancelled';
    }

    public function reminderLabel(): string
    {
        if ($this->second_reminder_sent_at) {
            return '2e aanmaning';
        }
        if ($this->first_reminder_sent_at) {
            return '1e aanmaning';
        }

        return '—';
    }
}
