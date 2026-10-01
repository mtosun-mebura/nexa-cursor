<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutIdentityAuditLog extends Model
{
    protected $fillable = [
        'payout_identity_id',
        'company_id',
        'actor_user_id',
        'action',
        'payload',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function payoutIdentity(): BelongsTo
    {
        return $this->belongsTo(PayoutIdentity::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
