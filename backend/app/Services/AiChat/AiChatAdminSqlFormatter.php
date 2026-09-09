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

        if (is_string($summary['answer'] ?? null) && trim((string) $summary['answer']) !== '') {
            return trim((string) $summary['answer']);
        }

        if ($summary !== null && $intent->isRevenueIntent()) {
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
            AiChatIntent::ChauffeursOnline,
            AiChatIntent::ChauffeursOverzicht => $this->formatDriverNames(
                $rows,
                $intent === AiChatIntent::ChauffeursOnline ? 'Chauffeurs nu online' : 'Chauffeurs',
            ),
            AiChatIntent::RittenHoogsteOmzet => $this->formatTopRevenueRides($rows),
            AiChatIntent::Planning => $this->formatPlanningRows($rows),
            AiChatIntent::PlanningChauffeurs => $this->formatDriverPlanning($rows),
            AiChatIntent::FacturenOpenstaand,
            AiChatIntent::FacturenBetaald,
            AiChatIntent::FacturenAchterstallig => $this->formatInvoiceRows($rows, $intent),
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
        $prefix = match ($intent) {
            AiChatIntent::OmzetVandaag => 'Omzet van vandaag',
            AiChatIntent::OmzetMorgen => 'Verwachte omzet voor morgen',
            AiChatIntent::OmzetDezeWeek => 'Omzet van deze week',
            AiChatIntent::OmzetDezeMaand => 'Omzet van deze maand',
            AiChatIntent::OmzetDitJaar => 'Omzet van dit jaar',
            AiChatIntent::OmzetVorigeMaand => 'Omzet van vorige maand',
            default => 'Omzet',
        };

        if ($intent === AiChatIntent::InkomstenOverzicht && is_array($summary['periods'] ?? null)) {
            $lines = ['Omzet van je bedrijf:'];
            foreach ($summary['periods'] as $period) {
                if (! is_array($period)) {
                    continue;
                }
                $label = (string) ($period['label'] ?? 'periode');
                $amount = number_format((float) ($period['total_amount'] ?? 0), 2, ',', '.');
                $rides = (int) ($period['ride_count'] ?? 0);
                $lines[] = '- '.ucfirst($label).": €{$amount} ({$rides} ritten)";
            }

            return implode("\n", $lines);
        }

        $amount = number_format((float) ($summary['total_amount'] ?? 0), 2, ',', '.');
        $rideCount = (int) ($summary['ride_count'] ?? 0);
        $label = (string) ($summary['label'] ?? '');
        if ($label !== '' && $intent === AiChatIntent::InkomstenOverzicht) {
            $prefix = 'Omzet '.$label;
        }

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
            AiChatIntent::RittenDezeWeek => $count === 1
                ? 'Er staat deze week 1 rit gepland.'
                : "Er staan deze week {$count} ritten gepland.",
            AiChatIntent::RittenDezeMaand => $count === 1
                ? 'Er staat deze maand 1 rit gepland.'
                : "Er staan deze maand {$count} ritten gepland.",
            AiChatIntent::RittenUitgevoerd => $count === 1
                ? 'Er is 1 rit uitgevoerd.'
                : "Er zijn {$count} ritten uitgevoerd.",
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
            AiChatIntent::PlanningChauffeurs => 'Er is geen chauffeurplanning gevonden.',
            AiChatIntent::ChauffeursOnline => 'Er zijn momenteel geen chauffeurs online.',
            AiChatIntent::ChauffeursOverzicht => 'Er zijn geen chauffeurs gevonden.',
            AiChatIntent::FacturenOpenstaand => 'Er staan geen openstaande facturen.',
            AiChatIntent::FacturenBetaald => 'Er zijn geen betaalde facturen gevonden.',
            AiChatIntent::FacturenAchterstallig => 'Er zijn geen achterstallige facturen.',
            AiChatIntent::RittenDezeWeek => 'Er staan deze week geen ritten gepland.',
            AiChatIntent::RittenDezeMaand => 'Er staan deze maand geen ritten gepland.',
            AiChatIntent::RittenUitgevoerd => 'Er zijn nog geen ritten uitgevoerd.',
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
            AiChatIntent::RittenDezeWeek => $count === 1 ? 'Er staat deze week 1 rit gepland:' : "Er staan deze week {$count} ritten gepland:",
            AiChatIntent::RittenDezeMaand => $count === 1 ? 'Er staat deze maand 1 rit gepland:' : "Er staan deze maand {$count} ritten gepland:",
            AiChatIntent::RittenUitgevoerd => $count === 1 ? 'Er is 1 uitgevoerde rit:' : "Er zijn {$count} uitgevoerde ritten:",
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

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatDriverPlanning(array $rows): string
    {
        $blocks = ['Planning van de chauffeurs:'];
        foreach ($rows as $row) {
            $name = trim((string) ($row['driver_name'] ?? 'Chauffeur'));
            $rides = is_array($row['rides'] ?? null) ? $row['rides'] : [];
            if ($rides === []) {
                $blocks[] = $name.': geen ritten in deze periode.';
                continue;
            }
            $lines = [$name.' ('.count($rides).' ritten):'];
            foreach ($rides as $index => $ride) {
                if (! is_array($ride)) {
                    continue;
                }
                $when = $this->formatPickupTime($ride['pickup_at'] ?? null);
                $from = trim((string) ($ride['pickup_address'] ?? ''));
                $to = trim((string) ($ride['dropoff_address'] ?? ''));
                $lines[] = '  '.($index + 1).'. '.($when !== '' ? $when.' — ' : '')
                    .($from !== '' ? $from : 'onbekend')
                    .' → '.($to !== '' ? $to : 'onbekend');
            }
            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function formatInvoiceRows(array $rows, AiChatIntent $intent): string
    {
        $intro = match ($intent) {
            AiChatIntent::FacturenOpenstaand => 'Openstaande facturen:',
            AiChatIntent::FacturenBetaald => 'Betaalde facturen:',
            AiChatIntent::FacturenAchterstallig => 'Achterstallige facturen:',
            default => 'Facturen:',
        };
        $lines = [$intro];
        foreach ($rows as $index => $row) {
            $number = trim((string) ($row['invoice_number'] ?? ''));
            $customer = trim((string) ($row['customer_name'] ?? ''));
            $amount = number_format((float) ($row['total_amount'] ?? 0), 2, ',', '.');
            $status = trim((string) ($row['status'] ?? ''));
            $due = trim((string) ($row['due_date'] ?? ''));
            $label = $number !== '' ? $number : ('Factuur #'.($row['id'] ?? $index + 1));
            if ($customer !== '') {
                $label .= ' — '.$customer;
            }
            $extra = '€'.$amount;
            if ($status !== '') {
                $extra .= ', '.$status;
            }
            if ($due !== '') {
                $extra .= ', vervalt '.$due;
            }
            $lines[] = ($index + 1).'. '.$label.' ('.$extra.')';
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
