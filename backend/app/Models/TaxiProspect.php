<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiProspect extends Model
{
    public const KNV_MEMBER = 'member';

    public const KNV_UNKNOWN = 'unknown';

    public const KNV_NOT_MEMBER = 'not_member';

    public const LEAD_DISCOVERED = 'discovered';

    public const LEAD_CONTACT_READY = 'contact_ready';

    public const LEAD_OPTED_OUT = 'opted_out';

    protected $fillable = [
        'company_name',
        'trade_name',
        'kvk_number',
        'street',
        'house_number',
        'postal_code',
        'city',
        'municipality',
        'province',
        'country',
        'phone',
        'email',
        'website',
        'website_domain',
        'google_place_id',
        'knv_member_status',
        'verification_status',
        'lead_status',
        'confidence_score',
        'normalized_name',
        'normalized_postal_code',
        'discovered_at',
        'last_verified_at',
        'notes',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'confidence_score' => 'integer',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(TaxiProspectSource::class);
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(TaxiProspectServiceArea::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TaxiProspectInvitation::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(TaxiProspectContact::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('company_name', 'like', $like)
                ->orWhere('trade_name', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('postal_code', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('kvk_number', 'like', $like);
        });
    }

    public function scopeWithEmail(Builder $query): Builder
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    public function hasContactChannel(): bool
    {
        return filled($this->email) || filled($this->phone);
    }

    public function markContactReadyIfPossible(): void
    {
        if ($this->lead_status === self::LEAD_OPTED_OUT) {
            return;
        }
        if (filled($this->email) && in_array($this->lead_status, [self::LEAD_DISCOVERED, 'verified'], true)) {
            $this->lead_status = self::LEAD_CONTACT_READY;
        }
    }
}
