<?php

namespace App\Support;

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
        return [
            self::EXTRA_CLIENTS => max(0, min(50, (int) ($raw[self::EXTRA_CLIENTS] ?? 0))),
            self::GPS_TRACKING => filter_var($raw[self::GPS_TRACKING] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            self::FLEET => filter_var($raw[self::FLEET] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $selections
     * @param  list<array<string, mixed>>  $catalog
     * @return list<array{key: string, name: string, quantity: int, unit_price: float, total: float}>
     */
    public static function selectedBillingLines(array $selections, array $catalog = []): array
    {
        $selections = self::normalizeSelections($selections);
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
            $quantity = (int) ($selections[$key] ?? 0);
            if ($quantity <= 0) {
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
            ];
        }

        return $lines;
    }
}
