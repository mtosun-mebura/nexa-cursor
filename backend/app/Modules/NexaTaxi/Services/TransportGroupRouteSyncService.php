<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
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
        $template = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
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

            return [
                'recalculated' => true,
                'warnings' => ['Geen actieve leden meer; route-stops zijn geleegd.'],
                'message' => 'Route geleegd (geen leden meer).',
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

        return [
            'recalculated' => true,
            'warnings' => $result['warnings'],
            'message' => ($forceFullPlan || $hadStops || $result['stops'] !== [])
                ? 'Route automatisch herberekend.'
                : null,
        ];
    }

    /**
     * @return array{recalculated: bool, warnings: list<string>, message: string|null}
     */
    public function syncDepartureAndRecalculate(string $conn, TransportGroup $group): array
    {
        $this->syncDepartureFromGroup($conn, $group);

        return $this->recalculateForGroup($conn, $group, forceFullPlan: true);
    }

    public function syncDepartureFromGroup(string $conn, TransportGroup $group): void
    {
        $template = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->first();

        if (! $template) {
            $template = TransportRouteTemplate::on($conn)->create([
                'company_id' => $group->company_id,
                'transport_group_id' => $group->id,
                'label' => $group->name.' route',
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
}
