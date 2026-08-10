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
        return $this->buildSelected($ride, [
            'customer_name',
            'customer_phone',
            'customer_email',
            'pickup_address',
            'dropoff_address',
            'pickup_at',
            'passengers',
            'baggage',
            'stopovers',
            'return_trip',
            'offer',
            'price',
            'reference',
            'remarks',
        ], $context, true);
    }

    /**
     * Bouw een samenvatting met alleen de gekozen velden (volgorde = checkbox-volgorde).
     *
     * @param  list<string>  $fields
     * @param  array{stopovers?: list<string>, return_at?: string|null, section_config?: array<string, mixed>}  $context
     */
    public function buildSelected(RideRequest $ride, array $fields, array $context = [], bool $includeTitle = false): string
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

        $lines = [];
        if ($includeTitle) {
            $lines[] = 'Nieuwe taxiboeking';
        }

        foreach ($fields as $field) {
            $line = match ($field) {
                'reference' => $ride->id ? 'Referentie: rit #'.$ride->id : null,
                'customer_name' => 'Naam: '.($ride->customer_name ?: '—'),
                'customer_phone' => 'Telefoon: '.($ride->customer_phone ?: '—'),
                'customer_email' => 'E-mail: '.($ride->customer_email ?: '—'),
                'pickup_address' => 'Ophalen: '.($ride->pickup_address ?: '—'),
                'dropoff_address' => 'Afzetten: '.($ride->dropoff_address ?: '—'),
                'pickup_at' => 'Datum/tijd: '.$this->formatDateTimeNl($ride->pickup_at),
                'passengers' => 'Passagiers: '.(string) ($ride->passengers ?? 1),
                'baggage' => 'Bagage: '.$this->baggageSummary($step, $sectionConfig),
                'stopovers' => $stopovers !== []
                    ? 'Tussenstops: '.implode(' -> ', $stopovers)
                    : null,
                'return_trip' => ! empty($step['return_trip'])
                    ? 'Retour: '.(($context['return_at'] ?? null)
                        ? 'Ja ('.$this->formatDateTimeString((string) $context['return_at']).')'
                        : 'Ja')
                    : null,
                'offer' => (! empty($selected['title']) || array_key_exists('price', $selected))
                    ? 'Aanbieding/voertuig: '.((string) ($selected['title'] ?? '—'))
                    : null,
                'price' => (isset($selected['price']) && is_numeric($selected['price']))
                    ? 'Prijsindicatie: € '.number_format((float) $selected['price'], 2, ',', '.')
                    : null,
                'remarks' => (($remarks = trim((string) ($ride->customer_note ?? ($step['remarks'] ?? '')))) !== '')
                    ? 'Opmerking: '.$remarks
                    : null,
                default => null,
            };

            if (is_string($line) && $line !== '') {
                $lines[] = $line;
            }
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

    public function formatDateTimeNl(mixed $value): string
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
