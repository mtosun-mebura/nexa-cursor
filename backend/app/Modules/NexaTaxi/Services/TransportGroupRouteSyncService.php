<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportRouteStop;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use Illuminate\Support\Facades\DB;

class TransportGroupRouteSyncService
{
    public function __construct(
        private readonly TransportRoutePlannerService $routePlanner,
        private readonly ContractOccurrenceGeneratorService $occurrenceGenerator,
    ) {}

    /**
     * Herbereken route-stops na wijziging in groepsleden (of passagiergegevens).
     *
     * @return array{recalculated: bool, warnings: list<string>, message: string|null}
     */
    public function recalculateForGroup(string $conn, TransportGroup $group, bool $forceFullPlan = false): array
    {
        app(TaxiContractvervoerSchemaService::class)->ensureTransportGroupReturnTripColumns($conn);

        $template = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where(function ($q) {
                $q->where('direction', TransportRouteTemplate::DIRECTION_OUTBOUND)
                    ->orWhereNull('direction');
            })
            ->with(['stops.passenger'])
            ->first();

        if (! $template) {
            return [
                'recalculated' => false,
                'warnings' => [],
                'message' => null,
            ];
        }

        $activeMembers = $this->activeMembers($conn, (int) $group->id);

        if ($activeMembers->isEmpty()) {
            DB::connection($conn)->transaction(function () use ($conn, $template) {
                TransportRouteStop::on($conn)
                    ->where('transport_route_template_id', $template->id)
                    ->delete();
            });
            $template->unsetRelation('stops');
            $this->occurrenceGenerator->resyncScheduleTimesForRouteTemplate($conn, (int) $template->id);
            $return = $this->syncReturnRoute($conn, $group);

            return [
                'recalculated' => true,
                'warnings' => array_values(array_unique(array_merge(
                    ['Geen actieve leden meer; route-stops zijn geleegd.'],
                    $return['warnings']
                ))),
                'message' => trim('Route geleegd (geen leden meer). '.($return['message'] ?? '')),
            ];
        }

        $hadStops = $template->stops->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)->isNotEmpty();

        if (! $forceFullPlan && $template->route_locked && $hadStops) {
            $existingPickups = $template->stops
                ->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)
                ->values()
                ->map(fn (TransportRouteStop $stop) => [
                    'stop_type' => $stop->stop_type,
                    'transport_passenger_id' => $stop->transport_passenger_id,
                    'passenger_name' => $stop->passenger?->full_name,
                    'address' => $stop->address,
                    'lat' => $stop->lat !== null ? (float) $stop->lat : null,
                    'lng' => $stop->lng !== null ? (float) $stop->lng : null,
                    'sequence' => $stop->sequence,
                ])
                ->all();

            $activePassengerIds = $activeMembers->pluck('transport_passenger_id')->map(fn ($id) => (int) $id)->all();
            $filteredPickups = array_values(array_filter(
                $existingPickups,
                fn (array $stop) => in_array((int) ($stop['transport_passenger_id'] ?? 0), $activePassengerIds, true)
            ));

            $existingPassengerIds = array_map(
                fn (array $stop) => (int) ($stop['transport_passenger_id'] ?? 0),
                $filteredPickups
            );

            foreach ($activeMembers as $member) {
                $passengerId = (int) $member->transport_passenger_id;
                if (in_array($passengerId, $existingPassengerIds, true)) {
                    continue;
                }
                $passenger = $member->passenger;
                if (! $passenger || ! $passenger->pickup_address) {
                    continue;
                }
                $filteredPickups[] = [
                    'stop_type' => TransportRouteStop::STOP_TYPE_PICKUP,
                    'transport_passenger_id' => $passengerId,
                    'passenger_name' => $passenger->full_name,
                    'address' => $passenger->pickup_address,
                    'lat' => $passenger->pickup_lat !== null ? (float) $passenger->pickup_lat : null,
                    'lng' => $passenger->pickup_lng !== null ? (float) $passenger->pickup_lng : null,
                    'sequence' => count($filteredPickups) + 1,
                ];
            }

