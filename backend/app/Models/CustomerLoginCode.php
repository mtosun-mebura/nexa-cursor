<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoginCode extends Model
{
    protected $table = 'customer_login_codes';

    public const PURPOSE_CUSTOMER = 'customer';

    public const PURPOSE_DRIVER = 'driver';

    public const PURPOSE_CONTRACT = 'contract';

    public const PURPOSE_ADMIN = 'admin';

    protected $fillable = [
        'user_id',
        'purpose',
        'code_hash',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

