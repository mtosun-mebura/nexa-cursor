<?php

namespace App\Models;

use App\Services\NexaPricingService;
use App\Support\TenantPackageAddon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'agreed_monthly_amount',
        'discount_percent',
        'billing_email',
        'billing_contact_name',
        'auto_collect_enabled',
        'overdue_block_mode',
        'access_restriction',
        'access_restriction_source',
        'access_restricted_at',
        'access_restricted_invoice_id',
        'subscription_start_date',
        'subscription_end_date',
        'trial_started_at',
        'trial_ends_at',
        'trial_notice_sent_at',
        'pending_change_type',
        'pending_package_key',
        'pending_change_effective_on',
        'pending_proration_amount',
        'pending_proration_label',
        'pending_proration_applied_at',
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
        'agreed_monthly_amount' => 'decimal:2',
        'discount_percent' => 'integer',
        'auto_collect_enabled' => 'boolean',
        'access_restricted_at' => 'datetime',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'trial_started_at' => 'date',
        'trial_ends_at' => 'date',
        'trial_notice_sent_at' => 'datetime',
        'pending_change_effective_on' => 'date',
        'pending_proration_amount' => 'decimal:2',
        'pending_proration_applied_at' => 'datetime',
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

    public function subscriptionChanges(): HasMany
    {
        return $this->hasMany(CompanySubscriptionChange::class);
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
            default => $this->nexaPackageName() ?? $this->package?->name ?? 'Maandabonnement',
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

        if ($this->billing_mode === self::MODE_PACKAGE) {
            if ($this->agreed_monthly_amount !== null) {
                return round(max(0, (float) $this->agreed_monthly_amount), 2);
            }

            $fromPricing = $this->nexaMonthlyAmount();
            if ($fromPricing !== null) {
                return $fromPricing;
            }

            if ($this->package) {
                return round(max(0, (float) $this->package->monthly_amount), 2);
            }
        }

        return 0.0;
    }

    public function subscriptionDiscountAmount(): float
    {
        $base = $this->subscriptionBaseAmount();
        $discount = $this->discountPercent();
        $packageNet = round(max(0, $base * (1 - ($discount / 100))), 2);

        return round(max(0, $base - $packageNet), 2);
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
        $packageNet = round(max(0, $base * (1 - ($discount / 100))), 2);

        if ($this->billing_mode !== self::MODE_PACKAGE) {
            return $packageNet;
        }

        return round($packageNet + $this->packageAddonMonthlyAmount(), 2);
    }

    /**
     * @return list<array{key: string, name: string, quantity: int, unit_price: float, total: float}>
     */
    public function packageAddonLines(): array
    {
        if ($this->billing_mode !== self::MODE_PACKAGE) {
            return [];
        }

        $company = $this->relatedCompany();
        if (! $company) {
            return [];
        }

        $selections = is_array($company->package_addons) ? $company->package_addons : [];

        return TenantPackageAddon::selectedBillingLines(
            $selections,
            app(NexaPricingService::class)->modulesCatalog()
        );
    }

    public function packageAddonMonthlyAmount(): float
    {
        return round(array_sum(array_map(
            fn (array $line) => (float) ($line['total'] ?? 0),
            $this->packageAddonLines()
        )), 2);
    }

    public function resolvedPackageName(): ?string
    {
        return $this->nexaPackageName() ?? $this->package?->name;
    }

    public function packageAddonSummary(): string
    {
        $parts = [];
        foreach ($this->packageAddonLines() as $line) {
            $name = trim((string) ($line['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $quantity = (int) ($line['quantity'] ?? 1);
            $parts[] = $quantity > 1 ? $name.' ×'.$quantity : $name;
        }

        return implode(', ', $parts);
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

    private function relatedCompany(): ?Company
    {
        if ($this->relationLoaded('company')) {
            return $this->company;
        }

        return $this->company()->first();
    }

    private function nexaPackageKey(): string
    {
        $fromCompany = trim((string) ($this->relatedCompany()?->package_key ?? ''));
        if ($fromCompany !== '') {
            return $fromCompany;
        }

        return trim((string) ($this->package?->package_key ?? ''));
    }

    private function nexaMonthlyAmount(): ?float
    {
        $key = $this->nexaPackageKey();
        if ($key === '') {
            return null;
        }

        $amount = app(NexaPricingService::class)->monthlyAmountForKey($key);

        return $amount === null ? null : round(max(0, $amount), 2);
    }

    private function nexaPackageName(): ?string
    {
        $key = $this->nexaPackageKey();
        if ($key === '') {
            return null;
        }

        $name = trim((string) (app(NexaPricingService::class)->packageByKey($key)['name'] ?? ''));

        return $name !== '' ? $name : null;
    }
}
