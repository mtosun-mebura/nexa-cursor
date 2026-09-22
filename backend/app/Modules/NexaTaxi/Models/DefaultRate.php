<?php

namespace App\Modules\NexaTaxi\Models;

use App\Modules\NexaTaxi\Support\DefaultRateSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Standaardtarieven per scope: company_id null = NEXA Suite (algemene website / marketplace),
 * anders de tarieven van dat taxibedrijf. Worden gebruikt wanneer een voertuig geen eigen tarieven heeft.
 */
class DefaultRate extends Model
{
    protected $table = 'default_rates';

    public const DEFAULT_EVENING_NIGHT_MULTIPLIER = 1.2;

    public const DEFAULT_EVENING_NIGHT_FROM_HOUR = 22;

    public const DEFAULT_EVENING_NIGHT_UNTIL_HOUR = 6;

    /** @var array<string, bool> */
    private static array $companyIdColumnCache = [];

    protected $fillable = [
        'company_id',
        'person_range',
        'base_fare',
        'min_fare',
        'price_per_km',
        'price_per_min',
        'cleaning_costs',
        'evening_night_multiplier',
        'evening_night_from_hour',
        'evening_night_until_hour',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'base_fare' => 'decimal:2',
        'min_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_min' => 'decimal:2',
        'cleaning_costs' => 'decimal:2',
        'evening_night_multiplier' => 'decimal:2',
        'evening_night_from_hour' => 'integer',
        'evening_night_until_hour' => 'integer',
    ];

    public static function resetCompanyIdColumnCache(): void
    {
        self::$companyIdColumnCache = [];
    }

    public static function hasCompanyIdColumn(string $connection): bool
    {
        if (! array_key_exists($connection, self::$companyIdColumnCache)) {
            self::$companyIdColumnCache[$connection] = Schema::connection($connection)->hasTable('default_rates')
                && Schema::connection($connection)->hasColumn('default_rates', 'company_id');
        }

        return self::$companyIdColumnCache[$connection];
    }

    public static function normalizeCompanyId(mixed $companyId): ?int
    {
        if ($companyId === null || $companyId === '' || ! is_numeric($companyId)) {
            return null;
        }
        $id = (int) $companyId;

        return $id > 0 ? $id : null;
    }

    /**
     * @return Builder<self>
     */
    public static function queryForCompany(string $connection, mixed $companyId): Builder
    {
        $query = self::on($connection);
        if (! self::hasCompanyIdColumn($connection)) {
            return $query;
        }

        $normalized = self::normalizeCompanyId($companyId);
        if ($normalized === null) {
            return $query->whereNull('company_id');
        }

        return $query->where('company_id', $normalized);
    }

    /**
     * Haal het default rates record op voor het gegeven personenbereik.
     */
    public static function getByPersonRange(string $connection, string $personRange, mixed $companyId = null, bool $fallbackToPlatform = true): ?self
    {
        $rate = self::queryForCompany($connection, $companyId)->where('person_range', $personRange)->first();
        if ($rate !== null || ! $fallbackToPlatform) {
            return $rate;
        }

        if (self::normalizeCompanyId($companyId) === null) {
            return null;
        }

        return self::queryForCompany($connection, null)->where('person_range', $personRange)->first();
    }

    /**
     * Haal het eerste/default record op.
     */
    public static function getDefault(string $connection, mixed $companyId = null, bool $fallbackToPlatform = true): ?self
    {
        return self::getByPersonRange($connection, '1-4', $companyId, $fallbackToPlatform)
            ?? self::sortedRates(self::queryForCompany($connection, $companyId)->get())->first()
            ?? ($fallbackToPlatform && self::normalizeCompanyId($companyId) !== null
                ? self::sortedRates(self::queryForCompany($connection, null)->get())->first()
                : null);
    }

    /**
     * Haal alle tariefsets op voor het bewerkformulier en zorg dat defaults bestaan.
     */
    public static function getRatesForEdit(string $connection, mixed $companyId = null): Collection
    {
        DefaultRateSchema::ensureColumns($connection);
        self::ensureBaseRanges($connection, $companyId);

        return self::sortedRates(self::queryForCompany($connection, $companyId)->get());
    }

    /**
     * Lees tarieven voor weergave zonder nieuwe rijen aan te maken. Tenant zonder eigen set valt terug op NEXA Suite.
     */
    public static function getRatesForDisplay(string $connection, mixed $companyId = null): Collection
    {
        $rates = self::sortedRates(self::queryForCompany($connection, $companyId)->get());
        if ($rates->isNotEmpty()) {
            return $rates;
        }

        if (self::normalizeCompanyId($companyId) !== null) {
            $platform = self::sortedRates(self::queryForCompany($connection, null)->get());
            if ($platform->isNotEmpty()) {
                return $platform;
            }
        }

        if (! self::hasCompanyIdColumn($connection)) {
            return self::sortedRates(self::on($connection)->get());
        }

        return $rates;
    }

