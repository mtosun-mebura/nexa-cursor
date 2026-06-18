<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportScheduleException extends Model
{
    protected $table = 'transport_schedule_exceptions';

    protected $fillable = [
        'company_id',
        'transport_contract_id',
        'exception_date',
        'name',
        'active',
    ];

    protected $casts = [
        'exception_date' => 'date',
        'active' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(TransportContract::class, 'transport_contract_id');
    }
}
