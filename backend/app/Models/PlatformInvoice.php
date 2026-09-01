<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'first_reminder_sent_at',
        'second_reminder_sent_at',
        'blocked_at',
        'block_waived_at',
        'line_items',
        'issuer_details',
        'recipient_details',
        'notes',
        'pdf_path',
        'mollie_payment_id',
        'collection_method',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'draft' => 'Concept',
        'sent' => 'Verzonden',
        'paid' => 'Betaald',
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
        'first_reminder_sent_at' => 'datetime',
        'second_reminder_sent_at' => 'datetime',
        'blocked_at' => 'datetime',
        'block_waived_at' => 'datetime',
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

    public function latestPayment(): HasOne
    {
        return $this->hasOne(PlatformPayment::class)->latestOfMany();
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

    public function mollieStatus(): ?string
    {
        $payment = $this->relationLoaded('latestPayment')
            ? $this->latestPayment
            : $this->latestPayment()->first();

        if (! $payment) {
            return null;
        }

        $payloadStatus = $payment->mollie_payload['status'] ?? null;
        if (is_string($payloadStatus) && $payloadStatus !== '') {
            return $payloadStatus;
        }

        $mapped = trim((string) $payment->status);

        return $mapped !== '' ? $mapped : null;
    }

    public function statusBadgeClass(): string
    {
        if ($this->isPaid()) {
            return 'kt-badge-success';
        }

        return match ($this->status) {
            'sent' => 'kt-badge-warning',
            default => 'kt-badge-yellow',
        };
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_at !== null;
    }
}