            $result = $filteredPickups === []
                ? $this->routePlanner->planRoute($group, $template, $activeMembers)
                : $this->routePlanner->recalculateTimesForOrder($group, $template, $filteredPickups);
        } else {
            $result = $this->routePlanner->planRoute($group, $template, $activeMembers);
        }

        $this->persistStops($conn, $template, $result['stops']);
        $this->occurrenceGenerator->resyncScheduleTimesForRouteTemplate($conn, (int) $template->id);

        $return = $this->syncReturnRoute($conn, $group);
        $warnings = array_values(array_unique(array_merge($result['warnings'], $return['warnings'])));
        $outboundMessage = ($forceFullPlan || $hadStops || $result['stops'] !== [])
            ? 'Route automatisch herberekend.'
            : null;
        $messages = array_values(array_filter([$outboundMessage, $return['message']]));

        return [
            'recalculated' => true,
            'warnings' => $warnings,
            'message' => $messages !== [] ? implode(' ', $messages) : null,
        ];
    }

    /**
     * @return array{recalculated: bool, warnings: list<string>, message: string|null}
     */
    public function syncDepartureAndRecalculate(string $conn, TransportGroup $group): array
    {
        $this->syncDepartureFromGroup($conn, $group);
        $outbound = $this->recalculateForGroup($conn, $group, forceFullPlan: true);
        $return = $this->syncReturnRoute($conn, $group);

        $warnings = array_values(array_unique(array_merge($outbound['warnings'], $return['warnings'])));
        $messages = array_values(array_filter([$outbound['message'], $return['message']]));

        return [
            'recalculated' => $outbound['recalculated'] || $return['recalculated'],
            'warnings' => $warnings,
            'message' => $messages !== [] ? implode(' ', $messages) : null,
        ];
    }

    /**
     * Maak/werk de retourroute bij of deactiveer die wanneer terugweg uitstaat.
     *
     * @return array{recalculated: bool, warnings: list<string>, message: string|null}
     */
    public function syncReturnRoute(string $conn, TransportGroup $group): array
    {
        $outbound = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where(function ($q) {
                $q->where('direction', TransportRouteTemplate::DIRECTION_OUTBOUND)
                    ->orWhereNull('direction');
            })
            ->with(['stops'])
            ->latest('id')
            ->first();

        $returnTemplate = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('direction', TransportRouteTemplate::DIRECTION_RETURN)
            ->latest('id')
            ->first();

        if (! $group->has_return_trip) {
            if ($returnTemplate && $returnTemplate->active) {
                $returnTemplate->update(['active' => false]);
                TransportRouteStop::on($conn)
                    ->where('transport_route_template_id', $returnTemplate->id)
                    ->delete();
                $this->cancelFutureOccurrencesForTemplate($conn, (int) $returnTemplate->id);

                return [
                    'recalculated' => true,
                    'warnings' => [],
                    'message' => 'Terugweg uitgeschakeld.',
                ];
            }

            return [
                'recalculated' => false,
                'warnings' => [],
                'message' => null,
            ];
        }

        $pickupTime = trim((string) ($group->return_pickup_time ?? ''));
        if ($pickupTime === '') {
            return [
                'recalculated' => false,
                'warnings' => ['Terugweg aan, maar geen ophaaltijd ingesteld.'],
                'message' => null,
            ];
        }

        if (! $returnTemplate) {
            $returnTemplate = TransportRouteTemplate::on($conn)->create([
                'company_id' => $group->company_id,
                'transport_group_id' => $group->id,
                'label' => $group->name.' terugweg',
                'direction' => TransportRouteTemplate::DIRECTION_RETURN,
                'recurrence_days' => $outbound?->recurrence_days ?: TransportRouteTemplate::defaultRecurrenceDays(),
                'driver_start_mode' => TransportRouteTemplate::DRIVER_START_FIRST_STOP,
                'buffer_seconds' => $outbound?->buffer_seconds ?? 120,
                'route_locked' => false,
                'active' => true,
            ]);
        } else {
            $returnTemplate->update([
                'active' => true,
                'label' => $group->name.' terugweg',
                'direction' => TransportRouteTemplate::DIRECTION_RETURN,
                'recurrence_days' => $outbound?->recurrence_days ?: ($returnTemplate->recurrence_days ?: TransportRouteTemplate::defaultRecurrenceDays()),
                'buffer_seconds' => $outbound?->buffer_seconds ?? $returnTemplate->buffer_seconds ?? 120,
                'driver_start_mode' => TransportRouteTemplate::DRIVER_START_FIRST_STOP,
                'driver_start_address' => null,
                'driver_start_lat' => null,
                'driver_start_lng' => null,
            ]);
        }

        $activeMembers = $this->activeMembers($conn, (int) $group->id);
        $result = $this->routePlanner->planReturnRoute($group, $returnTemplate, $activeMembers, $outbound);
        $this->persistStops($conn, $returnTemplate, $result['stops']);
        $this->occurrenceGenerator->syncOccurrencesForRouteTemplate($conn, (int) $returnTemplate->id);

        return [
            'recalculated' => true,
            'warnings' => $result['warnings'],
            'message' => $result['stops'] !== [] ? 'Terugweg automatisch berekend.' : 'Terugweg kon niet worden berekend (geen leden).',
        ];
    }

    public function syncDepartureFromGroup(string $conn, TransportGroup $group): void
    {
        $template = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where(function ($q) {
                $q->where('direction', TransportRouteTemplate::DIRECTION_OUTBOUND)
                    ->orWhereNull('direction');
            })
            ->first();

        if (! $template) {
            $template = TransportRouteTemplate::on($conn)->create([
                'company_id' => $group->company_id,
                'transport_group_id' => $group->id,
                'label' => $group->name.' route',
                'direction' => TransportRouteTemplate::DIRECTION_OUTBOUND,
                'recurrence_days' => TransportRouteTemplate::defaultRecurrenceDays(),
                'driver_start_mode' => TransportRouteTemplate::DRIVER_START_FIRST_STOP,
                'buffer_seconds' => 120,
                'route_locked' => false,
                'active' => true,
            ]);
        }

        $address = trim((string) ($group->departure_address ?? ''));

        if ($address !== '') {
            $template->update([
                'driver_start_mode' => TransportRouteTemplate::DRIVER_START_DEPOT,
                'driver_start_address' => $address,
                'driver_start_lat' => $group->departure_lat,
                'driver_start_lng' => $group->departure_lng,
            ]);

            return;
        }

        $template->update([
            'driver_start_mode' => TransportRouteTemplate::DRIVER_START_FIRST_STOP,
            'driver_start_address' => null,
            'driver_start_lat' => null,
            'driver_start_lng' => null,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $stops
     */
    private function persistStops(string $conn, TransportRouteTemplate $template, array $stops): void
    {
        DB::connection($conn)->transaction(function () use ($conn, $template, $stops) {
            TransportRouteStop::on($conn)
                ->where('transport_route_template_id', $template->id)
                ->delete();

            foreach ($stops as $stop) {
                TransportRouteStop::on($conn)->create([
                    'transport_route_template_id' => $template->id,
                    'sequence' => (int) $stop['sequence'],
                    'stop_type' => $stop['stop_type'],
                    'transport_passenger_id' => $stop['transport_passenger_id'] ?? null,
                    'address' => $stop['address'],
                    'lat' => $stop['lat'] ?? null,
                    'lng' => $stop['lng'] ?? null,
                    'planned_at_time' => strlen((string) $stop['planned_at_time']) === 5
                        ? $stop['planned_at_time'].':00'
                        : $stop['planned_at_time'],
                ]);
            }
        });

        $template->unsetRelation('stops');
        $template->load(['stops.passenger']);
    }

    private function activeMembers(string $conn, int $groupId)
    {
        $today = now()->toDateString();

        return TransportGroupMember::on($conn)
            ->where('transport_group_id', $groupId)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $today);
            })
            ->with(['passenger'])
            ->orderBy('sort_hint')
            ->orderBy('id')
            ->get();
    }

    private function cancelFutureOccurrencesForTemplate(string $conn, int $templateId): void
    {
        $occurrences = TransportOccurrence::on($conn)
            ->where('transport_route_template_id', $templateId)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($occurrences as $occurrence) {
            if ($occurrence->ride_request_id) {
                $ride = RideRequest::on($conn)->find($occurrence->ride_request_id);
                if ($ride && ! in_array($ride->status, [
                    RideRequest::STATUS_COMPLETED,
                    RideRequest::STATUS_ASSIGNED,
                ], true) && $ride->status !== RideRequest::STATUS_CANCELLED) {
                    $ride->update(['status' => RideRequest::STATUS_CANCELLED]);
                }
            }
            if ($occurrence->status !== 'cancelled') {
                $occurrence->update(['status' => 'cancelled']);
            }
        }
    }
}
