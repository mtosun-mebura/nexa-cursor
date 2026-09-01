<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'name',
        'subject',
        'preview_text',
        'blocks',
        'status',
        'created_by',
        'last_sent_at',
    ];

    protected $casts = [
        'blocks' => 'array',
        'last_sent_at' => 'datetime',
    ];

    public function sends(): HasMany
    {
        return $this->hasMany(NewsletterSend::class, 'campaign_id');
    }
}
