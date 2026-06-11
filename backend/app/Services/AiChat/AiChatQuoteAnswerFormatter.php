<?php

namespace App\Services\AiChat;

use Illuminate\Support\Carbon;

final class AiChatQuoteAnswerFormatter
{
    /**
     * @param  array<string, mixed>  $session
     */
    public function formatQuote(array $session, array $quoteData, string $bookingUrl): string
    {
        $offers = is_array($quoteData['offers'] ?? null) ? $quoteData['offers'] : [];
        if ($offers === []) {
            return 'Ik kon geen tarief berekenen voor deze rit. Probeer het via onze [boekingsmodule]('.$bookingUrl.').';
        }

        usort($offers, fn (array $a, array $b) => ((float) ($a['price'] ?? 0)) <=> ((float) ($b['price'] ?? 0)));
        $cheapest = $offers[0];
        $price = (float) ($cheapest['price'] ?? 0);
        $title = trim((string) ($cheapest['title'] ?? 'Standaard'));
        $distanceKm = round(((int) ($quoteData['distance_meters'] ?? 0)) / 1000, 1);
        $durationMin = max(1, (int) round(((int) ($quoteData['duration_seconds'] ?? 0)) / 60));
        $pickupAt = $this->formatPickupAt((string) ($session['pickup_at'] ?? ''));
        $isBooking = ($session['flow'] ?? 'quote') === 'booking';

        $lines = [
            $isBooking ? 'Je rit staat klaar om te boeken:' : 'Dit is je prijsindicatie:',
            'Van: '.trim((string) ($session['pickup_address'] ?? '')),
            'Naar: '.trim((string) ($session['dropoff_address'] ?? '')),
            'Ophaalmoment: '.$pickupAt,
            'Personen: '.(int) ($session['passengers'] ?? 1),
            'Bagage: '.$this->formatBaggageSummary($session),
            'Afstand: ca. '.$distanceKm.' km ('.$durationMin.' min)',
            'Tarief: '.$title.' — € '.$this->formatMoney($price),
        ];

        $remarks = trim((string) ($session['remarks'] ?? ''));
        if ($remarks !== '') {
            $lines[] = 'Opmerkingen: '.$remarks;
        }

        if ($isBooking && trim((string) ($session['first_name'] ?? '')) !== '') {
            $lines[] = 'Contact: '.trim((string) ($session['first_name'] ?? '')).' '.trim((string) ($session['last_name'] ?? ''));
            $lines[] = 'Telefoon: '.trim((string) ($session['phone'] ?? ''));
            $email = trim((string) ($session['email'] ?? ''));
            if ($email !== '') {
                $lines[] = 'E-mail: '.$email;
            }
        }

        if (count($offers) > 1) {
            $lines[] = 'Andere opties:';
            foreach (array_slice($offers, 1, 3) as $offer) {
                $lines[] = '- '.trim((string) ($offer['title'] ?? 'Optie')).': € '.$this->formatMoney((float) ($offer['price'] ?? 0));
            }
        }

        $lines[] = '';
        $lines[] = $isBooking
            ? 'Klik hier om je rit af te ronden: [Boek deze rit]('.$bookingUrl.')'
            : 'Wil je direct boeken? [Boek deze rit]('.$bookingUrl.')';

        return implode("\n", $lines);
    }

    public function questionForStep(string $step, array $session): string
    {
        $suggestedPickup = trim((string) ($session['suggested_pickup'] ?? ''));
        $suggestedDropoff = trim((string) ($session['suggested_dropoff'] ?? ''));

        return match ($step) {
            'pickup' => $suggestedPickup !== ''
                ? 'Bevestig je ophaaladres via het veld hieronder (vooringevuld: '.$suggestedPickup.'). Kies een suggestie uit Google.'
                : 'Vanaf welk adres word je opgehaald? Kies een adres uit de suggesties.',
            'dropoff' => $suggestedDropoff !== ''
                ? 'Bevestig je bestemming via het veld hieronder (vooringevuld: '.$suggestedDropoff.'). Kies een suggestie uit Google.'
                : 'Wat is je bestemming? Kies een adres uit de suggesties.',
            'passengers' => 'Met hoeveel personen reizen jullie?',
            'baggage' => 'Kies je bagage en geef per type het aantal door. Geen bagage? Laat alles op 0 staan en bevestig.',
            'pickup_at' => 'Wanneer moeten we je ophalen? Kies hieronder datum en tijd.',
            'remarks' => 'Zijn er nog opmerkingen of bijzonderheden die we moeten weten? (bijv. rolstoel, kinderzitje). Laat leeg als er niets is.',
            'first_name' => 'Wat is je voornaam?',
            'last_name' => 'Wat is je achternaam?',
            'phone' => 'Op welk telefoonnummer kunnen we je bereiken?',
            'email' => 'Wat is je e-mailadres?',
            default => 'Kun je dat nog eens specificeren?',
        };
    }

