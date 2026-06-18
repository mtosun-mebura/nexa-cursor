<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportIndividualBooking;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportRouteStop;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ContractOccurrenceGeneratorService
{
    public function __construct(
        private readonly TransportRoutePlannerService $routePlanner,
        private readonly TransportScheduleExceptionService $scheduleExceptions,
    ) {}

    /**
     * @return array{created: int, skipped: int, errors: int}
     */
    public function generateGroupOccurrences(string $conn, int $horizonDays = 14, ?Carbon $fromDate = null): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'errors' => 0];
        $start = ($fromDate ?? now())->copy()->startOfDay();
        $end = $start->copy()->addDays($horizonDays - 1);

        $templates = TransportRouteTemplate::on($conn)
            ->where('active', true)
            ->whereHas('stops')
            ->with(['stops.passenger', 'group', 'assignment'])
            ->get();

        foreach ($templates as $template) {
            $this->generateDatesForTemplate($conn, $template, $start, $end, $stats);
        }

        return $stats;
    }

    /**
     * @return array{created: int, skipped: int, errors: int}
     */
    public function generateForRouteTemplate(string $conn, int $templateId, int $horizonDays = 14): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'errors' => 0];
        $template = TransportRouteTemplate::on($conn)
            ->where('active', true)
            ->whereHas('stops')
            ->with(['stops.passenger', 'group', 'assignment'])
            ->find($templateId);

        if (! $template) {
            return $stats;
        }

        $start = now()->copy()->startOfDay();
        $end = $start->copy()->addDays($horizonDays - 1);
        $this->generateDatesForTemplate($conn, $template, $start, $end, $stats);

        return $stats;
    }

    public function syncRouteTemplateAssignment(
        string $conn,
        int $templateId,
        ?int $driverId,
        ?int $vehicleId,
    ): int {
        $rideIds = TransportOccurrence::on($conn)
            ->where('transport_route_template_id', $templateId)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->whereNotNull('ride_request_id')
            ->pluck('ride_request_id');

        if ($rideIds->isEmpty()) {
            return 0;
        }

        return RideRequest::on($conn)
            ->whereIn('id', $rideIds)
            ->where('status', RideRequest::STATUS_ACCEPTED)
            ->update([
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
            ]);
    }

    public function resyncScheduleTimesForRouteTemplate(string $conn, int $templateId): int
    {
        $template = TransportRouteTemplate::on($conn)
            ->where('active', true)
            ->whereHas('stops')
            ->with(['stops.passenger', 'group', 'assignment'])
            ->find($templateId);

        if (! $template) {
            return 0;
        }

        $pickupStops = $template->stops->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)->values();
        if ($pickupStops->isEmpty()) {
            return 0;
        }

        $firstPickup = $pickupStops->first();
        $departureTime = $this->resolveDepartureTime($template, $pickupStops, $firstPickup);

        $occurrences = TransportOccurrence::on($conn)
            ->where('transport_route_template_id', $templateId)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->whereNotNull('ride_request_id')
            ->get();

        $updated = 0;

        foreach ($occurrences as $occurrence) {
            $ride = RideRequest::on($conn)->find($occurrence->ride_request_id);
            if (! $ride || in_array($ride->status, [
                RideRequest::STATUS_COMPLETED,
                RideRequest::STATUS_CANCELLED,
            ], true)) {
                continue;
            }

            $date = $occurrence->scheduled_date instanceof Carbon
                ? $occurrence->scheduled_date->toDateString()
                : (string) $occurrence->scheduled_date;

            $scheduledAt = ContractTransportTimezone::parseLocalDateTime($date, $departureTime);

            $ride->update(['pickup_at' => $scheduledAt]);
            $occurrence->update(['scheduled_at' => $scheduledAt]);

            $rideStops = RideStop::on($conn)
                ->where('ride_request_id', $ride->id)
                ->orderBy('sequence')
                ->get()
                ->keyBy('sequence');

            foreach ($template->stops as $templateStop) {
                $rideStop = $rideStops->get($templateStop->sequence);
                if (! $rideStop) {
                    continue;
                }

                $rideStop->update([
                    'planned_at' => ContractTransportTimezone::parseLocalDateTime(
                        $date,
                        (string) $templateStop->planned_at_time
                    ),
                ]);
            }

            $updated++;
        }

        return $updated;
    }

    /**
     * @return array{date: string, template: TransportRouteTemplate}|null
     */
    private function resolveOccurrenceTemplateContext(string $conn, ?RideRequest $ride): ?array
    {
        if (! $ride || $ride->ride_type !== RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
            return null;
        }

        $occurrence = TransportOccurrence::on($conn)
            ->where('ride_request_id', $ride->id)
            ->first();

        if (! $occurrence?->transport_route_template_id) {
            return null;
        }

        $template = TransportRouteTemplate::on($conn)
            ->with('stops')
            ->find($occurrence->transport_route_template_id);

        if (! $template || $template->stops->isEmpty()) {
            return null;
        }

        $date = $occurrence->scheduled_date instanceof Carbon
            ? $occurrence->scheduled_date->toDateString()
            : (string) $occurrence->scheduled_date;

        return [
            'date' => $date,
            'template' => $template,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schedulePayloadForRide(string $conn, RideRequest $ride): array
    {
        $context = $this->resolveOccurrenceTemplateContext($conn, $ride);

        if (! $context) {
            return $this->schedulePayloadFromRideStops($conn, $ride);
        }

        ['date' => $date, 'template' => $template] = $context;
        $pickupStops = $template->stops->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)->values();
        $destinationStop = $template->stops->firstWhere('stop_type', TransportRouteStop::STOP_TYPE_DESTINATION);
        $firstPickup = $pickupStops->first();

        $departureAt = null;
        if ($firstPickup) {
            $departureTime = $this->resolveDepartureTime($template, $pickupStops, $firstPickup);
            $departureAt = ContractTransportTimezone::parseLocalDateTime($date, $departureTime);
        }

        $destinationAt = $destinationStop
            ? ContractTransportTimezone::parseLocalDateTime($date, (string) $destinationStop->planned_at_time)
            : null;

        $firstPickupAt = $firstPickup
            ? ContractTransportTimezone::parseLocalDateTime($date, (string) $firstPickup->planned_at_time)
            : null;

        return [
            'departure_at' => ContractTransportTimezone::toDriverIso8601($departureAt),
            'destination_arrival_at' => ContractTransportTimezone::toDriverIso8601($destinationAt),
            'first_pickup_at' => ContractTransportTimezone::toDriverIso8601($firstPickupAt),
            'destination_address' => $destinationStop?->address ?? $ride->dropoff_address,
        ];
    }

    public function plannedAtForRideStop(string $conn, RideStop $stop): ?Carbon
    {
        $ride = RideRequest::on($conn)->find($stop->ride_request_id);
        $context = $this->resolveOccurrenceTemplateContext($conn, $ride);

        if (! $context) {
            return $stop->planned_at;
        }

        $templateStop = $context['template']->stops->firstWhere('sequence', $stop->sequence);

        if (! $templateStop) {
            return $stop->planned_at;
        }

        return ContractTransportTimezone::parseLocalDateTime(
            $context['date'],
            (string) $templateStop->planned_at_time,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulePayloadFromRideStops(string $conn, RideRequest $ride): array
    {
        $stops = RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->orderBy('sequence')
            ->get();

        if ($stops->isEmpty()) {
            return [
                'departure_at' => ContractTransportTimezone::toDriverIso8601($ride->pickup_at),
                'destination_arrival_at' => null,
                'first_pickup_at' => null,
                'destination_address' => $ride->dropoff_address,
            ];
        }

        $destination = $stops->firstWhere('stop_type', TransportRouteStop::STOP_TYPE_DESTINATION);
        $firstPickup = $stops->firstWhere('stop_type', TransportRouteStop::STOP_TYPE_PICKUP);

        return [
            'departure_at' => ContractTransportTimezone::toDriverIso8601($ride->pickup_at),
            'destination_arrival_at' => ContractTransportTimezone::toDriverIso8601($destination?->planned_at),
            'first_pickup_at' => ContractTransportTimezone::toDriverIso8601($firstPickup?->planned_at),
            'destination_address' => $destination?->address ?? $ride->dropoff_address,
        ];
    }

    public function syncIndividualBookingAssignment(string $conn, TransportIndividualBooking $booking): int
    {
        $rideId = TransportOccurrence::on($conn)
            ->where('transport_individual_booking_id', $booking->id)
            ->whereNotNull('ride_request_id')
            ->value('ride_request_id');

        if (! $rideId) {
            return 0;
        }

        return RideRequest::on($conn)
            ->whereKey($rideId)
            ->where('status', RideRequest::STATUS_ACCEPTED)
            ->update([
                'driver_id' => $booking->driver_id,
                'vehicle_id' => $booking->vehicle_id,
            ]);
    }

    /**
     * @param  array{created: int, skipped: int, errors: int}  $stats
     */
    private function generateDatesForTemplate(
        string $conn,
        TransportRouteTemplate $template,
        Carbon $start,
        Carbon $end,
        array &$stats,
    ): void {
        $group = $template->group;
        if (! $group || ! $group->active) {
            return;
        }

        $contract = TransportContract::on($conn)->find($group->transport_contract_id);
        if (! $contract || $contract->status !== 'active') {
            return;
        }

        if ($contract->start_date && $contract->start_date->gt($end)) {
            return;
        }
        if ($contract->end_date && $contract->end_date->lt($start)) {
            return;
        }

        $template->loadMissing(['stops.passenger', 'assignment']);
        $recurrenceDays = $template->recurrence_days ?: TransportRouteTemplate::defaultRecurrenceDays();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($contract->start_date && $date->lt($contract->start_date)) {
                continue;
            }
            if ($contract->end_date && $date->gt($contract->end_date)) {
                continue;
            }

            if (! in_array($date->dayOfWeekIso, $recurrenceDays, true)) {
                continue;
            }

            if ($this->scheduleExceptions->isExceptionDate(
                $conn,
                (int) $contract->company_id,
                $date,
                (int) $contract->id,
            )) {
                $stats['skipped']++;

                continue;
            }

            $exists = TransportOccurrence::on($conn)
                ->where('transport_route_template_id', $template->id)
                ->whereDate('scheduled_date', $date->toDateString())
                ->exists();

            if ($exists) {
                $stats['skipped']++;

                continue;
            }

            try {
                $this->createGroupOccurrence($conn, $template, $contract, $date);
                $stats['created']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::warning('Contract occurrence generatie mislukt.', [
                    'transport_route_template_id' => $template->id,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array{created: int, skipped: int, errors: int}
     */
    public function generateIndividualOccurrences(string $conn, int $horizonDays = 14): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'errors' => 0];
        $end = now()->copy()->addDays($horizonDays)->endOfDay();

        $bookings = TransportIndividualBooking::on($conn)
            ->where('status', TransportIndividualBooking::STATUS_PLANNED)
            ->where('pickup_at', '>=', now()->startOfDay())
            ->where('pickup_at', '<=', $end)
            ->with(['passenger', 'contract'])
            ->orderBy('pickup_at')
            ->get();

        foreach ($bookings as $booking) {
            try {
                $created = $this->generateForIndividualBooking($conn, $booking);
                if ($created) {
                    $stats['created']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::warning('Individuele contract occurrence mislukt.', [
                    'transport_individual_booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * @return array{group: array{created: int, skipped: int, errors: int}, individual: array{created: int, skipped: int, errors: int}, created: int, skipped: int, errors: int}
     */
    public function generateAll(string $conn, int $horizonDays = 14, ?Carbon $fromDate = null): array
    {
        $group = $this->generateGroupOccurrences($conn, $horizonDays, $fromDate);
        $individual = $this->generateIndividualOccurrences($conn, $horizonDays);

        return [
            'group' => $group,
            'individual' => $individual,
            'created' => $group['created'] + $individual['created'],
            'skipped' => $group['skipped'] + $individual['skipped'],
            'errors' => $group['errors'] + $individual['errors'],
        ];
    }

    public function generateForIndividualBooking(string $conn, TransportIndividualBooking $booking): bool
    {
        if ($booking->status !== TransportIndividualBooking::STATUS_PLANNED) {
            return false;
        }

        $exists = TransportOccurrence::on($conn)
            ->where('transport_individual_booking_id', $booking->id)
            ->exists();

        if ($exists) {
            return false;
        }

        $contract = $booking->contract ?? TransportContract::on($conn)->find($booking->transport_contract_id);
        if (! $contract || $contract->status !== 'active') {
            return false;
        }

        if ($contract->start_date && $booking->pickup_at->lt($contract->start_date->startOfDay())) {
            return false;
        }
        if ($contract->end_date && $booking->pickup_at->gt($contract->end_date->endOfDay())) {
            return false;
        }

        $this->createIndividualOccurrence($conn, $booking, $contract);

        return true;
    }

    private function createIndividualOccurrence(
        string $conn,
        TransportIndividualBooking $booking,
        TransportContract $contract,
    ): TransportOccurrence {
        $customer = TransportCustomer::on($conn)->find($contract->transport_customer_id);
        $passenger = $booking->passenger;
        $quotedPrice = $booking->price_override ?? $contract->price_per_ride;

        $occurrence = TransportOccurrence::on($conn)->create([
            'company_id' => $booking->company_id,
            'transport_contract_id' => $contract->id,
            'occurrence_type' => 'individual',
            'transport_individual_booking_id' => $booking->id,
            'scheduled_date' => $booking->pickup_at->toDateString(),
            'scheduled_at' => $booking->pickup_at,
            'status' => 'generated',
        ]);

        $ride = RideRequest::on($conn)->create([
            'company_id' => $booking->company_id,
            'vehicle_id' => $booking->vehicle_id,
            'driver_id' => $booking->driver_id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => RideRequest::SOURCE_CONTRACT,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
            'transport_contract_id' => $contract->id,
            'transport_occurrence_id' => $occurrence->id,
            'transport_passenger_id' => $booking->transport_passenger_id,
            'pickup_address' => $booking->pickup_address,
            'dropoff_address' => $booking->dropoff_address,
            'pickup_lat' => $booking->pickup_lat,
            'pickup_lng' => $booking->pickup_lng,
            'dropoff_lat' => $booking->dropoff_lat,
            'dropoff_lng' => $booking->dropoff_lng,
            'passengers' => 1,
            'pickup_at' => $booking->pickup_at,
            'quoted_price' => $quotedPrice,
            'payment_method' => RideRequest::PAYMENT_METHOD_CONTRACT,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
            'customer_name' => $passenger?->full_name ?? $customer?->name ?? $contract->name,
            'customer_phone' => $passenger?->phone ?? $customer?->contact_phone,
            'customer_email' => $customer?->contact_email,
        ]);

        $occurrence->update(['ride_request_id' => $ride->id]);

        return $occurrence;
    }

    private function createGroupOccurrence(
        string $conn,
        TransportRouteTemplate $template,
        TransportContract $contract,
        Carbon $date,
    ): TransportOccurrence {
        $group = $template->group;
        $stops = $template->stops;
        $pickupStops = $stops->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)->values();
        $destinationStop = $stops->firstWhere('stop_type', TransportRouteStop::STOP_TYPE_DESTINATION);

        if ($pickupStops->isEmpty() || ! $destinationStop) {
            throw new \RuntimeException('Route-template mist pickup- of bestemmingsstop.');
        }

        $firstPickup = $pickupStops->first();
        $departureTime = $this->resolveDepartureTime($template, $pickupStops, $firstPickup);
        $scheduledAt = ContractTransportTimezone::parseLocalDateTime($date->toDateString(), $departureTime);

        $customer = TransportCustomer::on($conn)->find($contract->transport_customer_id);
        $assignment = $template->assignment;

        $occurrence = TransportOccurrence::on($conn)->create([
            'company_id' => $template->company_id,
            'transport_contract_id' => $contract->id,
            'occurrence_type' => 'group',
            'transport_route_template_id' => $template->id,
            'scheduled_date' => $date->toDateString(),
            'scheduled_at' => $scheduledAt,
            'status' => 'generated',
        ]);

        $ride = RideRequest::on($conn)->create([
            'company_id' => $template->company_id,
            'vehicle_id' => $assignment?->vehicle_id,
            'driver_id' => $assignment?->driver_id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => RideRequest::SOURCE_CONTRACT,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_GROUP,
            'transport_contract_id' => $contract->id,
            'transport_occurrence_id' => $occurrence->id,
            'pickup_address' => $firstPickup->address,
            'dropoff_address' => $destinationStop->address,
            'pickup_lat' => $firstPickup->lat,
            'pickup_lng' => $firstPickup->lng,
            'dropoff_lat' => $destinationStop->lat,
            'dropoff_lng' => $destinationStop->lng,
            'passengers' => $pickupStops->count(),
            'pickup_at' => $scheduledAt,
            'payment_method' => RideRequest::PAYMENT_METHOD_CONTRACT,
            'payment_status' => RideRequest::PAYMENT_STATUS_NOT_REQUIRED,
            'customer_name' => $customer?->name ?? $contract->name,
            'customer_email' => $customer?->contact_email,
            'customer_phone' => $customer?->contact_phone,
        ]);

        $occurrence->update(['ride_request_id' => $ride->id]);

        foreach ($stops as $stop) {
            RideStop::on($conn)->create([
                'ride_request_id' => $ride->id,
                'sequence' => $stop->sequence,
                'stop_type' => $stop->stop_type,
                'transport_passenger_id' => $stop->transport_passenger_id,
                'passenger_name' => $stop->passenger?->full_name,
                'address' => $stop->address,
                'lat' => $stop->lat,
                'lng' => $stop->lng,
                'planned_at' => ContractTransportTimezone::parseLocalDateTime(
                    $date->toDateString(),
                    (string) $stop->planned_at_time
                ),
                'status' => 'planned',
            ]);
        }

        return $occurrence;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TransportRouteStop>  $pickupStops
     */
    private function resolveDepartureTime(
        TransportRouteTemplate $template,
        $pickupStops,
        TransportRouteStop $firstPickup,
    ): string {
        if ($template->driver_start_mode === TransportRouteTemplate::DRIVER_START_FIRST_STOP) {
            return strlen((string) $firstPickup->planned_at_time) === 5
                ? $firstPickup->planned_at_time.':00'
                : (string) $firstPickup->planned_at_time;
        }

        if ($template->driver_start_lat !== null && $template->driver_start_lng !== null) {
            $travelSeconds = $this->routePlanner->estimateTravelSecondsBetween(
                (float) $template->driver_start_lat,
                (float) $template->driver_start_lng,
                $firstPickup->lat !== null ? (float) $firstPickup->lat : null,
                $firstPickup->lng !== null ? (float) $firstPickup->lng : null,
            );
            $firstTime = strlen((string) $firstPickup->planned_at_time) === 5
                ? $firstPickup->planned_at_time.':00'
                : (string) $firstPickup->planned_at_time;
            $departure = Carbon::createFromFormat('H:i:s', $firstTime)->subSeconds($travelSeconds);

            return $departure->format('H:i:s');
        }

        return strlen((string) $firstPickup->planned_at_time) === 5
            ? $firstPickup->planned_at_time.':00'
            : (string) $firstPickup->planned_at_time;
    }
}
