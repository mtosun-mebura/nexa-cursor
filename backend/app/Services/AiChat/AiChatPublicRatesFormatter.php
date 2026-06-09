<?php

namespace App\Services\AiChat;

final class AiChatPublicRatesFormatter
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function format(array $rows, ?string $companyLabel = null): string
    {
        if ($rows === []) {
            return 'Er zijn momenteel geen tarieven gepubliceerd. Neem contact op voor een prijsopgave.';
        }

        $label = $companyLabel !== null && trim($companyLabel) !== ''
            ? trim($companyLabel)
            : 'Nexa Taxi';

        $sections = [];
        foreach ($this->sortRows($rows) as $row) {
            $lines = $this->formatRowLines($row);
            if ($lines === []) {
                continue;
            }

            $personRange = trim((string) ($row['person_range'] ?? ''));
            if ($personRange !== '' && count($rows) > 1) {
                $sections[] = $personRange.' personen'."\n".implode("\n", $lines);
            } else {
                $sections[] = implode("\n", $lines);
            }
        }

        if ($sections === []) {
            return 'Er zijn momenteel geen tarieven gepubliceerd. Neem contact op voor een prijsopgave.';
        }

        return 'De actuele tarieven van '.$label.":\n\n".implode("\n\n", $sections);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function formatRowLines(array $row): array
    {
        $lines = [];

        if ($this->hasAmount($row, 'base_fare')) {
            $lines[] = '• Instaptarief: '.$this->formatEuro((float) $row['base_fare']);
        }

        if ($this->hasAmount($row, 'price_per_km')) {
            $lines[] = '• Kilometertarief: '.$this->formatEuro((float) $row['price_per_km']).' per km';
        }

        if ($this->hasAmount($row, 'price_per_min')) {
            $perMin = (float) $row['price_per_min'];
            $lines[] = '• Minuuttarief: '.$this->formatEuro($perMin).' per minuut';
            $lines[] = '• Wachttarief: '.$this->formatEuro($perMin * 60).' per uur';
        }

        if ($this->hasAmount($row, 'min_fare')) {
            $lines[] = '• Minimum ritprijs: '.$this->formatEuro((float) $row['min_fare']);
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortRows(array $rows): array
    {
        usort($rows, function (array $a, array $b): int {
            $order = ['1-4' => 0, '5-8' => 1];
            $aKey = (string) ($a['person_range'] ?? '');
            $bKey = (string) ($b['person_range'] ?? '');

            return ($order[$aKey] ?? 99) <=> ($order[$bKey] ?? 99);
        });

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function hasAmount(array $row, string $key): bool
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return false;
        }

        return (float) $row[$key] > 0;
    }

    private function formatEuro(float $amount): string
    {
        return '€'.number_format($amount, 2, ',', '.');
    }
}