    /**
     * @return array<string, string> [range => label]
     */
    public static function getPersonRangeOptions(string $connection, mixed $companyId = null): array
    {
        self::ensureBaseRanges($connection, $companyId);

        $ranges = self::queryForCompany($connection, $companyId)->pluck('person_range')->filter()->unique()->values()->all();
        if (self::normalizeCompanyId($companyId) !== null) {
            $platformRanges = self::queryForCompany($connection, null)->pluck('person_range')->filter()->all();
            $ranges = array_values(array_unique(array_merge($ranges, $platformRanges)));
        }
        usort($ranges, function (string $a, string $b) {
            [$aStart, $aEnd] = self::parseRangeBounds($a);
            [$bStart, $bEnd] = self::parseRangeBounds($b);

            return ($aStart <=> $bStart) ?: ($aEnd <=> $bEnd);
        });

        $out = [];
        foreach ($ranges as $range) {
            $out[$range] = self::formatPersonRangeLabel($range);
        }

        return $out;
    }

    public static function formatPersonRangeLabel(string $range): string
    {
        [$start, $end] = self::parseRangeBounds($range);
        if ($start === $end) {
            return $start . ' persoon';
        }
        if ($start <= 1) {
            return 't/m ' . $end . ' personen';
        }

        return $start . ' t/m ' . $end . ' personen';
    }

    /**
     * @return array{0:int,1:int}
     */
    public static function parseRangeBounds(string $range): array
    {
        $range = trim($range);
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $m) === 1) {
            $start = (int) $m[1];
            $end = (int) $m[2];
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }

            return [$start, $end];
        }

        return [1, 4];
    }

    /**
     * @return array{multiplier: float, from_hour: int, until_hour: int}
     */
    public static function eveningNightSettings(?self $rate): array
    {
        $multiplier = (float) ($rate?->evening_night_multiplier ?? self::DEFAULT_EVENING_NIGHT_MULTIPLIER);
        if ($multiplier < 1) {
            $multiplier = 1.0;
        }

        return [
            'multiplier' => $multiplier,
            'from_hour' => self::normalizeHour($rate?->evening_night_from_hour ?? self::DEFAULT_EVENING_NIGHT_FROM_HOUR),
            'until_hour' => self::normalizeHour($rate?->evening_night_until_hour ?? self::DEFAULT_EVENING_NIGHT_UNTIL_HOUR),
        ];
    }

    public static function isEveningNightHour(int $hour, int $fromHour, int $untilHour): bool
    {
        $hour = self::normalizeHour($hour);
        $fromHour = self::normalizeHour($fromHour);
        $untilHour = self::normalizeHour($untilHour);

        if ($fromHour === $untilHour) {
            return false;
        }

        if ($fromHour < $untilHour) {
            return $hour >= $fromHour && $hour < $untilHour;
        }

        return $hour >= $fromHour || $hour < $untilHour;
    }

    public static function formatHourLabel(int $hour): string
    {
        return str_pad((string) self::normalizeHour($hour), 2, '0', STR_PAD_LEFT).':00';
    }

    public static function normalizeHour(mixed $hour): int
    {
        $value = (int) $hour;
        if ($value < 0) {
            return 0;
        }
        if ($value > 23) {
            return 23;
        }

        return $value;
    }

    /**
     * @param  Collection<int, self>  $rates
     * @return Collection<int, self>
     */
    private static function sortedRates(Collection $rates): Collection
    {
        return $rates->sortBy(function (self $rate) {
            [$start, $end] = self::parseRangeBounds((string) $rate->person_range);

            return ($start * 1000) + $end;
        })->values();
    }

    private static function ensureBaseRanges(string $connection, mixed $companyId = null): void
    {
        DefaultRateSchema::ensureColumns($connection);

        $normalized = self::normalizeCompanyId($companyId);
        $platformByRange = [];
        if ($normalized !== null && self::hasCompanyIdColumn($connection)) {
            foreach (self::queryForCompany($connection, null)->get() as $platformRate) {
                $platformByRange[(string) $platformRate->person_range] = $platformRate;
            }
        }

        foreach (['1-4', '5-8'] as $range) {
            if (self::queryForCompany($connection, $normalized)->where('person_range', $range)->exists()) {
                continue;
            }

            $source = $platformByRange[$range] ?? null;
            $payload = [
                'person_range' => $range,
                'base_fare' => $source?->base_fare,
                'min_fare' => $source?->min_fare ?? 0,
                'price_per_km' => $source?->price_per_km ?? 0,
                'price_per_min' => $source?->price_per_min ?? 0,
                'cleaning_costs' => $source?->cleaning_costs,
                'evening_night_multiplier' => $source?->evening_night_multiplier ?? self::DEFAULT_EVENING_NIGHT_MULTIPLIER,
                'evening_night_from_hour' => $source?->evening_night_from_hour ?? self::DEFAULT_EVENING_NIGHT_FROM_HOUR,
                'evening_night_until_hour' => $source?->evening_night_until_hour ?? self::DEFAULT_EVENING_NIGHT_UNTIL_HOUR,
            ];
            if (self::hasCompanyIdColumn($connection)) {
                $payload['company_id'] = $normalized;
            }

            self::on($connection)->create($payload);
        }
    }
}
