<?php

namespace App\Services;

use App\Models\Module;
use App\Modules\TaxiRoyaal\Models\DefaultRate;
use App\Modules\TaxiRoyaal\Models\Vehicle;
use Carbon\Carbon;

class TaxiRoyaalBookingPricingService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    public function getDefaultSectionConfig(): array
    {
        return [
            'title' => 'Boek eenvoudig je taxirit',
            'subtitle' => '',
            'step_labels' => [
                'step1' => 'Bagage',
                'step2' => 'Aanbiedingen',
                'step3' => 'Reisgegevens',
                'step4' => 'Contactgegevens',
                'step5' => 'Bevestiging',
            ],
            'step_order' => ['trip', 'baggage', 'offers', 'contact', 'confirm'],
            'style' => [
                'primary_color' => '#5b21b6',
                'active_tab_color' => '#5b21b6',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'card_bg_color' => '#ffffff',
                'border_radius' => 12,
                'container_max_width' => '100%',
                'align' => 'center',
            ],
            'logic' => [
                'min_passengers' => 1,
                'max_passengers' => 8,
                'default_passengers' => 1,
                'return_enabled_by_default' => false,
                'skip_baggage_step' => false,
                'max_stopovers' => 3,
                'return_price_multiplier' => 2.0,
                'offer_display_mode' => 'vehicle',
                'person_range_base_price_multiplier' => 1.0,
                'person_range_base_old_price_multiplier' => 1.2,
                'use_evening_night_tariff' => true,
            ],
            'texts' => [
                'pickup_placeholder' => 'straatnaam met huisnummer',
                'dropoff_placeholder' => 'straatnaam met huisnummer',
                'pickup_datetime_placeholder' => 'Selecteer datum en tijd',
                'return_datetime_placeholder' => 'Selecteer datum en tijd',
                'person_range_feature_text' => 'Tarief op basis van aantal personen',
                'remarks_placeholder' => 'Opmerking(en)',
                'offer_button_text' => 'Selecteer',
                'submit_button_text' => 'Boeking versturen',
                'success_message' => 'Bedankt! Je boeking is ontvangen.',
            ],
            'baggage_items' => [
                ['key' => 'large', 'title' => 'Grote ruimbagage', 'subtitle' => '85cm x 55cm x 35cm', 'icon' => 'ki-filled ki-briefcase', 'price' => 0, 'max_qty' => 6],
                ['key' => 'small', 'title' => 'Kleine ruimbagage', 'subtitle' => '55cm x 45cm x 25cm', 'icon' => 'ki-filled ki-bag', 'price' => 0, 'max_qty' => 6],
                ['key' => 'hand', 'title' => 'Handbagage', 'subtitle' => 'Handtas, rugzak, etc.', 'icon' => 'ki-filled ki-handcart', 'price' => 0, 'max_qty' => 6],
            ],
            'special_items' => [
                ['key' => 'wheelchair', 'title' => 'Opvouwbare rolstoel', 'icon' => 'ki-filled ki-wheelchair', 'price' => 0, 'max_qty' => 4],
                ['key' => 'pets', 'title' => 'Huisdieren', 'icon' => 'ki-filled ki-heart', 'price' => 0, 'max_qty' => 4],
                ['key' => 'winter', 'title' => 'Wintersport', 'icon' => 'ki-filled ki-snowflake', 'price' => 2.5, 'max_qty' => 4],
                ['key' => 'golf', 'title' => 'Golftas', 'icon' => 'ki-filled ki-golf', 'price' => 2.5, 'max_qty' => 4],
            ],
            'offers' => [],
            'maps' => [
                'country' => 'nl',
                'language' => 'nl',
            ],
        ];
    }

    public function mergeSectionConfig(array $raw): array
    {
        $defaults = $this->getDefaultSectionConfig();
        $section = $defaults;
        $section['title'] = isset($raw['title']) && trim((string) $raw['title']) !== '' ? trim((string) $raw['title']) : $defaults['title'];
        $section['subtitle'] = isset($raw['subtitle']) ? trim((string) $raw['subtitle']) : '';

        $stepLabels = is_array($raw['step_labels'] ?? null) ? $raw['step_labels'] : [];
        foreach ($defaults['step_labels'] as $k => $v) {
            $value = isset($stepLabels[$k]) ? trim((string) $stepLabels[$k]) : $v;
            $section['step_labels'][$k] = $value !== '' ? $value : $v;
        }
        $allowedStepKeys = ['trip', 'baggage', 'offers', 'contact', 'confirm'];
        $rawStepOrder = is_array($raw['step_order'] ?? null) ? array_values($raw['step_order']) : [];
        $normalizedOrder = [];
        foreach ($rawStepOrder as $stepKey) {
            $stepKey = trim((string) $stepKey);
            if ($stepKey !== '' && in_array($stepKey, $allowedStepKeys, true) && ! in_array($stepKey, $normalizedOrder, true)) {
                $normalizedOrder[] = $stepKey;
            }
        }
        foreach ($defaults['step_order'] as $defaultStepKey) {
            if (! in_array($defaultStepKey, $normalizedOrder, true)) {
                $normalizedOrder[] = $defaultStepKey;
            }
        }
        $section['step_order'] = array_slice($normalizedOrder, 0, 5);

        $style = is_array($raw['style'] ?? null) ? $raw['style'] : [];
        foreach ($defaults['style'] as $k => $v) {
            $section['style'][$k] = isset($style[$k]) ? trim((string) $style[$k]) : $v;
        }
        $section['style']['border_radius'] = max(0, min(40, (int) ($section['style']['border_radius'] ?? $defaults['style']['border_radius'])));
        $width = trim((string) ($section['style']['container_max_width'] ?? ''));
        $section['style']['container_max_width'] = preg_match('/^(100|[1-9]\d?)%$/', $width) ? $width : $defaults['style']['container_max_width'];
        $align = trim((string) ($section['style']['align'] ?? $defaults['style']['align']));
        $section['style']['align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : $defaults['style']['align'];

        $logic = is_array($raw['logic'] ?? null) ? $raw['logic'] : [];
        $section['logic']['min_passengers'] = max(1, min(8, (int) ($logic['min_passengers'] ?? $defaults['logic']['min_passengers'])));
        $section['logic']['max_passengers'] = max($section['logic']['min_passengers'], min(20, (int) ($logic['max_passengers'] ?? $defaults['logic']['max_passengers'])));
        $section['logic']['default_passengers'] = max($section['logic']['min_passengers'], min($section['logic']['max_passengers'], (int) ($logic['default_passengers'] ?? $defaults['logic']['default_passengers'])));
        $section['logic']['return_enabled_by_default'] = ! empty($logic['return_enabled_by_default']);
        $section['logic']['skip_baggage_step'] = ! empty($logic['skip_baggage_step']);
        $section['logic']['max_stopovers'] = max(0, min(6, (int) ($logic['max_stopovers'] ?? $defaults['logic']['max_stopovers'])));
        $section['logic']['return_price_multiplier'] = max(1, min(3, (float) ($logic['return_price_multiplier'] ?? $defaults['logic']['return_price_multiplier'])));
        $offerDisplayMode = trim((string) ($logic['offer_display_mode'] ?? $defaults['logic']['offer_display_mode']));
        $section['logic']['offer_display_mode'] = in_array($offerDisplayMode, ['vehicle', 'person_range'], true) ? $offerDisplayMode : $defaults['logic']['offer_display_mode'];
        $section['logic']['use_evening_night_tariff'] = ! empty($logic['use_evening_night_tariff']);
        $section['logic']['person_range_base_price_multiplier'] = max(0.1, min(5, (float) ($logic['person_range_base_price_multiplier'] ?? $defaults['logic']['person_range_base_price_multiplier'])));
        $section['logic']['person_range_base_old_price_multiplier'] = max(1, min(5, (float) ($logic['person_range_base_old_price_multiplier'] ?? $defaults['logic']['person_range_base_old_price_multiplier'])));

        $texts = is_array($raw['texts'] ?? null) ? $raw['texts'] : [];
        foreach ($defaults['texts'] as $k => $v) {
            $section['texts'][$k] = isset($texts[$k]) ? trim((string) $texts[$k]) : $v;
            if ($section['texts'][$k] === '') {
                $section['texts'][$k] = $v;
            }
        }

        $section['baggage_items'] = $this->normalizePricedItems($raw['baggage_items'] ?? [], $defaults['baggage_items']);
        $section['special_items'] = $this->normalizePricedItems($raw['special_items'] ?? [], $defaults['special_items']);
        $section['offers'] = $this->normalizeOffers($raw['offers'] ?? []);
        $section['maps'] = [
            'country' => isset($raw['maps']['country']) ? strtolower(trim((string) $raw['maps']['country'])) : $defaults['maps']['country'],
            'language' => isset($raw['maps']['language']) ? strtolower(trim((string) $raw['maps']['language'])) : $defaults['maps']['language'],
        ];
        if ($section['maps']['country'] === '') {
            $section['maps']['country'] = $defaults['maps']['country'];
        }
        if ($section['maps']['language'] === '') {
            $section['maps']['language'] = $defaults['maps']['language'];
        }

        return $section;
    }

    public function buildQuotes(array $sectionConfig, array $input): array
    {
        $passengers = max(
            (int) ($sectionConfig['logic']['min_passengers'] ?? 1),
            min(
                (int) ($sectionConfig['logic']['max_passengers'] ?? 8),
                (int) ($input['passengers'] ?? ($sectionConfig['logic']['default_passengers'] ?? 1))
            )
        );
        $distanceMeters = max(0, (int) ($input['distance_meters'] ?? 0));
        $durationSeconds = max(0, (int) ($input['duration_seconds'] ?? 0));
        $returnTrip = ! empty($input['return_trip']);
        $pickupAt = isset($input['pickup_at']) && trim((string) $input['pickup_at']) !== '' ? (string) $input['pickup_at'] : null;
        $waitingMinutes = max(0, (float) ($input['waiting_minutes'] ?? 0));
        $range = $this->resolvePersonRangeForPassengers($passengers);
        $extraTotal = $this->calculateExtraCosts($sectionConfig, $input);

        $defaultRates = $this->getDefaultRates($range);
        $vehicleMap = $this->getActiveVehiclesById($range);
        $allVehicleMap = $this->getAllActiveVehiclesById();
        $offers = is_array($sectionConfig['offers'] ?? null) ? $sectionConfig['offers'] : [];
        if (empty($offers)) {
            $offers = $this->buildDefaultOffersFromVehicles(array_values($vehicleMap));
        }
        $offerDisplayMode = (string) ($sectionConfig['logic']['offer_display_mode'] ?? 'vehicle');
        if (! in_array($offerDisplayMode, ['vehicle', 'person_range'], true)) {
            $offerDisplayMode = 'vehicle';
        }
        $useEveningNightTariff = ! empty($sectionConfig['logic']['use_evening_night_tariff'] ?? true);

        $resultOffers = [];
        if ($offerDisplayMode === 'person_range') {
            $matchingOffers = array_values(array_filter($offers, fn (array $offer) => $this->offerMatchesPersonRange($offer, $range)));
            $personRangeVehicle = null;
            if (! empty($vehicleMap)) {
                $personRangeVehicle = collect($vehicleMap)->sortBy('name')->first();
            } elseif (! empty($allVehicleMap)) {
                $personRangeVehicle = collect($allVehicleMap)->sortBy('name')->first();
            }
            $baseRate = $this->resolveRateForVehicle(null, $defaultRates);
            $baseFare = $this->calculateFareFromRate(
                $distanceMeters,
                $durationSeconds,
                $baseRate,
                $returnTrip,
                (float) ($sectionConfig['logic']['return_price_multiplier'] ?? 2.0),
                $pickupAt,
                $waitingMinutes,
                $useEveningNightTariff
            );
            $baseMultiplier = max(0.1, (float) ($sectionConfig['logic']['person_range_base_price_multiplier'] ?? 1.0));
            $baseOldMultiplier = max(1.0, (float) ($sectionConfig['logic']['person_range_base_old_price_multiplier'] ?? 1.2));
            $total = round(($baseFare * $baseMultiplier) + $extraTotal, 2);
            $baseOldTotal = round($total * $baseOldMultiplier, 2);
            $rangeTitle = DefaultRate::formatPersonRangeLabel($range);
            $personRangeImageUrl = $this->resolvePersonRangeImageUrl($vehicleMap) ?: $this->resolvePersonRangeImageUrl($allVehicleMap);
            $resultOffers[] = [
                'id' => 'person_range_'.str_replace('-', '_', $range),
                'title' => $rangeTitle,
                'badge' => $rangeTitle,
                'button_text' => $sectionConfig['texts']['offer_button_text'] ?? 'Selecteer',
                'features' => [trim((string) ($sectionConfig['texts']['person_range_feature_text'] ?? 'Tarief op basis van aantal personen')) ?: 'Tarief op basis van aantal personen'],
                'vehicle_id' => $personRangeVehicle?->id,
                'vehicle_name' => $personRangeVehicle?->name,
                'image_url' => $personRangeImageUrl,
                'price' => $total,
                'old_price' => $baseOldTotal > $total ? $baseOldTotal : null,
                'currency' => 'EUR',
                'person_range' => $range,
            ];

            foreach ($matchingOffers as $idx => $offer) {
                $vehicleId = isset($offer['vehicle_id']) && is_numeric($offer['vehicle_id']) ? (int) $offer['vehicle_id'] : null;
                $vehicleForRate = $vehicleId !== null && isset($vehicleMap[$vehicleId]) ? $vehicleMap[$vehicleId] : null;
                $vehicleForDisplay = $vehicleId !== null && isset($allVehicleMap[$vehicleId]) ? $allVehicleMap[$vehicleId] : $vehicleForRate;

                $rate = $this->resolveRateForVehicle($vehicleForRate, $defaultRates);
                $offerBaseFare = $this->calculateFareFromRate(
                    $distanceMeters,
                    $durationSeconds,
                    $rate,
                    $returnTrip,
                    (float) ($sectionConfig['logic']['return_price_multiplier'] ?? 2.0),
                    $pickupAt,
                    $waitingMinutes,
                    $useEveningNightTariff
                );
                $multiplier = max(0.1, (float) ($offer['price_multiplier'] ?? 1.0));
                $surcharge = max(0, (float) ($offer['fixed_surcharge'] ?? 0));
                $total = round(($offerBaseFare * $multiplier) + $surcharge + $extraTotal, 2);
                $oldMultiplier = max(1.0, (float) ($offer['old_price_multiplier'] ?? 1.0));
                $oldTotal = round($total * $oldMultiplier, 2);
                $id = trim((string) ($offer['id'] ?? ('person_offer_'.($idx + 1))));
                $rawTitle = isset($offer['title']) ? trim((string) $offer['title']) : '';
                $rawBadge = isset($offer['badge']) ? trim((string) $offer['badge']) : '';
                $features = [];
                if (isset($offer['features']) && is_array($offer['features'])) {
                    $features = array_values(array_filter(array_map(static fn ($feature) => trim((string) $feature), $offer['features']), static fn ($feature) => $feature !== ''));
                }
                if (empty($features)) {
                    $features = $this->resolveVehicleFeatures($vehicleForDisplay);
                }

                $resultOffers[] = [
                    'id' => $id !== '' ? $id : ('person_offer_'.($idx + 1)),
                    'title' => $rawTitle !== '' ? $rawTitle : ($vehicleForDisplay?->name ?? ('Aanbieding '.($idx + 1))),
                    'badge' => $rawBadge,
                    'button_text' => $offer['button_text'] ?? ($sectionConfig['texts']['offer_button_text'] ?? 'Selecteer'),
                    'features' => $features,
                    'vehicle_id' => $vehicleForDisplay?->id,
                    'vehicle_name' => $vehicleForDisplay?->name,
                    'image_url' => $this->resolveVehicleImageUrlForOffer($vehicleForDisplay),
                    'price' => $total,
                    'old_price' => $oldTotal > $total ? $oldTotal : null,
                    'currency' => 'EUR',
                    'person_range' => $range,
                ];
            }
        } else {
            foreach ($offers as $idx => $offer) {
                if (! $this->offerMatchesPersonRange($offer, $range)) {
                    continue;
                }
                $vehicleId = isset($offer['vehicle_id']) && is_numeric($offer['vehicle_id']) ? (int) $offer['vehicle_id'] : null;
                $vehicleForRate = $vehicleId !== null && isset($vehicleMap[$vehicleId]) ? $vehicleMap[$vehicleId] : null;
                $vehicleForDisplay = $vehicleId !== null && isset($allVehicleMap[$vehicleId]) ? $allVehicleMap[$vehicleId] : $vehicleForRate;
                $rate = $this->resolveRateForVehicle($vehicleForRate, $defaultRates);
                $baseFare = $this->calculateFareFromRate(
                    $distanceMeters,
                    $durationSeconds,
                    $rate,
                    $returnTrip,
                    (float) ($sectionConfig['logic']['return_price_multiplier'] ?? 2.0),
                    $pickupAt,
                    $waitingMinutes,
                    $useEveningNightTariff
                );
                $multiplier = max(0.1, (float) ($offer['price_multiplier'] ?? 1.0));
                $surcharge = max(0, (float) ($offer['fixed_surcharge'] ?? 0));
                $total = round(($baseFare * $multiplier) + $surcharge + $extraTotal, 2);
                $oldMultiplier = max(1.0, (float) ($offer['old_price_multiplier'] ?? 1.0));
                $oldTotal = round($total * $oldMultiplier, 2);
                $id = trim((string) ($offer['id'] ?? ('offer_'.($idx + 1))));
                $rawTitle = isset($offer['title']) ? trim((string) $offer['title']) : '';
                $rawBadge = isset($offer['badge']) ? trim((string) $offer['badge']) : '';
                $features = [];
                if (isset($offer['features']) && is_array($offer['features'])) {
                    $features = array_values(array_filter(array_map(static fn ($feature) => trim((string) $feature), $offer['features']), static fn ($feature) => $feature !== ''));
                }
                if (empty($features)) {
                    $features = $this->resolveVehicleFeatures($vehicleForDisplay);
                }
                $resultOffers[] = [
                    'id' => $id !== '' ? $id : ('offer_'.($idx + 1)),
                    'title' => $rawTitle !== '' ? $rawTitle : ($vehicleForDisplay?->name ?? ('Aanbieding '.($idx + 1))),
                    'badge' => $rawBadge,
                    'button_text' => $offer['button_text'] ?? ($sectionConfig['texts']['offer_button_text'] ?? 'Selecteer'),
                    'features' => $features,
                    'vehicle_id' => $vehicleForDisplay?->id,
                    'vehicle_name' => $vehicleForDisplay?->name,
                    'image_url' => $this->resolveVehicleImageUrlForOffer($vehicleForDisplay),
                    'price' => $total,
                    'old_price' => $oldTotal > $total ? $oldTotal : null,
                    'currency' => 'EUR',
                    'person_range' => $range,
                ];
            }
        }

        return [
            'passengers' => $passengers,
            'offer_display_mode' => $offerDisplayMode,
            'person_range' => $range,
            'distance_meters' => $distanceMeters,
            'duration_seconds' => $durationSeconds,
            'return_trip' => $returnTrip,
            'extra_total' => round($extraTotal, 2),
            'offers' => $resultOffers,
        ];
    }

    private function normalizePricedItems(mixed $items, array $fallback): array
    {
        if (! is_array($items)) {
            return $fallback;
        }
        $out = [];
        foreach (array_values($items) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                $key = \Illuminate\Support\Str::slug($title, '_');
            }
            $out[] = [
                'key' => $key,
                'title' => $title,
                'subtitle' => isset($row['subtitle']) ? trim((string) $row['subtitle']) : '',
                'icon' => isset($row['icon']) ? trim((string) $row['icon']) : '',
                'price' => max(0, (float) ($row['price'] ?? 0)),
                'max_qty' => max(0, min(20, (int) ($row['max_qty'] ?? 4))),
            ];
        }

        return ! empty($out) ? $out : $fallback;
    }

    private function normalizeOffers(mixed $offers): array
    {
        if (! is_array($offers)) {
            return [];
        }
        $out = [];
        foreach (array_values($offers) as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $features = [];
            if (isset($row['features']) && is_array($row['features'])) {
                foreach ($row['features'] as $feature) {
                    $feature = trim((string) $feature);
                    if ($feature !== '') {
                        $features[] = $feature;
                    }
                }
            } elseif (isset($row['features_text'])) {
                $lines = preg_split('/\R+/', (string) $row['features_text']) ?: [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $features[] = $line;
                    }
                }
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                $id = 'offer_'.($idx + 1);
            }
            $out[] = [
                'id' => $id,
                'title' => $title,
                'badge' => trim((string) ($row['badge'] ?? 'Standaard taxi')),
                'button_text' => trim((string) ($row['button_text'] ?? 'Selecteer')),
                'vehicle_id' => isset($row['vehicle_id']) && $row['vehicle_id'] !== '' && is_numeric($row['vehicle_id']) ? (int) $row['vehicle_id'] : null,
                'person_range' => $this->normalizePersonRange($row['person_range'] ?? null),
                'price_multiplier' => max(0.1, min(5, (float) ($row['price_multiplier'] ?? 1.0))),
                'old_price_multiplier' => max(1, min(5, (float) ($row['old_price_multiplier'] ?? 1.0))),
                'fixed_surcharge' => max(0, (float) ($row['fixed_surcharge'] ?? 0)),
                'features' => $features,
            ];
        }

        return $out;
    }

    private function normalizePersonRange(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }
        if (! preg_match('/^(\d{1,2})\s*-\s*(\d{1,2})$/', $raw, $matches)) {
            return null;
        }
        $start = (int) ($matches[1] ?? 0);
        $end = (int) ($matches[2] ?? 0);
        if ($start <= 0 || $end <= 0 || $start > $end || $end > 50) {
            return null;
        }

        return $start.'-'.$end;
    }

    private function offerMatchesPersonRange(array $offer, string $activeRange): bool
    {
        $offerRange = $this->normalizePersonRange($offer['person_range'] ?? null);
        if ($offerRange === null) {
            return true;
        }

        return $offerRange === $activeRange;
    }

    private function calculateExtraCosts(array $sectionConfig, array $input): float
    {
        $total = 0.0;
        $selectedBaggage = is_array($input['baggage'] ?? null) ? $input['baggage'] : [];
        $selectedSpecial = is_array($input['special_baggage'] ?? null) ? $input['special_baggage'] : [];
        foreach (array_merge($sectionConfig['baggage_items'] ?? [], $sectionConfig['special_items'] ?? []) as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $qty = 0;
            if (array_key_exists($key, $selectedBaggage)) {
                $qty = (int) $selectedBaggage[$key];
            } elseif (array_key_exists($key, $selectedSpecial)) {
                $qty = (int) $selectedSpecial[$key];
            }
            $maxQty = max(0, (int) ($item['max_qty'] ?? 0));
            $qty = max(0, min($maxQty > 0 ? $maxQty : 20, $qty));
            $total += $qty * max(0, (float) ($item['price'] ?? 0));
        }

        return $total;
    }

    private function getDefaultRates(string $personRange): ?DefaultRate
    {
        $conn = $this->getTaxiConnection();
        if ($conn === null) {
            return null;
        }

        return DefaultRate::on($conn)->where('person_range', $personRange)->first()
            ?? DefaultRate::on($conn)->where('person_range', '1-4')->first()
            ?? DefaultRate::on($conn)->get()->sortBy(function (DefaultRate $rate) {
                [$start, $end] = DefaultRate::parseRangeBounds((string) $rate->person_range);

                return ($start * 1000) + $end;
            })->first();
    }

    /**
     * @return array<int, Vehicle>
     */
    private function getActiveVehiclesById(string $personRange): array
    {
        $conn = $this->getTaxiConnection();
        if ($conn === null) {
            return [];
        }

        return Vehicle::on($conn)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (Vehicle $vehicle) => $this->vehicleMatchesRange($vehicle, $personRange))
            ->keyBy('id')
            ->all();
    }

    /**
     * @return array<int, Vehicle>
     */
    private function getAllActiveVehiclesById(): array
    {
        $conn = $this->getTaxiConnection();
        if ($conn === null) {
            return [];
        }

        return Vehicle::on($conn)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('id')
            ->all();
    }

    private function resolvePersonRangeForPassengers(int $passengers): string
    {
        $conn = $this->getTaxiConnection();
        if ($conn === null) {
            return $passengers <= 4 ? '1-4' : '5-8';
        }

        $ranges = DefaultRate::on($conn)->pluck('person_range')->filter()->unique()->values()->all();
        if (empty($ranges)) {
            return $passengers <= 4 ? '1-4' : '5-8';
        }
        usort($ranges, function (string $a, string $b) {
            [$aStart, $aEnd] = DefaultRate::parseRangeBounds($a);
            [$bStart, $bEnd] = DefaultRate::parseRangeBounds($b);

            return ($aStart <=> $bStart) ?: ($aEnd <=> $bEnd);
        });

        foreach ($ranges as $range) {
            if ($this->rangeIncludesPassengers($range, $passengers)) {
                return $range;
            }
        }

        return end($ranges) ?: '1-4';
    }

    private function rangeIncludesPassengers(string $range, int $passengers): bool
    {
        [$start, $end] = DefaultRate::parseRangeBounds($range);

        return $passengers >= $start && $passengers <= $end;
    }

    private function vehicleMatchesRange(Vehicle $vehicle, string $personRange): bool
    {
        $vehicleRange = trim((string) ($vehicle->person_range ?? ''));
        if ($vehicleRange !== '') {
            return $vehicleRange === $personRange;
        }

        // Backward compatibility for older rows without person_range.
        $seats = (int) ($vehicle->seats ?? 0);
        [$start, $end] = DefaultRate::parseRangeBounds($personRange);

        return $seats >= $start && $seats <= $end;
    }

    private function resolveRateForVehicle(?Vehicle $vehicle, ?DefaultRate $defaultRate): array
    {
        $defaultBaseFare = (float) ($defaultRate?->base_fare ?? 0);
        $defaultMinFare = (float) ($defaultRate?->min_fare ?? 0);
        $defaultPerKm = (float) ($defaultRate?->price_per_km ?? 0);
        $defaultPerMin = (float) ($defaultRate?->price_per_min ?? 0);

        $vehicleBaseFare = $vehicle?->base_fare;
        $vehicleMinFare = $vehicle?->min_fare;
        $vehiclePerKm = $vehicle?->price_per_km;
        $vehiclePerMin = $vehicle?->price_per_min;

        return [
            // For vehicle overrides, only use positive values; 0/empty falls back to default rates.
            'base_fare' => ($vehicleBaseFare !== null && (float) $vehicleBaseFare > 0) ? (float) $vehicleBaseFare : $defaultBaseFare,
            'min_fare' => ($vehicleMinFare !== null && (float) $vehicleMinFare > 0) ? (float) $vehicleMinFare : $defaultMinFare,
            'price_per_km' => ($vehiclePerKm !== null && (float) $vehiclePerKm > 0) ? (float) $vehiclePerKm : $defaultPerKm,
            'price_per_min' => ($vehiclePerMin !== null && (float) $vehiclePerMin > 0) ? (float) $vehiclePerMin : $defaultPerMin,
        ];
    }

    private function calculateFareFromRate(
        int $distanceMeters,
        int $durationSeconds,
        array $rate,
        bool $returnTrip,
        float $returnMultiplier,
        ?string $rideDateTime = null,
        float $waitingMinutes = 0,
        bool $useEveningNightTariff = true
    ): float {
        $distanceKm = $distanceMeters / 1000;
        $durationMin = $durationSeconds / 60;
        $nightMultiplier = ($useEveningNightTariff && $this->isNightRide($rideDateTime)) ? 1.2 : 1.0;
        $baseFare = (float) ($rate['base_fare'] ?? 0);
        $pricePerKm = (float) ($rate['price_per_km'] ?? 0) * $nightMultiplier;
        $pricePerMin = (float) ($rate['price_per_min'] ?? 0) * $nightMultiplier;
        $waitPerMin = (((float) ($rate['min_fare'] ?? 0)) / 60) * $nightMultiplier;

        $fare = $baseFare
            + ($distanceKm * $pricePerKm)
            + ($durationMin * $pricePerMin)
            + (max(0, $waitingMinutes) * $waitPerMin);

        if ($returnTrip) {
            $fare *= max(1, $returnMultiplier);
        }

        return round(max(0, $fare), 2);
    }

    private function isNightRide(?string $rideDateTime): bool
    {
        try {
            $dt = $rideDateTime ? Carbon::parse($rideDateTime) : now();
        } catch (\Throwable $e) {
            $dt = now();
        }

        $hour = (int) $dt->format('G');

        return $hour >= 22 || $hour < 6;
    }

    private function resolveVehicleImageUrl(?Vehicle $vehicle): ?string
    {
        if (! $vehicle || ! (bool) ($vehicle->show_photo ?? false) || empty($vehicle->image_url)) {
            return null;
        }
        $displayUrl = $this->websiteBuilder->storageUrlToDisplayUrl(trim((string) $vehicle->image_url));

        return $displayUrl === '' ? null : $displayUrl;
    }

    /**
     * For manually configured offer cards we always show selected vehicle image.
     */
    private function resolveVehicleImageUrlForOffer(?Vehicle $vehicle): ?string
    {
        if (! $vehicle || empty($vehicle->image_url)) {
            return null;
        }
        $displayUrl = $this->websiteBuilder->storageUrlToDisplayUrl(trim((string) $vehicle->image_url));

        return $displayUrl === '' ? null : $displayUrl;
    }

    /**
     * @param  array<int, Vehicle>  $vehicleMap
     */
    private function resolvePersonRangeImageUrl(array $vehicleMap): ?string
    {
        foreach ($vehicleMap as $vehicle) {
            $imageUrl = $this->resolveVehicleImageUrl($vehicle);
            if ($imageUrl !== null) {
                return $imageUrl;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function resolveVehicleFeatures(?Vehicle $vehicle): array
    {
        if (! $vehicle) {
            return ['Altijd een prive-taxi'];
        }

        $notes = trim((string) ($vehicle->notes ?? ''));
        if ($notes === '') {
            return ['Altijd een prive-taxi'];
        }

        $lines = preg_split('/\R+/', $notes) ?: [];
        $features = [];
        foreach ($lines as $line) {
            $normalized = trim((string) $line);
            if ($normalized === '') {
                continue;
            }
            $normalized = ltrim($normalized, "-*• \t");
            $normalized = ltrim($normalized, '✓');
            $normalized = trim($normalized);
            if ($normalized !== '') {
                $features[] = $normalized;
            }
        }

        return ! empty($features) ? array_slice($features, 0, 4) : ['Altijd een prive-taxi'];
    }

    private function getTaxiConnection(): ?string
    {
        if (! Module::where('installed', true)->where('active', true)->whereRaw('LOWER(name) = ?', ['taxiroyaal'])->exists()) {
            return null;
        }
        try {
            return $this->moduleDb->getModuleConnectionName('taxiroyaal');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  array<int, Vehicle>  $vehicles
     * @return array<int, array<string, mixed>>
     */
    private function buildDefaultOffersFromVehicles(array $vehicles): array
    {
        if (empty($vehicles)) {
            return [
                [
                    'id' => 'offer_1',
                    'title' => 'Standaard retour',
                    'badge' => '',
                    'button_text' => 'Selecteer',
                    'vehicle_id' => null,
                    'price_multiplier' => 1.0,
                    'old_price_multiplier' => 1.0,
                    'fixed_surcharge' => 0,
                    'features' => ['Altijd een prive-taxi', 'Bagage gratis mee'],
                ],
            ];
        }
        $defaults = [];
        foreach (array_slice($vehicles, 0, 4) as $index => $vehicle) {
            $defaults[] = [
                'id' => 'offer_'.($index + 1),
                'title' => $vehicle->name,
                'badge' => '',
                'button_text' => 'Selecteer',
                'vehicle_id' => (int) $vehicle->id,
                'price_multiplier' => 1.0,
                'old_price_multiplier' => 1.0,
                'fixed_surcharge' => 0,
                'features' => $this->resolveVehicleFeatures($vehicle),
            ];
        }

        return $defaults;
    }
}
