<?php

namespace App\Models;

use App\Support\IncidentCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $fillable = [
        'company_id',
        'reporter_user_id',
        'reference',
        'kind',
        'title',
        'page_url',
        'description',
        'priority',
        'status',
        'screenshots',
        'resolution_note',
        'resolved_by_user_id',
        'resolved_at',
        'archived_at',
    ];

    protected $casts = [
        'screenshots' => 'array',
        'resolved_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class)->orderBy('created_at')->orderBy('id');
    }

    public function isHandled(): bool
    {
        return IncidentCatalog::isHandled((string) $this->status);
    }

    public static function nextReference(): string
    {
        $year = now()->format('Y');
        $last = static::query()
            ->where('reference', 'like', 'INC-'.$year.'-%')
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;
        if (is_string($last) && preg_match('/INC-\d{4}-(\d+)$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('INC-%s-%04d', $year, $sequence);
    }
}
