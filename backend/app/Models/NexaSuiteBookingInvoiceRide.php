<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NexaSuiteBookingInvoiceRide extends Model
{
    protected $fillable = [
        'nexa_suite_booking_invoice_id',
        'ride_request_id',
        'ride_price',
        'fee_amount',
    ];

    protected $casts = [
        'ride_request_id' => 'integer',
        'ride_price' => 'decimal:2',
        'fee_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(NexaSuiteBookingInvoice::class, 'nexa_suite_booking_invoice_id');
    }
}
