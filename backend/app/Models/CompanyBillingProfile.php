<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyBillingProfile extends Model
{
    public const MODE_PACKAGE = 'package';

    public const MODE_CUSTOM = 'custom';

    public const MODE_FREE = 'free';

    protected $fillable = [
        'company_id',
        'billing_mode',
        'platform_billing_package_id',
        'custom_monthly_amount',
        'discount_percent',
        'billing_email',
        'billing_contact_name',
        'auto_collect_enabled',
        'subscription_start_date',
        'subscription_end_date',
        'mollie_subscription_id',
        'mollie_subscription_status',
        'mollie_subscription_synced_at',
        'notes',
        'extra_lines_one_time',
        'extra_lines_applied_at',
        'extra_lines_discount_percent',
    ];

    protected $casts = [
        'custom_monthly_amount' => 'decimal:2',
        'discount_percent' => 'integer',
        'auto_collect_enabled' => 'boolean',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'mollie_subscription_synced_at' => 'datetime',
        'extra_lines_one_time' => 'boolean',
        'extra_lines_applied_at' => 'datetime',
        'extra_lines_discount_percent' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PlatformBillingPackage::class, 'platform_billing_package_id');
    }

    public function lineItems(): BelongsToMany
    {
        return $this->belongsToMany(
            PlatformBillingLineItem::class,
            'company_billing_profile_line_item',
            'company_billing_profile_id',
            'platform_billing_line_item_id',
        )->orderBy('platform_billing_line_items.sort_order')
            ->orderBy('platform_billing_line_items.name');
    }

    public function shouldIncludeExtraLines(): bool
    {
        $items = $this->relationLoaded('lineItems')
            ? $this->lineItems
            : $this->lineItems()->where('is_active', true)->get();

        if ($items->isEmpty()) {
            return false;
        }

        if (! $this->extra_lines_one_time) {
            return true;
        }

        return $this->extra_lines_applied_at === null;
    }

    public function subscriptionLineLabel(): string
    {
        return match ($this->billing_mode) {
            self::MODE_FREE => 'Gratis gebruik',
            self::MODE_CUSTOM => 'Maandabonnement (maatwerk)',
            default => $this->package?->name ?? 'Maandabonnement',
        };
    }

    public function discountPercent(): int
    {
        return max(0, min(100, (int) ($this->discount_percent ?? 0)));
    }

    public function subscriptionBaseAmount(): float
    {
        if ($this->billing_mode === self::MODE_FREE) {
            return 0.0;
        }

        if ($this->billing_mode === self::MODE_CUSTOM) {
            return round(max(0, (float) ($this->custom_monthly_amount ?? 0)), 2);
        }

        if ($this->billing_mode === self::MODE_PACKAGE && $this->package) {
            return round(max(0, (float) $this->package->monthly_amount), 2);
        }

        return 0.0;
    }

    public function subscriptionDiscountAmount(): float
    {
        return round(max(0, $this->subscriptionBaseAmount() - $this->resolveMonthlyAmount()), 2);
    }

    public function extraLinesDiscountPercent(): int
    {
        return max(0, min(100, (int) ($this->extra_lines_discount_percent ?? 0)));
    }

    public function extraLinesGrossAmount(): float
    {
        if (! $this->shouldIncludeExtraLines()) {
            return 0.0;
        }

        $items = $this->relationLoaded('lineItems')
            ? $this->lineItems
            : $this->lineItems()->where('is_active', true)->get();

        $total = 0.0;
        foreach ($items as $item) {
            if (! $item->is_active) {
                continue;
            }
            $total += (float) $item->unit_price;
        }

        return round(max(0, $total), 2);
    }

    public function extraLinesDiscountAmount(): float
    {
        $percent = $this->extraLinesDiscountPercent();
        if ($percent <= 0) {
            return 0.0;
        }

        return round($this->extraLinesGrossAmount() * ($percent / 100), 2);
    }

    public function resolveMonthlyAmount(): float
    {
        $base = $this->subscriptionBaseAmount();
        $discount = $this->discountPercent();

        return round(max(0, $base * (1 - ($discount / 100))), 2);
    }

    public function billingEmailForCompany(): ?string
    {
        $email = trim((string) ($this->billing_email ?? ''));

        return $email !== '' ? $email : ($this->company?->email ?: null);
    }

    public function subscriptionStartDate(): ?Carbon
    {
        return $this->subscription_start_date
            ? Carbon::parse($this->subscription_start_date)->startOfDay()
            : null;
    }

    public function subscriptionEndDate(): ?Carbon
    {
        return $this->subscription_end_date
            ? Carbon::parse($this->subscription_end_date)->startOfDay()
            : null;
    }

    public function hasActiveMollieSubscription(): bool
    {
        return $this->mollie_subscription_id
            && in_array($this->mollie_subscription_status, ['pending', 'active'], true);
    }
}
