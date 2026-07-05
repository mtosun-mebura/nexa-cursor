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
        'planning_color',
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

    public function customer()
    {
        return $this->belongsTo(TransportCustomer::class, 'transport_customer_id');
    }

    public function mandate()
    {
        return $this->hasOne(TransportPaymentMandate::class, 'transport_contract_id')->latestOfMany();
    }

    public function planningColorHex(): string
    {
        $color = trim((string) ($this->planning_color ?? ''));

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return strtolower($color);
        }

        $palette = [
            '#3b82f6',
            '#8b5cf6',
            '#10b981',
            '#f59e0b',
            '#ef4444',
            '#06b6d4',
            '#ec4899',
        ];

        $index = $this->id ? ((int) $this->id % count($palette)) : 0;

        return $palette[$index];
    }

    public function planningCardStyle(): string
    {
        $hex = ltrim($this->planningColorHex(), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return 'background-color: rgba('.$r.', '.$g.', '.$b.', 0.16); border-color: rgba('.$r.', '.$g.', '.$b.', 0.55);';
    }
}

