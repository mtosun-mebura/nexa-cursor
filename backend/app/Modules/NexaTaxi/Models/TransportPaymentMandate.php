<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPaymentMandate extends Model
{
    protected $table = 'transport_payment_mandates';

    protected $fillable = [
        'transport_contract_id',
        'mandate_reference',
        'account_holder',
        'iban',
        'bic',
        'status',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];
}

