<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RidePlatformSettlement extends Model
{
    public const MODEL_MARKETPLACE = 'marketplace';

    public const MODEL_NETWORK = 'network';

    public const STATUS_PENDING_PAYOUT = 'pending_payout';

    public const STATUS_PAID_OUT = 'paid_out';

    public const STATUS_FAILED = 'failed';

    public const STATUS_MANUAL_REQUIRED = 'manual_required';

    protected $fillable = [
        'ride_request_id',
        'model',
        'owner_company_id',
        'fulfiller_company_id',
        'gross_amount',
        'nexa_fee_percent',
        'nexa_fee_amount',
        'net_amount',
        'owner_share_percent',
        'owner_share_amount',
        'fulfiller_share_percent',
        'fulfiller_share_amount',
        'currency',
        'status',
        'payout_attempts',
        'last_error',
        'payout_lines',
        'meta',
        'paid_out_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'nexa_fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'owner_share_amount' => 'decimal:2',
            'fulfiller_share_amount' => 'decimal:2',
            'payout_lines' => 'array',
            'meta' => 'array',
            'paid_out_at' => 'datetime',
        ];
    }

    public function ownerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'owner_company_id');
    }

    public function fulfillerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'fulfiller_company_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_PAYOUT => 'Wacht op uitbetaling',
            self::STATUS_PAID_OUT => 'Uitbetaald',
            self::STATUS_FAILED => 'Mislukt',
            self::STATUS_MANUAL_REQUIRED => 'Handmatige actie nodig',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? (string) $this->status;
    }
}
