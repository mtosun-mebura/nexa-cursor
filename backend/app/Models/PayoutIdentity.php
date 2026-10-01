<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayoutIdentity extends Model
{
    public const PARTY_COMPANY = 'company';

    public const PARTY_INDEPENDENT_DRIVER = 'independent_driver';

    public const PROVIDER_MOLLIE = 'mollie';

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_ENABLED = 'enabled';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'owner_key',
        'company_id',
        'user_id',
        'settlement_party',
        'provider',
        'provider_account_id',
        'provider_organization_id',
        'capability_status',
        'masked_destination',
        'verified_at',
        'disabled_at',
        'pending_provider_account_id',
        'pending_masked_destination',
        'destination_change_requested_at',
        'destination_change_eligible_at',
        'last_synced_at',
        'provider_meta',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'destination_change_requested_at' => 'datetime',
            'destination_change_eligible_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'provider_meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PayoutIdentityAuditLog::class);
    }

    public function isEnabled(): bool
    {
        return $this->capability_status === self::STATUS_ENABLED
            && $this->disabled_at === null
            && filled($this->provider_account_id);
    }

    public function hasPendingDestinationChange(): bool
    {
        return filled($this->pending_provider_account_id)
            && $this->destination_change_eligible_at !== null;
    }

    public function destinationChangeIsCooling(): bool
    {
        if (! $this->hasPendingDestinationChange()) {
            return false;
        }

        return $this->destination_change_eligible_at->isFuture();
    }

    /**
     * Never expose provider_meta secrets — strip known sensitive keys if present.
     *
     * @return array<string, mixed>
     */
    public function safeMeta(): array
    {
        $meta = $this->provider_meta ?? [];
        foreach (['api_key', 'card_number', 'cvc', 'iban', 'account_number', 'secret', 'access_token', 'refresh_token'] as $key) {
            unset($meta[$key]);
        }

        return $meta;
    }
}
