<?php

namespace App\Modules\TaxiRoyaal\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Algemene standaardtarieven (één rij). Worden gebruikt wanneer een voertuig geen eigen tarieven heeft.
 */
class DefaultRate extends Model
{
    protected $table = 'default_rates';

    protected $fillable = [
        'person_range',
        'base_fare',
        'min_fare',
        'price_per_km',
        'price_per_min',
        'cleaning_costs',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'min_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_min' => 'decimal:2',
        'cleaning_costs' => 'decimal:2',
    ];

    /**
     * Haal het default rates record op voor het gegeven personenbereik.
     */
    public static function getByPersonRange(string $connection, string $personRange): ?self
    {
        return self::on($connection)->where('person_range', $personRange)->first();
    }

    /**
     * Haal het eerste/default record op.
     */
    public static function getDefault(string $connection): ?self
    {
        $rate = self::on($connection)->where('person_range', '1-4')->first();
        return $rate ?? self::on($connection)->orderBy('person_range')->first();
    }

    /**
     * Haal alle tariefsets op voor het bewerkformulier en zorg dat defaults bestaan.
     */
    public static function getRatesForEdit(string $connection): \Illuminate\Support\Collection
    {
        self::ensureBaseRanges($connection);

        return self::on($connection)->get()->sortBy(function (self $rate) {
            [$start, $end] = self::parseRangeBounds((string) $rate->person_range);
            return ($start * 1000) + $end;
        })->values();
    }

    /**
     * @return array<string, string> [range => label]
     */
    public static function getPersonRangeOptions(string $connection): array
    {
        self::ensureBaseRanges($connection);

        $ranges = self::on($connection)->pluck('person_range')->filter()->unique()->values()->all();
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

    private static function ensureBaseRanges(string $connection): void
    {
        foreach (['1-4', '5-8'] as $range) {
            if (! self::on($connection)->where('person_range', $range)->exists()) {
                self::on($connection)->create([
                    'person_range' => $range,
                    'base_fare' => null,
                    'min_fare' => 0,
                    'price_per_km' => 0,
                    'price_per_min' => 0,
                    'cleaning_costs' => null,
                ]);
            }
        }
    }
}
