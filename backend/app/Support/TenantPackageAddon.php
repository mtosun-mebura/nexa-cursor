<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Aanvullende abonnementsmodules, los van het maandpakket.
 * Prijzen zijn overschrijfbaar via de Paketten-pagina.
 */
final class TenantPackageAddon
{
    public const EXTRA_CLIENTS = 'extra_clients';

    public const GPS_TRACKING = 'gps_tracking';

    public const FLEET = 'vloot';

    public const TYPE_BOOL = 'bool';

    public const TYPE_QUANTITY = 'quantity';

    public const EXTRA_CLIENTS_PER_PACK = 10;

    /**
     * @return list<array{
     *     key: string,
     *     type: string,
     *     label: string,
     *     hint: string,
     *     price: int,
     *     code: string,
     *     extra_clients?: int,
     *     unlimited_clients?: bool
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::EXTRA_CLIENTS,
                'type' => self::TYPE_QUANTITY,
                'label' => 'Extra contractklanten',
                'hint' => 'Elke bundel telt +'.self::EXTRA_CLIENTS_PER_PACK.' actieve contractklanten bovenop het Business-limiet (10).',
                'price' => 49,
                'extra_clients' => self::EXTRA_CLIENTS_PER_PACK,
                'code' => 'TenantPackageAddon::EXTRA_CLIENTS',
            ],
            [
                'key' => self::GPS_TRACKING,
                'type' => self::TYPE_BOOL,
                'label' => 'GPS-trackers',
                'hint' => 'Volg taxi’s live op de kaart via GPS (+ € 19 per maand).',
                'price' => 19,
                'code' => 'TenantPackageAddon::GPS_TRACKING',
            ],
            [
                'key' => self::FLEET,
                'type' => self::TYPE_BOOL,
                'label' => 'Vloot',
                'hint' => 'Onbeperkt contractklanten, plus ruimte voor meerdere vestigingen en maatwerk. GPS-trackers blijven een aparte module.',
                'price' => 249,
                'unlimited_clients' => true,
                'code' => 'TenantPackageAddon::FLEET',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'key');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $key): ?array
    {
        foreach (self::definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $saved
     * @return list<array<string, mixed>>
     */
    public static function catalogWithPrices(array $saved = []): array
    {
        $byKey = [];
        foreach ($saved as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? (is_string($index) ? $index : '')));
            if ($key !== '') {
                $byKey[$key] = $row;
            }
        }

        $out = [];
        foreach (self::definitions() as $definition) {
            $row = $byKey[$definition['key']] ?? [];
            $price = $row['price'] ?? $definition['price'];
            $out[] = array_merge($definition, [
                'name' => trim((string) ($row['name'] ?? $definition['label'])),
                'price' => max(0, (int) $price),
                'description' => trim((string) ($row['description'] ?? $definition['hint'])),
            ]);
        }

        return $out;
    }

    /**
     * @param  array<int|string, mixed>  $raw
     * @return list<array{key: string, name: string, price: int, description: string}>
     */
    public static function normalizeCatalog(array $raw): array
    {
        $byKey = [];
        foreach ($raw as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? (is_string($index) ? $index : '')));
            if ($key !== '') {
                $byKey[$key] = $row;
            }
        }

        $out = [];
        foreach (self::definitions() as $definition) {
            $row = $byKey[$definition['key']] ?? [];
            $out[] = [
                'key' => $definition['key'],
                'name' => trim((string) ($row['name'] ?? $definition['label'])) ?: $definition['label'],
                'price' => max(0, (int) ($row['price'] ?? $definition['price'])),
                'description' => trim((string) ($row['description'] ?? $definition['hint'])),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, int>
     */
    public static function normalizeSelections(array $raw): array
    {
        $records = self::normalizeRecords($raw);
        $out = [];
        foreach (self::definitions() as $definition) {
            $out[$definition['key']] = (int) ($records[$definition['key']]['quantity'] ?? 0);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $previous
     * @return array<string, array{quantity: int, starts_at: ?string, active_quantity: int, prepaid_through: ?string}>
     */
    public static function normalizeRecords(array $raw, array $previous = [], bool $cancelImmediately = false): array
    {
        $previousRecords = $previous === [] ? [] : self::normalizeRecords($previous, []);
        $asOf = now()->startOfDay();
        $nextMonth = self::latestStartDate($asOf)->toDateString();
        $out = [];

        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $posted = $raw[$key] ?? 0;
            if (
                $previous === []
                && is_array($posted)
                && array_key_exists('quantity', $posted)
                && (array_key_exists('active_quantity', $posted) || array_key_exists('prepaid_through', $posted))
            ) {
                $quantity = self::clampQuantity($key, self::quantityFrom($posted, $definition['type']));
                $startsAt = self::startsAtFrom($posted);
                $activeQuantity = self::clampQuantity(
                    $key,
                    (int) ($posted['active_quantity'] ?? $quantity)
                );
                if ($quantity <= 0 && ($activeQuantity <= 0 || $startsAt === null)) {
                    $out[$key] = self::emptyRecord();

                    continue;
                }
                $out[$key] = [
                    'quantity' => $quantity,
                    'starts_at' => $startsAt ?? ($asOf->toDateString()),
                    'active_quantity' => $activeQuantity,
                    'prepaid_through' => self::periodFrom($posted['prepaid_through'] ?? null),
                ];

                continue;
            }

            $prev = $previousRecords[$key] ?? self::emptyRecord();
            $quantity = self::clampQuantity($key, self::quantityFrom($posted, $definition['type']));
            $postedStart = self::startsAtFrom($posted);
            $prevEntitled = self::entitledQuantityFromRecord($prev, $asOf);

            if ($quantity < $prevEntitled && ! $cancelImmediately) {
                $out[$key] = [
                    'quantity' => $quantity,
                    'starts_at' => $nextMonth,
                    'active_quantity' => $prevEntitled,
                    'prepaid_through' => $prev['prepaid_through'] ?? null,
                ];

                continue;
            }

            if ($quantity <= 0) {
                $out[$key] = self::emptyRecord();

                continue;
            }

            $startsAt = $postedStart;
            if ($startsAt === null && $quantity > $prevEntitled) {
                $startsAt = $asOf->toDateString();
            }
            if ($startsAt === null) {
                $startsAt = $prev['starts_at'] ?? $asOf->toDateString();
            }

            $activeQuantity = $quantity;
            if ($quantity > $prevEntitled && $startsAt > $asOf->toDateString()) {
                $activeQuantity = $prevEntitled;
            } elseif ($quantity <= $prevEntitled) {
                $activeQuantity = $quantity;
            }

            $out[$key] = [
                'quantity' => $quantity,
                'starts_at' => $startsAt,
                'active_quantity' => $activeQuantity,
                'prepaid_through' => $prev['prepaid_through'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $records
     * @param  array<string, mixed>  $previous
     */
    public static function assertStartDates(array $records, array $previous = [], ?CarbonInterface $asOf = null): void
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $maxStart = $asOf->copy()->addMonthNoOverflow()->startOfMonth();
        $previousRecords = $previous === [] ? [] : self::normalizeRecords($previous, []);
        $errors = [];

        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $record = $records[$key] ?? self::emptyRecord();
            $quantity = (int) ($record['quantity'] ?? 0);
            $prevEntitled = self::entitledQuantityFromRecord($previousRecords[$key] ?? self::emptyRecord(), $asOf);
            if ($quantity <= $prevEntitled) {
                continue;
            }

            $startsAt = self::parseDate($record['starts_at'] ?? null);
            $field = 'package_addons.'.$key.'.starts_at';
            if (! $startsAt) {
                $errors[$field] = 'Kies een ingangsdatum voor '.$definition['label'].'.';

                continue;
            }
            if ($startsAt->lt($asOf)) {
                $errors[$field] = 'De ingangsdatum van '.$definition['label'].' mag niet in het verleden liggen.';

                continue;
            }
            if ($startsAt->gt($maxStart)) {
                $errors[$field] = 'De ingangsdatum van '.$definition['label'].' mag maximaal de 1e van volgende maand zijn.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, int>
     */
    public static function effectiveSelections(array $raw, ?CarbonInterface $asOf = null): array
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $records = self::looksLikeRecords($raw) ? self::normalizeRecords($raw, []) : self::normalizeRecords($raw, []);
        $out = [];
        foreach (self::definitions() as $definition) {
            $out[$definition['key']] = self::entitledQuantityFromRecord(
                $records[$definition['key']] ?? self::emptyRecord(),
                $asOf
            );
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public static function entitledQuantityFromRecord(array $record, ?CarbonInterface $asOf = null): int
    {
        $quantity = (int) ($record['quantity'] ?? 0);
        $startsAt = self::parseDate($record['starts_at'] ?? null);
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $activeQuantity = max(0, (int) ($record['active_quantity'] ?? 0));

        if ($startsAt !== null && $startsAt->greaterThan($asOf)) {
            return $activeQuantity;
        }

        return max(0, $quantity);
    }

    public static function isPendingCancel(array $record, ?CarbonInterface $asOf = null): bool
    {
        return (int) ($record['quantity'] ?? 0) <= 0
            && self::entitledQuantityFromRecord($record, $asOf) > 0;
    }

    /**
     * Minder bundels ingepland per starts_at, huidige hoeveelheid blijft tot die datum.
     */
    public static function isPendingDecrease(array $record, ?CarbonInterface $asOf = null): bool
    {
        $quantity = (int) ($record['quantity'] ?? 0);
        $entitled = self::entitledQuantityFromRecord($record, $asOf);

        return $quantity > 0 && $entitled > $quantity;
    }

    /**
     * @param  array<string, mixed>  $selections
     * @param  list<array<string, mixed>>  $catalog
     * @return list<array{key: string, name: string, quantity: int, unit_price: float, total: float, starts_at?: ?string}>
     */
    public static function selectedBillingLines(
        array $selections,
        array $catalog = [],
        ?CarbonInterface $asOf = null,
        bool $includeUpcoming = true,
        ?string $skipPrepaidPeriod = null,
    ): array {
        $records = self::looksLikeRecords($selections)
            ? self::normalizeRecords($selections, [])
            : self::normalizeRecords($selections, []);
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $catalogByKey = [];
        foreach ($catalog as $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? ''));
            if ($key !== '') {
                $catalogByKey[$key] = $row;
            }
        }

        $lines = [];
        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $record = $records[$key] ?? self::emptyRecord();
            $quantity = $includeUpcoming
                ? (int) ($record['quantity'] ?? 0)
                : self::entitledQuantityFromRecord($record, $asOf);
            if ($quantity <= 0) {
                continue;
            }
            $prepaid = trim((string) ($record['prepaid_through'] ?? ''));
            if ($skipPrepaidPeriod !== null && $prepaid !== '' && $prepaid === $skipPrepaidPeriod) {
                continue;
            }

            $row = $catalogByKey[$key] ?? [];
            $name = trim((string) ($row['name'] ?? $definition['label']));
            $unitPrice = round(max(0, (float) ($row['price'] ?? $definition['price'])), 2);

            $lines[] = [
                'key' => $key,
                'name' => $name !== '' ? $name : $definition['label'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($unitPrice * $quantity, 2),
                'starts_at' => $record['starts_at'] ?? null,
                'prepaid_through' => $record['prepaid_through'] ?? null,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $next
     * @param  list<array<string, mixed>>  $catalog
     * @return list<array<string, mixed>>
     */
    public static function activationCharges(
        array $previous,
        array $next,
        array $catalog = [],
        ?CarbonInterface $asOf = null,
    ): array {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $prevRecords = self::normalizeRecords($previous, []);
        $nextRecords = self::normalizeRecords($next, $previous);
        $catalogByKey = [];
        foreach ($catalog as $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? ''));
            if ($key !== '') {
                $catalogByKey[$key] = $row;
            }
        }

        $charges = [];
        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $prevQty = self::entitledQuantityFromRecord($prevRecords[$key] ?? self::emptyRecord(), $asOf);
            $nextRecord = $nextRecords[$key] ?? self::emptyRecord();
            $nextQty = (int) ($nextRecord['quantity'] ?? 0);
            $delta = $nextQty - $prevQty;
            if ($delta <= 0) {
                continue;
            }

            $startsAt = self::parseDate($nextRecord['starts_at'] ?? null) ?? $asOf;
            $row = $catalogByKey[$key] ?? [];
            $name = trim((string) ($row['name'] ?? $definition['label'])) ?: $definition['label'];
            $unitPrice = round(max(0, (float) ($row['price'] ?? $definition['price'])), 2);
            $charge = self::activationCharge($name, $delta, $unitPrice, $startsAt, $asOf);
            if ($charge === null) {
                continue;
            }
            $charge['key'] = $key;
            $charges[] = $charge;
        }

        return $charges;
    }

    /**
     * @return array{
     *     amount: float,
     *     quantity: int,
     *     unit_price: float,
     *     fraction: float,
     *     days: int,
     *     days_in_month: int,
     *     period: string,
     *     from: string,
     *     to: string,
     *     prepaid_through: string,
     *     description: string,
     *     full_month: bool
     * }|null
     */
    public static function activationCharge(
        string $name,
        int $quantity,
        float $unitPrice,
        CarbonInterface $startsAt,
        ?CarbonInterface $asOf = null,
    ): ?array {
        $quantity = max(0, $quantity);
        $unitPrice = round(max(0, $unitPrice), 2);
        if ($quantity <= 0 || $unitPrice <= 0) {
            return null;
        }

        $start = Carbon::parse($startsAt)->startOfDay();
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        if ($start->lt($asOf)) {
            $start = $asOf->copy();
        }

        $month = $start->copy()->startOfMonth();
        $monthEnd = $start->copy()->endOfMonth()->startOfDay();
        $daysInMonth = $month->daysInMonth;
        $fullMonth = $start->day === 1;
        $days = $fullMonth ? $daysInMonth : (int) ($start->diffInDays($monthEnd) + 1);
        $fraction = $days / $daysInMonth;
        $amount = round($unitPrice * $quantity * $fraction, 2);
        if ($amount <= 0) {
            return null;
        }

        $period = $month->format('Y-m');
        $monthNl = $month->copy()->locale('nl');
        $startNl = $start->copy()->locale('nl');
        $monthEndNl = $monthEnd->copy()->locale('nl');
        if ($fullMonth) {
            $description = $name.' — '.$monthNl->translatedFormat('F Y');
        } else {
            $description = $name.' — '.$startNl->translatedFormat('j').' t/m '.$monthEndNl->translatedFormat('j F Y')
                .' ('.$days.'/'.$daysInMonth.' dagen)';
        }

        return [
            'amount' => $amount,
            'quantity' => $quantity,
            'unit_price' => round($amount / $quantity, 2),
            'monthly_unit_price' => $unitPrice,
            'fraction' => $fraction,
            'days' => $days,
            'days_in_month' => $daysInMonth,
            'period' => $period,
            'from' => $start->toDateString(),
            'to' => $monthEnd->toDateString(),
            'prepaid_through' => $period,
            'description' => $description,
            'full_month' => $fullMonth,
        ];
    }

    /**
     * @param  array<string, mixed>  $records
     * @param  list<array<string, mixed>>  $charges
     * @return array<string, mixed>
     */
    public static function applyPrepaidThrough(array $records, array $charges): array
    {
        foreach ($charges as $charge) {
            $key = (string) ($charge['key'] ?? '');
            if ($key === '' || ! isset($records[$key]) || ! is_array($records[$key])) {
                continue;
            }
            $records[$key]['prepaid_through'] = $charge['prepaid_through'] ?? $records[$key]['prepaid_through'] ?? null;
        }

        return $records;
    }

    public static function earliestStartDate(?CarbonInterface $asOf = null): Carbon
    {
        return Carbon::parse($asOf ?? now())->startOfDay();
    }

    public static function latestStartDate(?CarbonInterface $asOf = null): Carbon
    {
        return Carbon::parse($asOf ?? now())->startOfDay()->addMonthNoOverflow()->startOfMonth();
    }

    /**
     * @return array{quantity: int, starts_at: null, active_quantity: int, prepaid_through: null}
     */
    public static function emptyRecord(): array
    {
        return [
            'quantity' => 0,
            'starts_at' => null,
            'active_quantity' => 0,
            'prepaid_through' => null,
        ];
    }

    public static function clampQuantity(string $key, int $quantity): int
    {
        $definition = self::definition($key);
        if (! $definition) {
            return 0;
        }
        if ($definition['type'] === self::TYPE_QUANTITY) {
            return max(0, min(50, $quantity));
        }

        return $quantity > 0 ? 1 : 0;
    }

    /**
     * @param  mixed  $raw
     */
    public static function quantityFrom(mixed $raw, string $type = self::TYPE_BOOL): int
    {
        if (is_array($raw)) {
            if (array_key_exists('quantity', $raw)) {
                return (int) $raw['quantity'];
            }
            if (array_key_exists('enabled', $raw)) {
                return filter_var($raw['enabled'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            return 0;
        }

        if ($type === self::TYPE_QUANTITY) {
            return (int) $raw;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    /**
     * @param  mixed  $raw
     */
    public static function startsAtFrom(mixed $raw): ?string
    {
        if (! is_array($raw)) {
            return null;
        }

        return self::parseDate($raw['starts_at'] ?? null)?->toDateString();
    }

    public static function parseDate(mixed $value): ?Carbon
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function periodFrom(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
            return $raw;
        }

        return self::parseDate($raw)?->format('Y-m');
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private static function looksLikeRecords(array $raw): bool
    {
        foreach ($raw as $value) {
            if (is_array($value) && (array_key_exists('quantity', $value) || array_key_exists('starts_at', $value))) {
                return true;
            }
        }

        return false;
    }
}
