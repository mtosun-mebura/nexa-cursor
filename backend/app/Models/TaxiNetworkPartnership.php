<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiNetworkPartnership extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'owner_company_id',
        'partner_company_id',
        'status',
        'invite_code_id',
        'requested_by_company_id',
        'acted_by_user_id',
        'accepted_at',
        'declined_at',
        'revoked_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function ownerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'owner_company_id');
    }

    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'partner_company_id');
    }

    public function inviteCode(): BelongsTo
    {
        return $this->belongsTo(TaxiNetworkInviteCode::class, 'invite_code_id');
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
