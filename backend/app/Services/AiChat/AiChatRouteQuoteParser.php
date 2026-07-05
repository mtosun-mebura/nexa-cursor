<?php

namespace App\Services\AiChat;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Parseert route-informatie en antwoorden in een offerte-gesprek.
 */
final class AiChatRouteQuoteParser
{
    /**
     * @return array{pickup_address: ?string, dropoff_address: ?string}
     */
    public function parseRouteFromQuestion(string $message): array
    {
        if (preg_match('/\b(?:van|from)\s+(.+?)\s+(?:naar|to)\s+(.+)$/iu', $message, $matches)) {
            return [
                'pickup_address' => $this->cleanAddress($matches[1]),
                'dropoff_address' => $this->cleanAddress($matches[2]),
            ];
        }

        if (preg_match('/\b(?:boek(?:\s+(?:een|een\s+))?(?:rit|taxirit)|rit\s+boeken)\s+(?:naar|to)\s+(.+)$/iu', $message, $matches)) {
            return [
                'pickup_address' => null,
                'dropoff_address' => $this->cleanAddress($matches[1]),
            ];
        }

        if (preg_match('/\b(?:ik\s+)?(?:wil|moet|ga|wilt)\s+(?:graag\s+)?(?:naar|to)\s+(.+)$/iu', $message, $matches)) {
            return [
                'pickup_address' => null,
                'dropoff_address' => $this->cleanAddress($matches[1]),
            ];
        }

        if (preg_match('/\b(?:kan|kun)\s+ik\s+(?:ook\s+)?(?:naar|to)\s+(.+)$/iu', $message, $matches)) {
            return [
                'pickup_address' => null,
                'dropoff_address' => $this->cleanAddress($matches[1]),
            ];
        }

        if (preg_match('/\b(?:rit\s+)?naar\s+(.+)$/iu', $message, $matches)) {
            return [
                'pickup_address' => null,
                'dropoff_address' => $this->cleanAddress($matches[1]),
            ];
        }

        if (preg_match('/\b(?:rit\s+)?van\s+(.+?)\s+(?:naar|to)\s+(.+)$/iu', $message, $matches)) {
            return [
                'pickup_address' => $this->cleanAddress($matches[1]),
                'dropoff_address' => $this->cleanAddress($matches[2]),
            ];
        }

        return [
            'pickup_address' => null,
            'dropoff_address' => null,
        ];
    }

    public function isBookingRequest(string $message): bool
    {
        $text = $this->normalize($message);

        if ($this->matchesAny($text, [
            'boek een rit', 'boek een taxirit', 'rit boeken', 'boeken een rit',
            'wil een rit boeken', 'kan ik een rit boeken', 'boek mijn rit',
            'taxi boeken', 'taxirit boeken', 'rit reserveren', 'taxi reserveren',
            'wil reserveren', 'graag reserveren', 'kan ik reserveren',
            'wil boeken', 'graag boeken', 'kan ik boeken',
            'een rit reserveren', 'een taxi boeken', 'een taxirit boeken',
        ])) {
            return true;
        }

        if (preg_match('/\b(?:boeken|reserveren)\b/u', $text) === 1
            && preg_match('/\b(?:naar|to)\s+.+/iu', $text) === 1) {
            return true;
        }

        return false;
    }

    public function isPriceQuoteRequest(string $message): bool
    {
        $text = $this->normalize($message);

        return $this->matchesAny($text, [
            'wat kost', 'kost een rit', 'kost een taxirit', 'prijs van een rit',
            'prijs voor een rit', 'offerte voor', 'prijsindicatie', 'prijs berekenen',
        ]);
    }

    public function isTravelIntentMessage(string $message): bool
    {
        $text = $this->normalize($message);

        if ($this->matchesAny($text, [
            'mijn rit', 'volgende rit', 'eerst volgende', 'eerste volgende',
            'welke rit', 'welke ritten', 'hoeveel rit', 'hoeveel ritten',
            'chauffeur', 'chauffeurs', 'klant', 'klanten', 'omzet', 'planning',
        ])) {
            return false;
        }

        if (preg_match('/\b(?:ik\s+)?(?:wil|moet|ga|wilt)\s+(?:graag\s+)?(?:naar|to)\s+.+/iu', $text)) {
            return true;
        }

        if (preg_match('/\b(?:kan|kun)\s+ik\s+(?:ook\s+)?(?:naar|to)\s+.+/iu', $text)) {
            return true;
        }

        if (preg_match('/\b(?:taxi|taxirit|rit)\s+naar\s+.+/iu', $text)) {
            return true;
        }

        return false;
    }

