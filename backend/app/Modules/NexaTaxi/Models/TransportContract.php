<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportContract extends Model
{
    protected $table = 'transport_contracts';

    protected $fillable = [
        'company_id',
        'transport_customer_id',
        'name',
        'status',
        'start_date',
        'end_date',
        'billing_model',
        'monthly_amount',
        'price_per_ride',
        'invoice_day',
        'payment_terms_days',
        'tax_rate',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_amount' => 'decimal:2',
        'price_per_ride' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'invoice_day' => 'integer',
        'payment_terms_days' => 'integer',
    ];

    public function passengers()
    {
        return $this->hasMany(TransportPassenger::class, 'transport_contract_id');
    }

    public function groups()
    {
        return $this->hasMany(TransportGroup::class, 'transport_contract_id');
    }

    public function individualBookings()
    {
        return $this->hasMany(TransportIndividualBooking::class, 'transport_contract_id');
    }
}

