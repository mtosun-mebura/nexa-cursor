<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Models\TransportAnnouncement;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportPassengerAbsence;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Services\TransportPassengerAbsenceService;
use App\Modules\NexaTaxi\Services\TransportScheduleExceptionService;
use App\Modules\NexaTaxi\Support\ContractPortalDayLegs;
use App\Modules\NexaTaxi\Support\ContractPortalLegLabel;
use App\Modules\NexaTaxi\Support\ContractPortalNavigationRoute;
use App\Modules\NexaTaxi\Support\ContractPortalRideStatus;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContractPortalController extends Controller
{
    private const WEEKS_BACK = 4;

    private const WEEKS_AHEAD = 8;

    public function passengers(Request $request, TaxiContractPortalAccessService $access): JsonResponse
    {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');
        $passengers = $access->visiblePassengers($conn, $context);

        $today = now(config('app.timezone', 'Europe/Amsterdam'))->toDateString();
        $absenceMap = TransportPassengerAbsence::on($conn)
            ->whereIn('transport_passenger_id', $passengers->pluck('id'))
            ->whereDate('absence_date', $today)
            ->whereNull('cancelled_at')
            ->get()
            ->keyBy('transport_passenger_id');

        return response()->json([
            'data' => [
                'passengers' => $passengers->map(function (TransportPassenger $p) use ($absenceMap) {
                    $absence = $absenceMap->get($p->id);

                    return [
                        'id' => (int) $p->id,
                        'name' => $p->full_name,
                        'phone' => $p->phone,
                        'pickup_address' => $p->pickup_address,
                        'absent_today' => $absence !== null,
                        'absence_reason' => $absence?->reason,
                    ];
                })->values(),
            ],
        ]);
    }

    public function today(Request $request, TaxiContractPortalAccessService $access): JsonResponse
    {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');
        $passengers = $access->visiblePassengers($conn, $context);

        $tz = ContractTransportTimezone::TIMEZONE;
        $day = now($tz)->startOfDay();
        $customer = TransportCustomer::on($conn)->find($context['transport_customer_id']);

        $items = $this->buildDayItems($conn, $passengers, $day);

        return response()->json([
            'data' => [
                'date' => $day->toDateString(),
                'customer_name' => $customer?->name,
                'destination_summary' => $this->destinationSummary($items, $customer),
                'navigation' => ContractPortalNavigationRoute::fromDayItems($items),
                'items' => $items,
            ],
        ]);
    }

    public function week(
        Request $request,
        TaxiContractPortalAccessService $access,
        TransportScheduleExceptionService $exceptions
    ): JsonResponse {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');
        $passengers = $access->visiblePassengers($conn, $context);

        $tz = ContractTransportTimezone::TIMEZONE;
        $today = now($tz)->startOfDay();

        $fromInput = $request->query('from');
        try {
            $from = $fromInput
                ? Carbon::parse((string) $fromInput, $tz)->startOfWeek(Carbon::MONDAY)
                : $today->copy()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            $from = $today->copy()->startOfWeek(Carbon::MONDAY);
        }

        $earliest = $today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(self::WEEKS_BACK);
        $latest = $today->copy()->startOfWeek(Carbon::MONDAY)->addWeeks(self::WEEKS_AHEAD);
        if ($from->lt($earliest)) {
            $from = $earliest;
        }
        if ($from->gt($latest)) {
            $from = $latest;
        }

        $to = $from->copy()->endOfWeek(Carbon::SUNDAY);
        $customer = TransportCustomer::on($conn)->find($context['transport_customer_id']);
        $companyId = (int) ($customer?->company_id ?? $context['company_id'] ?? 0);

        $passengerIds = $passengers->pluck('id')->all();
        $absenceRows = TransportPassengerAbsence::on($conn)
            ->whereIn('transport_passenger_id', $passengerIds)
            ->whereNull('cancelled_at')
            ->whereBetween('absence_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (TransportPassengerAbsence $a) => $a->absence_date->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dateString = $d->toDateString();
            $dayAbsences = ($absenceRows->get($dateString) ?? collect())->keyBy('transport_passenger_id');
            $dayItems = $this->buildDayItems($conn, $passengers, $d->copy()->startOfDay(), $dayAbsences);

            $mappedItems = $dayItems->map(function (array $item) use (
                $conn,
                $exceptions,
                $companyId,
                $passengers,
                $d,
                $dayAbsences
            ) {
                $passenger = $passengers->firstWhere('id', $item['passenger_id']);
                $contractId = $passenger?->transport_contract_id
                    ? (int) $passenger->transport_contract_id
                    : null;
                $isException = $companyId > 0 && $exceptions->isExceptionDate(
                    $conn,
                    $companyId,
                    $d->copy(),
                    $contractId
                );
                $absent = $dayAbsences->has($item['passenger_id']);
                $legs = $item['legs'] ?? [];
                $allLegsAbsent = is_array($legs) && ContractPortalRideStatus::allLegsAbsent($legs);

                $dayStatus = 'scheduled';
                if ($absent || $allLegsAbsent || ($item['status_key'] ?? '') === 'absent') {
                    $dayStatus = 'absent';
                } elseif ($isException) {
                    $dayStatus = 'exception';
                } elseif ($legs === []) {
                    $dayStatus = 'none';
                } elseif (($item['status_key'] ?? '') === 'expired') {
                    $dayStatus = 'expired';
                }

                return [
                    'passenger_id' => $item['passenger_id'],
                    'name' => $item['name'],
                    'day_status' => $dayStatus,
                    'day_status_label' => match ($dayStatus) {
                        'absent' => 'Afgemeld',
                        'exception' => 'Geen vervoer',
                        'none' => 'Geen rit',
                        'expired' => 'Verlopen',
                        default => 'Gepland',
                    },
                    'legs' => $item['legs'],
                    'pickup_address' => $item['pickup_address'] ?? null,
                    'destination_address' => $item['destination_address'] ?? null,
                    'planned_at' => $item['planned_at'] ?? null,
                    'status_key' => $item['status_key'] ?? $dayStatus,
                    'picked_up' => (bool) ($item['picked_up'] ?? false),
                    'destination_reached' => (bool) ($item['destination_reached'] ?? false),
                    'can_cancel' => $item['can_cancel'],
                    'absence_id' => $item['absence_id'],
                    'absence_reason' => $item['absence_reason'],
                ];
            })->values();

            $days[] = [
                'date' => $dateString,
                'is_today' => $dateString === $today->toDateString(),
                'ride_count' => $mappedItems->sum(
                    fn (array $item) => ContractPortalRideStatus::countableRideCount($item)
                ),
                'items' => $mappedItems,
            ];
        }

        return response()->json([
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'customer_name' => $customer?->name,
                'days' => $days,
            ],
        ]);
    }

    public function announcements(Request $request): JsonResponse
    {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');
        $customerId = (int) ($context['transport_customer_id'] ?? 0);

        app(TaxiContractvervoerSchemaService::class)
            ->ensureContractPortalTables($conn);

        $rows = TransportAnnouncement::on($conn)
            ->activeForCustomer($customerId)
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'announcements' => $rows->map(fn (TransportAnnouncement $a) => [
                    'id' => (int) $a->id,
                    'title' => $a->title,
                    'body' => $a->body,
                    'severity' => $a->severity,
                    'starts_at' => $a->starts_at?->toIso8601String(),
                    'ends_at' => $a->ends_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    public function storeAbsence(
        Request $request,
        int $passenger,
        TaxiContractPortalAccessService $access,
        TransportPassengerAbsenceService $absences
    ): JsonResponse {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');

        if (! $access->canAccessPassenger($conn, $context, $passenger)) {
            return response()->json(['message' => 'Geen toegang tot deze passagier.'], 403);
        }

        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $fromRaw = $data['date_from'] ?? $data['date'] ?? null;
        if (! $fromRaw) {
            return response()->json([
                'message' => 'Kies een van-datum.',
                'errors' => ['date_from' => ['Kies een van-datum.']],
            ], 422);
        }

        $model = TransportPassenger::on($conn)->findOrFail($passenger);
        $from = Carbon::parse($fromRaw);
        $to = Carbon::parse($data['date_to'] ?? $fromRaw);

        try {
            $created = $absences->createRange(
                $conn,
                $request->user(),
                $model,
                $from,
                $to,
                $data['reason'] ?? null
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        $first = $created->first();
        $last = $created->last();
        $days = $created->count();

        return response()->json([
            'message' => $days === 1
                ? 'Passagier afgemeld.'
                : "Passagier afgemeld voor {$days} dagen.",
            'data' => [
                'id' => (int) $first->id,
                'date' => $first->absence_date->toDateString(),
                'date_from' => $first->absence_date->toDateString(),
                'date_to' => $last->absence_date->toDateString(),
                'days' => $days,
                'ids' => $created->pluck('id')->map(fn ($id) => (int) $id)->values(),
                'reason' => $first->reason,
            ],
        ]);
    }

    public function destroyAbsence(
        Request $request,
        int $absence,
        TaxiContractPortalAccessService $access,
        TransportPassengerAbsenceService $absences
    ): JsonResponse {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');

        $model = TransportPassengerAbsence::on($conn)->findOrFail($absence);
        if (! $access->canAccessPassenger($conn, $context, (int) $model->transport_passenger_id)) {
            return response()->json(['message' => 'Geen toegang tot deze afmelding.'], 403);
        }

        try {
            $absences->cancel($conn, $model);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['message' => 'Afmelding ingetrokken.']);
    }

    public function absences(Request $request, TaxiContractPortalAccessService $access): JsonResponse
    {
        $conn = $request->attributes->get('taxi_contract_conn');
        $context = $request->attributes->get('taxi_contract_context');
        $passengers = $access->visiblePassengers($conn, $context);
        $tz = config('app.timezone', 'Europe/Amsterdam');
        $from = now($tz)->startOfDay();
        $to = $from->copy()->addDays(TransportPassengerAbsenceService::MAX_DAYS_AHEAD);

        $rows = TransportPassengerAbsence::on($conn)
            ->whereIn('transport_passenger_id', $passengers->pluck('id'))
            ->whereNull('cancelled_at')
            ->whereBetween('absence_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('absence_date')
            ->get();

        $names = $passengers->keyBy('id');

        return response()->json([
            'data' => [
                'absences' => $rows->map(function (TransportPassengerAbsence $a) use ($names) {
                    $p = $names->get($a->transport_passenger_id);

                    return [
                        'id' => (int) $a->id,
                        'passenger_id' => (int) $a->transport_passenger_id,
                        'passenger_name' => $p?->full_name ?? '—',
                        'date' => $a->absence_date->toDateString(),
                        'reason' => $a->reason,
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, TransportPassenger>  $passengers
     * @param  Collection<int, TransportPassengerAbsence>|null  $absenceMap
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDayItems(
        string $conn,
        Collection $passengers,
        Carbon $dayStart,
        ?Collection $absenceMap = null
    ): Collection {
        $passengerIds = $passengers->pluck('id')->all();
        $tz = $dayStart->getTimezone()->getName() ?: ContractTransportTimezone::TIMEZONE;
        $wallStart = $dayStart->copy()->timezone($tz)->startOfDay();
        $wallEnd = $wallStart->copy()->endOfDay();
        $start = ContractTransportTimezone::naiveUtcForWallClockQuery($wallStart);
        $end = ContractTransportTimezone::naiveUtcForWallClockQuery($wallEnd);

        if ($absenceMap === null) {
            $absenceMap = TransportPassengerAbsence::on($conn)
                ->whereIn('transport_passenger_id', $passengerIds)
                ->whereDate('absence_date', $start->toDateString())
                ->whereNull('cancelled_at')
                ->get()
                ->keyBy('transport_passenger_id');
        }

        $pickupStops = RideStop::on($conn)
            ->whereIn('transport_passenger_id', $passengerIds)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('planned_at', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('planned_at')
                            ->whereHas('ride', fn ($rq) => $rq->whereBetween('pickup_at', [$start, $end]));
                    });
            })
            ->whereHas('ride', function ($rq) {
                $rq->whereNotIn('status', [RideRequest::STATUS_CANCELLED])
                    ->whereIn('ride_type', [
                        RideRequest::RIDE_TYPE_CONTRACT_GROUP,
                        RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
                    ]);
            })
            ->with('ride')
            ->orderBy('planned_at')
            ->get();

        $rideIds = $pickupStops->pluck('ride_request_id')->filter()->unique()->values()->all();
        $destinationsByRideId = collect();
        if ($rideIds !== []) {
            $destinationsByRideId = RideStop::on($conn)
                ->whereIn('ride_request_id', $rideIds)
                ->where('stop_type', RideStop::STOP_TYPE_DESTINATION)
                ->orderBy('sequence')
                ->get()
                ->groupBy('ride_request_id');
        }

        $stopsByPassenger = $pickupStops->groupBy('transport_passenger_id');

        return $passengers->map(function (TransportPassenger $p) use ($stopsByPassenger, $destinationsByRideId, $absenceMap, $tz) {
            $absence = $absenceMap->get($p->id);
            $stops = ($stopsByPassenger->get($p->id) ?? collect())
                ->filter(function (RideStop $stop) {
                    $ride = $stop->ride;
                    if (! $ride) {
                        return true;
                    }

                    return $ride->status !== RideRequest::STATUS_CANCELLED;
                })
                ->values();
            $legs = $stops->map(function (RideStop $stop) use ($destinationsByRideId, $absence, $tz, $p) {
                $ride = $stop->ride;
                $destination = null;
                if ($stop->ride_request_id) {
                    $rideDestinations = $destinationsByRideId->get($stop->ride_request_id) ?? collect();
                    $destination = $rideDestinations->first(
                        fn (RideStop $d) => (int) ($d->transport_passenger_id ?? 0) === (int) $p->id
                    ) ?: $rideDestinations->last();
                }
                $statusKey = $this->statusKey($stop, $ride, $destination, $absence !== null);
                $plannedAt = ContractTransportTimezone::asAmsterdamWall(
                    $stop->planned_at ?? $ride?->pickup_at
                );
                $destinationAt = ContractTransportTimezone::asAmsterdamWall($destination?->planned_at);
                [$legKey, $legLabel] = ContractPortalLegLabel::forPlannedAt($plannedAt, $tz);

                return [
                    'ride_stop_id' => (int) $stop->id,
                    'leg_key' => $legKey,
                    'leg_label' => $legLabel,
                    'pickup_address' => $stop->address ?: $p->pickup_address,
                    'pickup_lat' => $this->coordValue($stop->lat ?? $p->pickup_lat),
                    'pickup_lng' => $this->coordValue($stop->lng ?? $p->pickup_lng),
                    'destination_address' => $destination?->address
                        ?: ($ride?->dropoff_address ?? null),
                    'destination_lat' => $this->coordValue($destination?->lat ?? $ride?->dropoff_lat),
                    'destination_lng' => $this->coordValue($destination?->lng ?? $ride?->dropoff_lng),
                    'status' => $this->statusLabel($statusKey),
                    'status_key' => $statusKey,
                    'picked_up' => in_array($statusKey, ['picked_up', 'completed'], true),
                    'destination_reached' => $statusKey === 'completed',
                    'planned_at' => ContractTransportTimezone::toDriverIso8601($plannedAt),
                    'destination_at' => ContractTransportTimezone::toDriverIso8601($destinationAt),
                    'can_cancel' => $this->canCancelStop($stop, $absence !== null),
                ];
            })->values()->all();

            $legs = ContractPortalDayLegs::uniqueBySlot($legs);

            $anyCancel = $absence === null && (
                $legs === [] || collect($legs)->contains(fn (array $leg) => $leg['can_cancel'])
            );

            $primary = $legs[0] ?? null;

            return [
                'passenger_id' => (int) $p->id,
                'name' => $p->full_name,
                'pickup_address' => $p->pickup_address,
                'pickup_lat' => $this->coordValue($p->pickup_lat),
                'pickup_lng' => $this->coordValue($p->pickup_lng),
                'legs' => $legs,
                // Backward-compatible flat fields from first leg / absence.
                'destination_address' => $primary['destination_address'] ?? null,
                'status' => $absence
                    ? $this->statusLabel('absent')
                    : ($primary['status'] ?? $this->statusLabel('none')),
                'status_key' => $absence ? 'absent' : ($primary['status_key'] ?? 'none'),
                'picked_up' => (bool) ($primary['picked_up'] ?? false),
                'destination_reached' => (bool) ($primary['destination_reached'] ?? false),
                'planned_at' => $primary['planned_at'] ?? null,
                'destination_at' => $primary['destination_at'] ?? null,
                'can_cancel' => $anyCancel,
                'absence_id' => $absence?->id,
                'absence_reason' => $absence?->reason,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function destinationSummary(Collection $items, ?TransportCustomer $customer): ?string
    {
        $addresses = $items
            ->flatMap(function (array $item) {
                $legs = $item['legs'] ?? [];
                if ($legs === []) {
                    return [];
                }

                return collect($legs)
                    ->filter(fn (array $leg) => ! in_array($leg['status_key'] ?? '', ['completed', 'absent'], true))
                    ->pluck('destination_address');
            })
            ->filter(fn ($address) => is_string($address) && trim($address) !== '')
            ->map(fn ($address) => trim($address))
            ->unique()
            ->values();

        if ($addresses->count() === 1) {
            return $addresses->first();
        }
        if ($addresses->count() > 1) {
            return 'Meerdere bestemmingen';
        }
        if (is_string($customer?->name) && trim($customer->name) !== '') {
            return trim($customer->name);
        }

        return null;
    }

    private function coordValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }

    private function statusKey(
        ?RideStop $stop,
        ?RideRequest $ride,
        ?RideStop $destination,
        bool $absent
    ): string {
        if ($absent || ($stop && $stop->status === RideStop::STATUS_SKIPPED)) {
            return 'absent';
        }
        if (! $stop) {
            return 'none';
        }

        $destinationDone = $destination && in_array($destination->status, [
            RideStop::STATUS_COMPLETED,
            RideStop::STATUS_PICKED_UP,
            RideStop::STATUS_ARRIVED,
        ], true);
        $rideDone = $ride && $ride->status === RideRequest::STATUS_COMPLETED;

        if ($destinationDone || $rideDone || $stop->status === RideStop::STATUS_COMPLETED) {
            return 'completed';
        }

        $key = match ($stop->status) {
            RideStop::STATUS_PICKED_UP => 'picked_up',
            RideStop::STATUS_ARRIVED => 'arrived',
            default => ($ride && $ride->status === RideRequest::STATUS_ASSIGNED) ? 'en_route' : 'planned',
        };

        return ContractPortalRideStatus::applyExpiry(
            $key,
            ContractTransportTimezone::asAmsterdamWall($stop->planned_at ?? $ride?->pickup_at)
        );
    }

    private function statusLabel(string $statusKey): string
    {
        return ContractPortalRideStatus::label($statusKey);
    }

    private function canCancelStop(?RideStop $stop, bool $alreadyAbsent): bool
    {
        if ($alreadyAbsent) {
            return false;
        }
        if (! $stop) {
            return true;
        }

        return in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true);
    }
}