    public function resolveFlow(string $message, bool $publicChannel): string
    {
        if ($this->isBookingRequest($message)) {
            return 'booking';
        }

        if ($this->isPriceQuoteRequest($message)) {
            return 'quote';
        }

        if ($publicChannel && $this->isTravelIntentMessage($message)) {
            return 'booking';
        }

        return 'quote';
    }

    public function parsePassengers(string $message): ?int
    {
        $text = $this->normalize($message);

        if (preg_match('/\b(\d{1,2})\s*(?:personen|passagiers|mensen|pax)\b/u', $text, $matches)) {
            return $this->boundedPassengers((int) $matches[1]);
        }

        if (preg_match('/\b(?:met|voor)\s+(\d{1,2})\b/u', $text, $matches)) {
            return $this->boundedPassengers((int) $matches[1]);
        }

        if (preg_match('/^\s*(\d{1,2})\s*$/u', $text, $matches)) {
            return $this->boundedPassengers((int) $matches[1]);
        }

        return null;
    }

    public function parseBaggagePieces(string $message): ?int
    {
        $text = $this->normalize($message);

        if ($this->matchesAny($text, ['geen bagage', 'geen koffers', 'niets', 'geen'])) {
            return 0;
        }

        if (preg_match('/\b(\d{1,2})\s*(?:stuks|stuk|koffers|koffer|tassen|tas|bagage)\b/u', $text, $matches)) {
            return max(0, min(20, (int) $matches[1]));
        }

        if (preg_match('/^\s*(\d{1,2})\s*$/u', $text, $matches)) {
            return max(0, min(20, (int) $matches[1]));
        }

        return null;
    }

    public function parsePickupDatetime(string $message): ?string
    {
        $text = trim($message);
        if ($text === '') {
            return null;
        }

        $lower = mb_strtolower($text);

        if (preg_match('/^morgen(?: om)?\s+(.+)$/iu', $text, $matches)) {
            return $this->tryParseDateTime('tomorrow '.$matches[1]);
        }

        if (preg_match('/^vandaag(?: om)?\s+(.+)$/iu', $text, $matches)) {
            return $this->tryParseDateTime('today '.$matches[1]);
        }

        if (Str::startsWith($lower, 'morgen')) {
            return $this->tryParseDateTime('tomorrow '.preg_replace('/^morgen\s*(om\s*)?/iu', '', $text));
        }

        if (Str::startsWith($lower, 'vandaag')) {
            return $this->tryParseDateTime('today '.preg_replace('/^vandaag\s*(om\s*)?/iu', '', $text));
        }

        return $this->tryParseDateTime($text);
    }

    public function isCancellation(string $message): bool
    {
        $text = $this->normalize($message);

        return $this->matchesAny($text, [
            'annuleer', 'annuleren', 'stop', 'afbreken', 'andere vraag',
            'laat maar', 'vergeet het', 'niet meer',
        ]);
    }

    public function isOptionalSkipAnswer(string $message): bool
    {
        $text = $this->normalize($message);

        return in_array($text, ['geen', '-', 'nee', 'nvt', 'niets', 'niks', 'overslaan'], true);
    }

    public function isValidContactName(string $name): bool
    {
        return mb_strlen(trim($name)) >= 2;
    }

    public function isValidPhone(string $phone): bool
    {
        $value = trim($phone);
        if ($value === '' || ! preg_match('/^[+.\d\s()\-]+$/', $value)) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return false;
        }

        if (str_starts_with($digits, '06')) {
            return (bool) preg_match('/^06\d{8}$/', $digits);
        }

        if (str_starts_with($value, '+31') || str_starts_with($value, '0031')) {
            return strlen($digits) >= 10 && strlen($digits) <= 11;
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15;
    }

    public function isValidEmail(string $email): bool
    {
        $value = trim($email);

        return $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function tryParseDateTime(string $value): ?string
    {
        $value = trim(str_replace([' uur', 'uur', '.'], ['', '', ':00'], $value));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            $value = str_replace('T', ' ', $value).':00';
        }

        try {
            $parsed = Carbon::parse($value, config('app.timezone'));
            if ($parsed->isPast()) {
                return null;
            }

            return $parsed->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function boundedPassengers(int $count): ?int
    {
        if ($count < 1 || $count > 20) {
            return null;
        }

        return $count;
    }

    private function cleanAddress(string $value): ?string
    {
        $address = trim($value, " \t\n\r\0\x0B?.!");
        $address = preg_replace('/\s+/u', ' ', $address) ?? $address;

        return $address !== '' ? $address : null;
    }

    private function normalize(string $message): string
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
