<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatIntent;
use App\Enums\AiChat\AiChatResponseMode;
use Illuminate\Support\Carbon;

final class AiChatAdminSqlFormatter
{
    /**
     * @param  array{count: int, rows: list<array<string, mixed>>, summary?: ?array<string, mixed>, response_mode?: string}  $result
     */
    public function format(AiChatIntent $intent, array $result, ?string $responseMode = null): string
    {
        $mode = $responseMode ?? ($result['response_mode'] ?? 'list');
        $count = (int) ($result['count'] ?? count($result['rows'] ?? []));
        $rows = is_array($result['rows'] ?? null) ? $result['rows'] : [];
        $summary = is_array($result['summary'] ?? null) ? $result['summary'] : null;

        if ($summary !== null && in_array($intent, [AiChatIntent::OmzetVandaag, AiChatIntent::OmzetMorgen, AiChatIntent::OmzetVorigeMaand], true)) {
            return $this->formatRevenueSummary($intent, $summary);
        }

        if ($mode === 'count' || $mode === AiChatResponseMode::Count->value) {
            return $this->formatCountAnswer($intent, $count);
        }

        if ($rows === []) {
            return $this->emptyMessage($intent);
        }

        return match ($intent) {
            AiChatIntent::ChauffeursVandaag,
            AiChatIntent::ChauffeursMeesteRittenVandaag => $this->formatDriverRideCounts($rows),
            AiChatIntent::KlantenMeesteRitten,
            AiChatIntent::KlantenDezeMaand => $this->formatCustomerRideCounts($rows),
            AiChatIntent::KlantenNieuwDezeMaand => $this->formatNewCustomers($rows),
            AiChatIntent::KlantenLuchthaven,
            AiChatIntent::KlantenGeannuleerd => $this->formatCustomerRides($rows),
            AiChatIntent::VrijeChauffeursMorgen => $this->formatAvailableDrivers($rows),
            AiChatIntent::ChauffeursZonderRit => $this->formatDriverNames($rows, 'Chauffeurs zonder rit vandaag'),
            AiChatIntent::ChauffeursSchipholMorgen => $this->formatSchipholDrivers($rows),
            AiChatIntent::ChauffeursOnderweg => $this->formatDriversOnTrip($rows),
            AiChatIntent::RittenHoogsteOmzet => $this->formatTopRevenueRides($rows),
            AiChatIntent::Planning => $this->formatPlanningRows($rows),
            AiChatIntent::VoertuigenMorgen,
            AiChatIntent::VoertuigenBeschikbaar => $this->formatVehicles($rows),
            default => $this->formatRideRows($rows, $this->rideIntro($intent, $count)),
        };
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function formatRevenueSummary(AiChatIntent $intent, array $summary): string
    {
        $amount = number_format((float) ($summary['total_amount'] ?? 0), 2, ',', '.');
        $rideCount = (int) ($summary['ride_count'] ?? 0);
        $prefix = match ($intent) {
            AiChatIntent::OmzetVandaag => 'Omzet van vandaag',
            AiChatIntent::OmzetMorgen => 'Verwachte omzet voor morgen',
            AiChatIntent::OmzetVorigeMaand => 'Omzet van vorige maand',
            default => 'Omzet',
        };

        return "{$prefix}: €{$amount} ({$rideCount} ritten).";
    }

    private function formatCountAnswer(AiChatIntent $intent, int $count): string
    {
        return match ($intent) {
            AiChatIntent::RittenVandaag => $count === 1
                ? 'Er staat vandaag 1 rit gepland.'
                : "Er staan vandaag {$count} ritten gepland.",
            AiChatIntent::RittenMorgen => $count === 1
                ? 'Er staat morgen 1 rit gepland.'
                : "Er staan morgen {$count} ritten gepland.",
            AiChatIntent::LuchthavenrittenDezeMaand => $count === 1
                ? 'Er is deze maand 1 luchthavenrit uitgevoerd.'
                : "Er zijn deze maand {$count} luchthavenritten uitgevoerd.",
            default => $count === 1
                ? 'Er is 1 resultaat.'
                : "Er zijn {$count} resultaten.",
        };
    }

    private function emptyMessage(AiChatIntent $intent): string
    {
        return match ($intent) {
            AiChatIntent::RittenVandaag => 'Er staan vandaag geen ritten gepland.',
            AiChatIntent::RittenMorgen => 'Er staan morgen geen ritten gepland.',
            AiChatIntent::OpenRitten => 'Er zijn geen ritten die nog bevestigd moeten worden.',
            AiChatIntent::RittenGeannuleerd => 'Er zijn geen geannuleerde ritten gevonden.',
            AiChatIntent::RittenZonderChauffeur => 'Alle geplande ritten hebben een chauffeur toegewezen.',
            AiChatIntent::RittenZonderVoertuig => 'Alle geplande ritten hebben een voertuig toegewezen.',
            AiChatIntent::VrijeChauffeursMorgen => 'Er zijn morgen geen vrije chauffeurs gevonden.',
            AiChatIntent::ChauffeursZonderRit => 'Alle chauffeurs hebben vandaag minstens één rit.',
            AiChatIntent::ChauffeursOnderweg => 'Er zijn momenteel geen chauffeurs onderweg.',
            AiChatIntent::Planning => 'Geen planningproblemen gevonden.',
            AiChatIntent::LuchthavenrittenDezeMaand => 'Er zijn deze maand nog geen luchthavenritten uitgevoerd.',
            default => 'Geen resultaten gevonden voor deze vraag.',
        };
    }

    private function rideIntro(AiChatIntent $intent, int $count): string
    {
        return match ($intent) {
            AiChatIntent::RittenVandaag => $count === 1 ? 'Er staat vandaag 1 rit gepland:' : "Er staan vandaag {$count} ritten gepland:",
            AiChatIntent::RittenMorgen => $count === 1 ? 'Er staat morgen 1 rit gepland:' : "Er staan morgen {$count} ritten gepland:",
            AiChatIntent::OpenRitten => $count === 1 ? 'Er is 1 rit die nog bevestigd moet worden:' : "Er zijn {$count} ritten die nog bevestigd moeten worden:",
            AiChatIntent::RittenGeannuleerd => $count === 1 ? 'Er is 1 geannuleerde rit:' : "Er zijn {$count} geannuleerde ritten:",
            AiChatIntent::RittenZonderChauffeur => $count === 1 ? 'Er is 1 rit zonder chauffeur:' : "Er zijn {$count} ritten zonder chauffeur:",
            AiChatIntent::RittenZonderVoertuig => $count === 1 ? 'Er is 1 rit zonder voertuig:' : "Er zijn {$count} ritten zonder voertuig:",
            default => $count === 1 ? 'Er is 1 rit:' : "Er zijn {$count} ritten:",
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatRideRows(array $rows, string $intro): string
    {
        $entries = [];
        foreach ($rows as $index => $row) {
            $entries[] = $this->formatRideEntry($row, $index + 1);
        }

        return implode("\n\n", array_merge([$intro], $entries));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formatRideEntry(array $row, int $index): string
    {
        $customer = trim((string) ($row['customer_name'] ?? ''));
        $driver = trim((string) ($row['driver_name'] ?? ''));
        $pickup = trim((string) ($row['pickup_address'] ?? ''));
        $dropoff = trim((string) ($row['dropoff_address'] ?? ''));
        $pickupTime = $this->formatPickupTime($row['pickup_at'] ?? null);
        $status = trim((string) ($row['status_label'] ?? $row['status'] ?? ''));
        $label = $customer !== '' ? $customer : ('Rit #'.($row['id'] ?? $index));

        $lines = ["{$index}. {$label}"];
        if ($pickupTime !== '') {
            $lines[] = '   Ophaaltijd: '.$pickupTime;
        }
        if ($pickup !== '') {
            $lines[] = '   Van: '.$pickup;
        }
        if ($dropoff !== '') {
            $lines[] = '   Naar: '.$dropoff;
        }
        if ($driver !== '') {
            $lines[] = '   Chauffeur: '.$driver;
        }
        if ($status !== '') {
            $lines[] = '   Status: '.$status;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatDriverRideCounts(array $rows): string
    {
        $lines = ['Chauffeurs met ritten vandaag:'];
        foreach ($rows as $index => $row) {
            $lines[] = ($index + 1).'. '.trim((string) ($row['driver_name'] ?? 'Onbekend'))
                .' — '.(int) ($row['ride_count'] ?? 0).' ritten';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatCustomerRideCounts(array $rows): string
    {
        $lines = ['Klanten:'];
        foreach ($rows as $index => $row) {
            $lines[] = ($index + 1).'. '.trim((string) ($row['customer_name'] ?? 'Onbekend'))
                .' — '.(int) ($row['ride_count'] ?? 0).' ritten';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatNewCustomers(array $rows): string
    {
        $lines = ['Nieuwe klanten deze maand:'];
        foreach ($rows as $index => $row) {
            $lines[] = ($index + 1).'. '.trim((string) ($row['customer_name'] ?? 'Onbekend'))
                .' — '.(int) ($row['ride_count'] ?? 0).' ritten';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatCustomerRides(array $rows): string
    {
        return $this->formatRideRows($rows, 'Klanten met relevante ritten:');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatAvailableDrivers(array $rows): string
    {
        $names = array_values(array_filter(array_map(
            fn (array $row) => trim((string) ($row['driver_name'] ?? '')),
            $rows,
        )));

        if ($names === []) {
            return 'Er zijn morgen geen vrije chauffeurs gevonden.';
        }

        return "Beschikbare chauffeurs morgen:\n- ".implode("\n- ", $names);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatDriverNames(array $rows, string $intro): string
    {
        $names = array_values(array_filter(array_map(
            fn (array $row) => trim((string) ($row['driver_name'] ?? '')),
            $rows,
        )));

        if ($names === []) {
            return $this->emptyMessage(AiChatIntent::ChauffeursZonderRit);
        }

        return $intro.":\n- ".implode("\n- ", $names);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatSchipholDrivers(array $rows): string
    {
        return $this->formatDriverNames($rows, 'Chauffeurs morgen naar Schiphol/luchthaven');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatDriversOnTrip(array $rows): string
    {
        if ($rows === []) {
            return $this->emptyMessage(AiChatIntent::ChauffeursOnderweg);
        }

        return $this->formatRideRows($rows, 'Chauffeurs onderweg:');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatTopRevenueRides(array $rows): string
    {
        $lines = ['Ritten met de hoogste omzet:'];
        foreach ($rows as $index => $row) {
            $amount = number_format((float) ($row['revenue'] ?? 0), 2, ',', '.');
            $lines[] = ($index + 1).'. '.trim((string) ($row['customer_name'] ?? 'Rit'))
                .' — €'.$amount
                .($this->formatPickupTime($row['pickup_at'] ?? null) !== '' ? ' ('.$this->formatPickupTime($row['pickup_at'] ?? null).')' : '');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatPlanningRows(array $rows): string
    {
        if ($rows === []) {
            return $this->emptyMessage(AiChatIntent::Planning);
        }

        if (isset($rows[0]['rit_a'])) {
            $lines = ['Overlappende ritten:'];
            foreach ($rows as $index => $row) {
                $lines[] = ($index + 1).'. Chauffeur '.($row['driver_name'] ?? 'Onbekend')
                    .' — ritten #'.($row['rit_a'] ?? '?').' en #'.($row['rit_b'] ?? '?');
            }

            return implode("\n", $lines);
        }

        return $this->formatRideRows($rows, 'Planning:');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatVehicles(array $rows): string
    {
        $lines = ['Voertuigen:'];
        foreach ($rows as $index => $row) {
            $lines[] = ($index + 1).'. '.trim((string) ($row['name'] ?? 'Voertuig'))
                .(! empty($row['license_plate']) ? ' ('.$row['license_plate'].')' : '');
        }

        return implode("\n", $lines);
    }

    private function formatPickupTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->timezone(config('app.timezone'))->format('d-m-Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
