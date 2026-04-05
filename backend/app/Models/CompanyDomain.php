<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDomain extends Model
{
    protected $fillable = [
        'company_id',
        'host',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (CompanyDomain $domain): void {
            $domain->host = self::normalizeHost((string) $domain->host);
        });
    }

    public static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        return $host;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
