<?php

namespace App\Services\FinancialOverviews;

use Carbon\Carbon;
use InvalidArgumentException;

final class FinancialOverviewPeriod
{
    public const TYPE_MONTH = 'month';

    public const TYPE_QUARTER = 'quarter';

    public const TYPE_HALF_YEAR = 'half_year';

    public const TYPE_YEAR = 'year';

    public const TYPE_CUSTOM = 'custom';

    public function __construct(
        public readonly string $type,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $label,
        public readonly ?int $year = null,
        public readonly ?int $month = null,
        public readonly ?int $quarter = null,
        public readonly ?int $half = null,
    ) {}

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_MONTH,
            self::TYPE_QUARTER,
            self::TYPE_HALF_YEAR,
            self::TYPE_YEAR,
            self::TYPE_CUSTOM,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_MONTH => 'Maand',
            self::TYPE_QUARTER => 'Kwartaal',
            self::TYPE_HALF_YEAR => 'Halfjaar',
            self::TYPE_YEAR => 'Jaar',
            self::TYPE_CUSTOM => 'Vrije periode',
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromInput(array $input): self
    {
        $type = (string) ($input['period_type'] ?? self::TYPE_MONTH);
        if (! in_array($type, self::types(), true)) {
            throw new InvalidArgumentException('Ongeldige periode.');
        }

        $timezone = (string) config('app.timezone', 'Europe/Amsterdam');
        $now = Carbon::now($timezone);

        if ($type === self::TYPE_CUSTOM) {
            $startRaw = parse_admin_date($input['start_date'] ?? null);
            $endRaw = parse_admin_date($input['end_date'] ?? null);
            if ($startRaw === null || $endRaw === null) {
                throw new InvalidArgumentException('Kies een start- en einddatum.');
            }
            $start = Carbon::createFromFormat('Y-m-d', $startRaw, $timezone)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endRaw, $timezone)->endOfDay();
            if ($end->lt($start)) {
                throw new InvalidArgumentException('De einddatum moet na de startdatum liggen.');
            }

            return new self(
                self::TYPE_CUSTOM,
                $start,
                $end,
                $start->format('d-m-Y').' t/m '.$end->format('d-m-Y'),
            );
        }

        $year = (int) ($input['year'] ?? $now->year);
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Kies een geldig jaar.');
        }

        return match ($type) {
            self::TYPE_MONTH => self::forMonth($year, (int) ($input['month'] ?? $now->month), $timezone),
            self::TYPE_QUARTER => self::forQuarter($year, (int) ($input['quarter'] ?? (int) ceil($now->month / 3)), $timezone),
            self::TYPE_HALF_YEAR => self::forHalfYear($year, (int) ($input['half'] ?? ($now->month <= 6 ? 1 : 2)), $timezone),
            default => self::forYear($year, $timezone),
        };
    }

    public static function forMonth(int $year, int $month, string $timezone): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Kies een geldige maand.');
        }
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $months = [
            1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april',
            5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'augustus',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
        ];
        $label = ($months[$month] ?? $start->format('F')).' '.$year;

        return new self(self::TYPE_MONTH, $start, $end, $label, $year, $month);
    }

    public static function forQuarter(int $year, int $quarter, string $timezone): self
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException('Kies een geldig kwartaal.');
        }
        $startMonth = (($quarter - 1) * 3) + 1;
        $start = Carbon::create($year, $startMonth, 1, 0, 0, 0, $timezone)->startOfDay();
        $end = $start->copy()->addMonths(2)->endOfMonth();
        $label = $quarter.'e kwartaal '.$year;

        return new self(self::TYPE_QUARTER, $start, $end, $label, $year, quarter: $quarter);
    }

    public static function forHalfYear(int $year, int $half, string $timezone): self
    {
        if ($half !== 1 && $half !== 2) {
            throw new InvalidArgumentException('Kies een geldig halfjaar.');
        }
        $startMonth = $half === 1 ? 1 : 7;
        $start = Carbon::create($year, $startMonth, 1, 0, 0, 0, $timezone)->startOfDay();
        $end = $start->copy()->addMonths(5)->endOfMonth();
        $label = $half === 1 ? '1e helft '.$year : '2e helft '.$year;

        return new self(self::TYPE_HALF_YEAR, $start, $end, $label, $year, half: $half);
    }

    public static function forYear(int $year, string $timezone): self
    {
        $start = Carbon::create($year, 1, 1, 0, 0, 0, $timezone)->startOfDay();
        $end = Carbon::create($year, 12, 31, 23, 59, 59, $timezone);

        return new self(self::TYPE_YEAR, $start, $end, (string) $year, $year);
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }
}
