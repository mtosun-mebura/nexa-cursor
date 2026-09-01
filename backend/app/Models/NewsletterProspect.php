<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsletterProspect extends Model
{
    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'company_name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'province',
        'branch',
        'google_place_id',
        'source',
        'source_url',
        'status',
        'unsubscribe_token',
        'unsubscribed_at',
        'notes',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $prospect): void {
            if (! filled($prospect->unsubscribe_token)) {
                $prospect->unsubscribe_token = Str::lower(Str::random(48));
            }
            $prospect->email = strtolower(trim((string) $prospect->email));
        });
    }

    public function sends(): HasMany
    {
        return $this->hasMany(NewsletterSend::class, 'prospect_id');
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBSCRIBED);
    }

    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    public function contactFullName(): ?string
    {
        $full = trim(implode(' ', array_filter([
            trim((string) $this->first_name),
            trim((string) $this->middle_name),
            trim((string) $this->last_name),
        ], fn (string $part) => $part !== '')));

        return $full !== '' ? $full : null;
    }

    public function greetingName(): string
    {
        return $this->contactFullName() ?: $this->company_name;
    }

    public function unsubscribe(): void
    {
        $this->status = self::STATUS_UNSUBSCRIBED;
        $this->unsubscribed_at = now();
        $this->save();
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->unsubscribe_token]);
    }
}
