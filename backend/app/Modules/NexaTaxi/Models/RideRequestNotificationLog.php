<?php

namespace App\Modules\NexaTaxi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideRequestNotificationLog extends Model
{
    public const UPDATED_AT = null;

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'ride_request_notification_logs';

    protected $fillable = [
        'ride_request_id',
        'channel',
        'status',
        'recipient_name',
        'recipient_address',
        'driver_id',
        'detail',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public static function channelLabels(): array
    {
        return [
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_EMAIL => 'E-mail',
            self::CHANNEL_SMS => 'SMS',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SENT => 'Verzonden',
            self::STATUS_FAILED => 'Mislukt',
            self::STATUS_SKIPPED => 'Overgeslagen',
        ];
    }

    public function getChannelLabelAttribute(): string
    {
        return self::channelLabels()[$this->channel] ?? $this->channel;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function rideRequest(): BelongsTo
    {
        return $this->belongsTo(RideRequest::class, 'ride_request_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
