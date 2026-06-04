<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use Carbon\CarbonInterface;

class TaxiBookingSummaryText
{
    /**
     * @param  array{stopovers?: list<string>, return_at?: string|null, section_config?: array<string, mixed>}  $context
     */
    public function build(RideRequest $ride, array $context = []): string
    {
        $step = is_array($ride->booking_payload['step_data'] ?? null)
            ? $ride->booking_payload['step_data']
            : [];
        $selected = is_array($ride->selected_offer_payload) ? $ride->selected_offer_payload : [];
        $sectionConfig = is_array($context['section_config'] ?? null) ? $context['section_config'] : [];

        $stopovers = array_values(array_filter(array_map(
            fn ($s) => is_string($s) ? trim($s) : '',
            $context['stopovers'] ?? []
        )));
        if ($stopovers === []) {
            $stopovers = $ride->resolveStopoverAddresses();
        }

        $lines = [
            'Nieuwe taxiboeking',
            'Naam: '.($ride->customer_name ?: '—'),
            'Telefoon: '.($ride->customer_phone ?: '—'),
            'E-mail: '.($ride->customer_email ?: '—'),
            'Ophalen: '.($ride->pickup_address ?: '—'),
            'Afzetten: '.($ride->dropoff_address ?: '—'),
            'Datum/tijd: '.$this->formatDateTimeNl($ride->pickup_at),
            'Passagiers: '.(string) ($ride->passengers ?? 1),
            'Bagage: '.$this->baggageSummary($step, $sectionConfig),
        ];

        if ($stopovers !== []) {
            $lines[] = 'Tussenstops: '.implode(' -> ', $stopovers);
        }

        if (! empty($step['return_trip'])) {
            $returnAt = $context['return_at'] ?? null;
            $lines[] = 'Retour: '.($returnAt
                ? 'Ja ('.$this->formatDateTimeString((string) $returnAt).')'
                : 'Ja');
        }

        if (! empty($selected['title']) || array_key_exists('price', $selected)) {
            $lines[] = 'Aanbieding: '.((string) ($selected['title'] ?? '—'));
            if (isset($selected['price']) && is_numeric($selected['price'])) {
                $lines[] = 'Prijsindicatie: € '.number_format((float) $selected['price'], 2, ',', '.');
            }
        }

        if ($ride->id) {
            $lines[] = 'Referentie: rit #'.$ride->id;
        }

        $remarks = trim((string) ($ride->customer_note ?? ($step['remarks'] ?? '')));
        if ($remarks !== '') {
            $lines[] = 'Opmerking: '.$remarks;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $sectionConfig
     */
    private function baggageSummary(array $step, array $sectionConfig): string
    {
        $itemMap = [];
        foreach (['baggage_items', 'special_items'] as $listKey) {
            foreach ($sectionConfig[$listKey] ?? [] as $item) {
                if (! is_array($item) || empty($item['key'])) {
                    continue;
                }
                $itemMap[(string) $item['key']] = (string) ($item['title'] ?? $item['key']);
            }
        }

        $rows = [];
        foreach (['baggage', 'special_baggage'] as $bagKey) {
            $bag = is_array($step[$bagKey] ?? null) ? $step[$bagKey] : [];
            foreach ($bag as $key => $qty) {
                $count = (int) $qty;
                if ($count <= 0) {
                    continue;
                }
                $label = $itemMap[(string) $key] ?? (string) $key;
                $rows[] = $label.' x '.$count;
            }
        }

        return $rows !== [] ? implode(', ', $rows) : 'Geen';
    }

    private function formatDateTimeNl(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i');
        }

        return $this->formatDateTimeString(is_string($value) ? $value : '');
    }

    private function formatDateTimeString(string $value): string
    {
        $trim = trim($value);
        if ($trim === '') {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($trim)
                ->timezone(config('app.timezone', 'Europe/Amsterdam'))
                ->format('d-m-Y H:i');
        } catch (\Throwable) {
            return $trim;
        }
    }
}
