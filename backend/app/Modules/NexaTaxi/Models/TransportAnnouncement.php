<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TransportAnnouncement extends Model
{
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    protected $table = 'transport_announcements';

    protected $fillable = [
        'company_id',
        'transport_customer_id',
        'title',
        'body',
        'severity',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(TransportCustomer::class, 'transport_customer_id');
    }

    public function scopeActiveForCustomer($query, int $customerId, $now = null)
    {
        $now = $now ?? now(config('app.timezone', 'Europe/Amsterdam'));

        return $query
            ->where('transport_customer_id', $customerId)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }
}
