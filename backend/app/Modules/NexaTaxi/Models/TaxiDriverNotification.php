<?php

namespace App\Modules\NexaTaxi\Models;

use Illuminate\Database\Eloquent\Model;

class TaxiDriverNotification extends Model
{
    public const TYPE_NEW_RATING = 'new_rating';

    public const TYPE_ADMIN_MESSAGE = 'admin_message';

    public const TYPE_COMPLAINT_FOLLOW_UP = 'complaint_follow_up';

    public const TYPE_BLOCKED = 'blocked';

    protected $table = 'taxi_driver_notifications';

    protected $fillable = [
        'driver_id',
        'company_id',
        'type',
        'title',
        'body',
        'related_complaint_id',
        'related_ride_id',
        'related_rating_id',
        'created_by',
        'read_at',
        'closed_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }
}