    /**
     * @param  array<string, mixed>  $session
     */
    public function introForSession(array $session): string
    {
        $isBooking = ($session['flow'] ?? 'quote') === 'booking';
        $parts = [$isBooking
            ? 'Ik help je graag met het boeken van een rit.'
            : 'Ik help je graag met een prijsindicatie.'];

        $suggestedDropoff = trim((string) ($session['suggested_dropoff'] ?? ''));
        $suggestedPickup = trim((string) ($session['suggested_pickup'] ?? ''));

        if ($suggestedDropoff !== '') {
            $parts[] = 'Bestemming: '.$suggestedDropoff.'.';
        }

        if ($suggestedPickup !== '') {
            $parts[] = 'Vertrek: '.$suggestedPickup.'.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    public function invalidAnswerForStep(string $step, array $session = []): string
    {
        return match ($step) {
            'passengers' => 'Geef het aantal personen als getal (bijv. 2).',
            'baggage' => 'Bevestig je bagagekeuze met de knop hieronder (0 is ook goed).',
            'pickup_at' => 'Kies een geldige datum en tijd in de toekomst.',
            'remarks' => 'Typ je opmerking of laat het veld leeg als er niets is.',
            'first_name', 'last_name' => 'Vul je voor- of achternaam in (minimaal 2 tekens).',
            'phone' => 'Vul een geldig telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'email' => 'Vul een geldig e-mailadres in.',
            'pickup', 'dropoff' => 'Kies een adres uit de Google-suggesties en klik op «Adres bevestigen».',
            default => 'Selecteer een adres uit de suggesties of kies een herkenbaar adres.',
        };
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{type: string, step: string, placeholder?: string, min?: string|int, max?: int, prefill?: string, items?: array<int, array<string, mixed>>, special_items?: array<int, array<string, mixed>>}|null
     */
    public function inputSpecForStep(?string $step, array $session = []): ?array
    {
        if ($step === null || $step === '') {
            return null;
        }

        return match ($step) {
            'pickup' => array_filter([
                'type' => 'address',
                'step' => 'pickup',
                'placeholder' => 'Zoek ophaaladres…',
                'prefill' => trim((string) ($session['suggested_pickup'] ?? '')) ?: null,
            ]),
            'dropoff' => array_filter([
                'type' => 'address',
                'step' => 'dropoff',
                'placeholder' => 'Zoek bestemming…',
                'prefill' => trim((string) ($session['suggested_dropoff'] ?? '')) ?: null,
            ]),
            'passengers' => [
                'type' => 'number',
                'step' => 'passengers',
                'placeholder' => 'Aantal personen',
                'min' => 1,
                'max' => 8,
            ],
            'baggage' => [
                'type' => 'baggage',
                'step' => 'baggage',
                'items' => $this->normalizeBaggageItems($session['baggage_items'] ?? []),
                'special_items' => $this->normalizeBaggageItems($session['special_items'] ?? []),
            ],
            'pickup_at' => [
                'type' => 'datetime',
                'step' => 'pickup_at',
                'min' => now()->format('Y-m-d\TH:i'),
            ],
            'remarks' => [
                'type' => 'text',
                'step' => 'remarks',
                'placeholder' => 'Bijv. rolstoel, kinderzitje (optioneel)',
                'required' => false,
            ],
            'first_name' => [
                'type' => 'text',
                'step' => 'first_name',
                'placeholder' => 'Voornaam',
                'inputType' => 'text',
                'autocomplete' => 'given-name',
                'required' => true,
            ],
            'last_name' => [
                'type' => 'text',
                'step' => 'last_name',
                'placeholder' => 'Achternaam',
                'inputType' => 'text',
                'autocomplete' => 'family-name',
                'required' => true,
            ],
            'phone' => [
                'type' => 'text',
                'step' => 'phone',
                'placeholder' => 'Bijv. 0612345678',
                'inputType' => 'tel',
                'inputMode' => 'tel',
                'autocomplete' => 'tel',
                'required' => true,
            ],
            'email' => [
                'type' => 'text',
                'step' => 'email',
                'placeholder' => 'E-mailadres',
                'inputType' => 'email',
                'inputMode' => 'email',
                'autocomplete' => 'email',
                'required' => true,
            ],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function formatBaggageSummary(array $session): string
    {
        $labels = [];
        foreach ($this->normalizeBaggageItems($session['baggage_items'] ?? []) as $item) {
            $key = (string) ($item['key'] ?? '');
            $qty = (int) (($session['baggage'][$key] ?? 0));
            if ($qty > 0) {
                $labels[] = trim((string) ($item['title'] ?? $key)).': '.$qty;
            }
        }
        foreach ($this->normalizeBaggageItems($session['special_items'] ?? []) as $item) {
            $key = (string) ($item['key'] ?? '');
            $qty = (int) (($session['special_baggage'][$key] ?? 0));
            if ($qty > 0) {
                $labels[] = trim((string) ($item['title'] ?? $key)).': '.$qty;
            }
        }

        return $labels !== [] ? implode(', ', $labels) : 'Geen';
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{key: string, title: string, subtitle?: string, max: int}>
     */
    private function normalizeBaggageItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = trim((string) ($item['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $normalized[] = [
                'key' => $key,
                'title' => trim((string) ($item['title'] ?? $key)),
                'subtitle' => trim((string) ($item['subtitle'] ?? '')),
                'max' => max(0, (int) ($item['max_qty'] ?? 6)),
            ];
        }

        return $normalized;
    }

    private function formatPickupAt(string $value): string
    {
        if ($value === '') {
            return '—';
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone'))->format('d-m-Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }
}
