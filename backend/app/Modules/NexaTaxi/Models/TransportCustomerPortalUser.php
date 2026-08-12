<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportCustomerPortalUser extends Model
{
    public const ROLE_CONTRACTANT = 'contractant';

    public const ROLE_CONTRACTOUDER = 'contractouder';

    protected $table = 'transport_customer_portal_users';

    protected $fillable = [
        'company_id',
        'transport_customer_id',
        'user_id',
        'portal_role',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(TransportCustomer::class, 'transport_customer_id');
    }

    public function isContractant(): bool
    {
        return $this->portal_role === self::ROLE_CONTRACTANT;
    }

    public function isContractouder(): bool
    {
        return $this->portal_role === self::ROLE_CONTRACTOUDER;
    }
}
