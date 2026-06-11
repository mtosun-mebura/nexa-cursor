<?php

namespace App\Services\AiChat;

use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Support\Carbon;

final class AiChatOwnRideFormatter
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function format(array $rows, ?string $hint = null): string
    {
        if ($rows === []) {
            return $this->emptyMessage($hint);
        }

        if ($hint === 'gepland') {
            return $this->formatCountAnswer(count($rows), 'gepland');
        }

        if ($hint === 'voltooid') {
            return $this->formatCountAnswer(count($rows), 'voltooid');
        }

        if (in_array($hint, ['vandaag', 'morgen', 'aankomend'], true)) {
            return $this->formatRideList($rows, $hint);
        }

        $ride = $rows[0];
        $status = (string) ($ride['status'] ?? '');
        $statusLabel = trim((string) ($ride['status_label'] ?? $status));
        $pickupTime = $this->formatPickupTime($ride['pickup_at'] ?? null);

        if ($hint === 'factuur') {
            return $this->formatInvoiceAnswer($ride, $pickupTime);
        }

        if ($hint === 'prijs') {
            return $this->formatPriceAnswer($ride, $pickupTime);
        }

        if ($hint === 'status') {
            return $this->formatStatusAnswer($status, $statusLabel, $pickupTime, $ride);
        }

        if ($hint === 'chauffeur') {
            $driver = trim((string) ($ride['driver_name'] ?? ''));
            if ($driver === '') {
                return 'Er is nog geen chauffeur aan je reservering toegewezen. Huidige status: '.$statusLabel.'.';
            }

            $vehicle = trim((string) ($ride['vehicle_name'] ?? ''));

            return 'Je chauffeur is '.$driver
                .($vehicle !== '' ? ' (voertuig: '.$vehicle.')' : '')
                .'. Ophaaltijd: '.$pickupTime.'.';
        }

        if (in_array($hint, ['volgende', 'ophaaltijd'], true)) {
            return $this->formatNextRideAnswer($ride, $pickupTime, $hint === 'volgende');
        }

        return $this->formatRideSummary($ride, $statusLabel, $pickupTime);
    }

    private function emptyMessage(?string $hint): string
    {
        return match ($hint) {
            'gepland' => 'Je hebt geen ritten gepland.',
            'voltooid' => 'Je hebt nog geen voltooide ritten.',
            'vandaag' => 'Je hebt vandaag geen ritten gepland.',
            'morgen' => 'Je hebt morgen geen ritten gepland.',
            'aankomend' => 'Je hebt geen aankomende ritten gepland.',
            'volgende', 'ophaaltijd', 'prijs' => 'Je hebt geen komende ritten gepland.',
            'factuur' => 'Ik kon geen factuur vinden voor je laatste voltooide rit.',
            default => 'Ik kon geen actieve reservering vinden op jouw naam. Controleer of je bent ingelogd met hetzelfde e-mailadres als bij je boeking, of bekijk je ritten op Mijn Taxi.',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatRideList(array $rows, string $hint): string
    {
        $intro = match ($hint) {
            'vandaag' => count($rows) === 1 ? 'Je hebt vandaag 1 rit:' : 'Je hebt vandaag '.count($rows).' ritten:',
            'morgen' => count($rows) === 1 ? 'Je hebt morgen 1 rit:' : 'Je hebt morgen '.count($rows).' ritten:',
            default => count($rows) === 1 ? 'Je hebt 1 aankomende rit:' : 'Je hebt '.count($rows).' aankomende ritten:',
        };

        $entries = [];
        foreach ($rows as $index => $row) {
            $pickupTime = $this->formatPickupTime($row['pickup_at'] ?? null);
            $statusLabel = trim((string) ($row['status_label'] ?? $row['status'] ?? ''));
            $pickup = trim((string) ($row['pickup_address'] ?? ''));
            $dropoff = trim((string) ($row['dropoff_address'] ?? ''));
            $route = $pickup;
            if ($dropoff !== '') {
                $route = $route !== '' ? $route.' → '.$dropoff : $dropoff;
            }

            $line = ($index + 1).'. '.$pickupTime;
            if ($route !== '') {
                $line .= ' — '.$route;
            }
            if ($statusLabel !== '') {
                $line .= ' ('.$statusLabel.')';
            }
            $entries[] = $line;
        }

        return $intro."\n".implode("\n", $entries);
    }

    private function formatCountAnswer(int $count, string $type): string
    {
        if ($type === 'gepland') {
            if ($count === 0) {
                return 'Je hebt geen ritten gepland.';
            }

            return 'Je hebt '.$count.' '.($count === 1 ? 'rit gepland' : 'ritten gepland').'.';
        }

        if ($count === 0) {
            return 'Je hebt nog geen voltooide ritten.';
        }

        return 'Je hebt '.$count.' voltooide '.($count === 1 ? 'rit' : 'ritten').'.';
    }

    /**
     * @param  array<string, mixed>  $ride
     */
    private function formatNextRideAnswer(array $ride, string $pickupTime, bool $isExplicitNext): string
    {
        $pickup = trim((string) ($ride['pickup_address'] ?? 'het opgegeven adres'));
        $dropoff = trim((string) ($ride['dropoff_address'] ?? ''));

        if ($isExplicitNext) {
            $answer = 'Je volgende rit is op '.$pickupTime.' bij '.$pickup;
            if ($dropoff !== '') {
                $answer .= ' naar '.$dropoff;
            }

            return $answer.'.';
        }

        return 'Je wordt opgehaald op '.$pickupTime.' bij '.$pickup.'.';
    }

    /**
     * @param  array<string, mixed>  $ride
     */
    private function formatPriceAnswer(array $ride, string $pickupTime): string
    {
        $price = $ride['display_price'] ?? null;
        if ($price === null) {
            return 'Voor je volgende rit op '.$pickupTime.' is nog geen prijs bekend.';
        }

        return 'De prijs van je volgende rit op '.$pickupTime.' is € '.$this->formatMoney((float) $price).'.';
    }

    /**
     * @param  array<string, mixed>  $ride
     */
    private function formatInvoiceAnswer(array $ride, string $pickupTime): string
    {
        $invoiceNumber = trim((string) ($ride['invoice_number'] ?? ''));
        $pdfUrl = trim((string) ($ride['invoice_pdf_url'] ?? ''));

        if ($invoiceNumber !== '' && $pdfUrl !== '') {
            return 'Factuur '.$invoiceNumber.' voor je rit op '.$pickupTime.': [Factuur downloaden]('.$pdfUrl.')';
        }

        return 'Voor je laatste rit op '.$pickupTime.' is nog geen factuur beschikbaar.';
    }

    /**
     * @param  array<string, mixed>  $ride
     */
    private function formatStatusAnswer(string $status, string $statusLabel, string $pickupTime, array $ride): string
    {
        if ($status === RideRequest::STATUS_CANCELLED) {
            return 'Je reservering is geannuleerd.';
        }

        if (in_array($status, [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
            RideRequest::STATUS_COMPLETED,
        ], true)) {
            return 'Ja, je reservering is bevestigd. Status: '.$statusLabel.'. Ophaaltijd: '.$pickupTime.'.';
        }

        if ($status === RideRequest::STATUS_PENDING_PAYMENT) {
            return 'Je reservering wacht nog op betaling. Status: '.$statusLabel.'. Ophaaltijd: '.$pickupTime.'.';
        }

        return 'Je reservering is ontvangen maar nog niet definitief bevestigd. Huidige status: '.$statusLabel
            .'. Ophaaltijd: '.$pickupTime.'.';
    }

    /**
     * @param  array<string, mixed>  $ride
     */
    private function formatRideSummary(array $ride, string $statusLabel, string $pickupTime): string
    {
        $pickup = trim((string) ($ride['pickup_address'] ?? ''));
        $dropoff = trim((string) ($ride['dropoff_address'] ?? ''));

        $lines = [
            'Je reservering:',
            'Status: '.$statusLabel,
            'Ophaaltijd: '.$pickupTime,
        ];

        if ($pickup !== '') {
            $lines[] = 'Ophalen: '.$pickup;
        }

        if ($dropoff !== '') {
            $lines[] = 'Bestemming: '.$dropoff;
        }

        return implode("\n", $lines);
    }

    private function formatPickupTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'nog niet bekend';
        }

        try {
            return Carbon::parse((string) $value)->timezone(config('app.timezone'))->format('d-m-Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }
}
