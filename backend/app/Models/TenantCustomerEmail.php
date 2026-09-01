<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomerEmail extends Model
{
    public const TYPE_WELCOME = 'welcome';

    public const TYPE_TENANT_WELCOME = 'tenant_welcome';

    public const TYPE_LOGIN_CODE = 'login_code';

    public const TYPE_BOOKING = 'booking';

    public const TYPE_RIDE_ACCEPTED = 'ride_accepted';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_PAYMENT_LINK = 'payment_link';

    public const TYPE_REMINDER = 'reminder';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_WELCOME,
        self::TYPE_TENANT_WELCOME,
        self::TYPE_LOGIN_CODE,
        self::TYPE_BOOKING,
        self::TYPE_RIDE_ACCEPTED,
        self::TYPE_INVOICE,
        self::TYPE_PAYMENT_LINK,
        self::TYPE_REMINDER,
    ];

    /**
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        self::TYPE_WELCOME => 'Welkomstmail',
        self::TYPE_TENANT_WELCOME => 'Welkomstmail tenant',
        self::TYPE_LOGIN_CODE => 'Inlogcode',
        self::TYPE_BOOKING => 'Boekingsbevestiging',
        self::TYPE_RIDE_ACCEPTED => 'Rit geaccepteerd',
        self::TYPE_INVOICE => 'Factuur',
        self::TYPE_PAYMENT_LINK => 'Betaallink',
        self::TYPE_REMINDER => 'Herinnering',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_SENT => 'Verzonden',
        self::STATUS_FAILED => 'Mislukt',
        self::STATUS_SKIPPED => 'Overgeslagen',
    ];

    protected $fillable = [
        'company_id',
        'type',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_html',
        'body_text',
        'status',
        'related_type',
        'related_id',
        'sent_at',
        'resent_count',
        'last_resent_at',
        'resent_from_id',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'last_resent_at' => 'datetime',
        'related_id' => 'integer',
        'resent_count' => 'integer',
        'resent_from_id' => 'integer',
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resentFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isResend(): bool
    {
        return $this->resent_from_id !== null;
    }
}
