<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportCustomer extends Model
{
    protected $table = 'transport_customers';

    protected $fillable = [
        'company_id',
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'debtor_number',
        'billing_address',
        'billing_city',
        'billing_postal_code',
        'billing_country',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

