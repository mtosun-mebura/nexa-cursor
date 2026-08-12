<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportPassengerAbsence;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TransportPassengerAbsenceService
{
    public const MAX_DAYS_AHEAD = 14;

    public function __construct(
        protected TaxiDriverInboxPushService $driverPush,
        protected TransportRoutePlannerService $routePlanner,
    ) {}

    public function create(
        string $conn,
        User $actor,
        TransportPassenger $passenger,
        CarbonInterface $date,
        ?string $reason = null
    ): TransportPassengerAbsence {
        $created = $this->createRange($conn, $actor, $passenger, $date, $date, $reason);

        return $created->first();
    }

    /**
     * Meld af voor een aaneengesloten periode (inclusief van én tot).
     * Per kalenderdag één afmeldingsrecord + route-sync (skipped stops).
     *
     * @return \Illuminate\Support\Collection<int, TransportPassengerAbsence>
     */
    public function createRange(
        string $conn,
        User $actor,
        TransportPassenger $passenger,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?string $reason = null
    ) {
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $tz = config('app.timezone', 'Europe/Amsterdam');
        $from = Carbon::parse($dateFrom->toDateString(), $tz)->startOfDay();
        $to = Carbon::parse($dateTo->toDateString(), $tz)->startOfDay();
        $today = now($tz)->startOfDay();
        $max = $today->copy()->addDays(self::MAX_DAYS_AHEAD);

        if ($from->lt($today) || $from->gt($max)) {
            throw ValidationException::withMessages([
                'date_from' => ['Afmelden kan vanaf vandaag tot '.self::MAX_DAYS_AHEAD.' dagen vooruit.'],
            ]);
        }

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'date_to' => ['De tot-datum moet op of na de van-datum liggen.'],
            ]);
        }

        if ($to->gt($max)) {
            throw ValidationException::withMessages([
                'date_to' => ['Afmelden kan tot maximaal '.self::MAX_DAYS_AHEAD.' dagen vooruit.'],
            ]);
        }

        $reasonValue = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        $created = collect();
        $already = [];

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $existing = TransportPassengerAbsence::on($conn)
                ->where('transport_passenger_id', $passenger->id)
                ->whereDate('absence_date', $day->toDateString())
                ->first();

            if ($existing && $existing->cancelled_at === null) {
                $already[] = $day->toDateString();
                continue;
            }

            if ($existing) {
                $existing->update([
                    'reason' => $reasonValue,
                    'created_by_user_id' => $actor->id,
                    'cancelled_at' => null,
                ]);
                $absence = $existing->fresh();
            } else {
                $absence = TransportPassengerAbsence::on($conn)->create([
                    'company_id' => (int) $passenger->company_id,
                    'transport_passenger_id' => (int) $passenger->id,
                    'absence_date' => $day->toDateString(),
                    'reason' => $reasonValue,
                    'created_by_user_id' => $actor->id,
                    'cancelled_at' => null,
                ]);
            }

            $this->syncStopsForAbsence($conn, $passenger, $day->copy(), true);
            $created->push($absence);
        }

        if ($created->isEmpty()) {
            throw ValidationException::withMessages([
                'date_from' => $already === []
                    ? ['Geen dagen om af te melden.']
                    : ['Deze passagier is al afgemeld voor de gekozen periode.'],
            ]);
        }

        return $created->values();
    }

    public function cancel(string $conn, TransportPassengerAbsence $absence): TransportPassengerAbsence
    {
        if ($absence->cancelled_at !== null) {
            return $absence;
        }

        $day = Carbon::parse($absence->absence_date)->startOfDay();
        $today = now(config('app.timezone', 'Europe/Amsterdam'))->startOfDay();
        if ($day->lt($today)) {
            throw ValidationException::withMessages([
                'absence' => ['Afgemelde dagen in het verleden kun je niet meer intrekken.'],
            ]);
        }

        $absence->update(['cancelled_at' => now()]);
        $passenger = TransportPassenger::on($conn)->find($absence->transport_passenger_id);
        if ($passenger) {
            $this->syncStopsForAbsence($conn, $passenger, $day, false);
        }

        return $absence->fresh();
    }

    /**
     * Markeer ophaalstops als skipped (of herstel), herbereken chauffeur-route voor die dag,
     * en push update naar de chauffeur.
     */
    public function syncStopsForAbsence(
        string $conn,
        TransportPassenger $passenger,
        CarbonInterface $day,
        bool $markAbsent
    ): void {
        $start = Carbon::parse($day->toDateString(), config('app.timezone', 'Europe/Amsterdam'))->startOfDay();
        $end = $start->copy()->endOfDay();

        $stops = RideStop::on($conn)
            ->where('transport_passenger_id', $passenger->id)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('planned_at', [$start, $end])
                    ->orWhereHas('ride', function ($rq) use ($start, $end) {
                        $rq->whereBetween('pickup_at', [$start, $end]);
                    });
            })
            ->get();

        $affectedRideIds = [];

        foreach ($stops as $stop) {
            if ($markAbsent) {
                if (! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
                    continue;
                }
                $stop->update([
                    'status' => RideStop::STATUS_SKIPPED,
                    'completed_at' => now(),
                ]);
            } else {
                if ($stop->status !== RideStop::STATUS_SKIPPED) {
                    continue;
                }
                $stop->update([
                    'status' => RideStop::STATUS_PLANNED,
                    'completed_at' => null,
                ]);
            }

            $affectedRideIds[(int) $stop->ride_request_id] = true;
        }

        // Individuele contractritten zonder RideStop (alleen passenger op de rit)
        $individualRides = RideRequest::on($conn)
            ->where('ride_type', RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL)
            ->where('transport_passenger_id', $passenger->id)
            ->whereBetween('pickup_at', [$start, $end])
            ->whereNotIn('status', [RideRequest::STATUS_COMPLETED, RideRequest::STATUS_CANCELLED])
            ->get();

        foreach ($individualRides as $ride) {
            $affectedRideIds[(int) $ride->id] = true;
        }

        foreach (array_keys($affectedRideIds) as $rideId) {
            $ride = RideRequest::on($conn)->find($rideId);
            if (! $ride || in_array($ride->status, [
                RideRequest::STATUS_COMPLETED,
                RideRequest::STATUS_CANCELLED,
            ], true)) {
                continue;
            }

            $this->refreshDriverRouteForRide($conn, $ride, $day->toDateString());
            $this->notifyDriverOfAbsence($conn, $ride, $passenger, restored: ! $markAbsent);
        }
    }

    /**
     * Pas bekende afmeldingen toe bij het aanmaken van een groepsrit (stops + tijden).
     */
    public function applyAbsencesToNewGroupRide(string $conn, RideRequest $ride, string $date): void
    {
        $absentPassengerIds = TransportPassengerAbsence::on($conn)
            ->whereDate('absence_date', $date)
            ->whereNull('cancelled_at')
            ->pluck('transport_passenger_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($absentPassengerIds === []) {
            return;
        }

        $stops = RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->whereIn('transport_passenger_id', $absentPassengerIds)
            ->where('status', RideStop::STATUS_PLANNED)
            ->get();

        if ($stops->isEmpty()) {
            return;
        }

        foreach ($stops as $stop) {
            $stop->update([
                'status' => RideStop::STATUS_SKIPPED,
                'completed_at' => now(),
            ]);
        }

        $this->refreshDriverRouteForRide($conn, $ride->fresh(), $date);
    }

    /**
     * Herbereken stoptijden / ophaalpunt voor de rit van die dag (zonder de vaste weekroute te wijzigen).
     */
    public function refreshDriverRouteForRide(string $conn, RideRequest $ride, string $date): void
    {
        if ($ride->ride_type === RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL) {
            $this->refreshIndividualRideForAbsence($conn, $ride, $date);

            return;
        }

        if ($ride->ride_type !== RideRequest::RIDE_TYPE_CONTRACT_GROUP) {
            return;
        }

        $stops = RideStop::on($conn)
            ->where('ride_request_id', $ride->id)
            ->orderBy('sequence')
            ->get();

        if ($stops->isEmpty()) {
            return;
        }

        $activePickups = $stops
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->filter(fn (RideStop $s) => $s->status !== RideStop::STATUS_SKIPPED)
            ->values();

        $destination = $stops->firstWhere('stop_type', RideStop::STOP_TYPE_DESTINATION);

        if ($activePickups->isEmpty()) {
            // Iedereen afgemeld → rit annuleren
            $ride->update([
                'status' => RideRequest::STATUS_CANCELLED,
                'passengers' => 0,
            ]);
            if ($destination && $destination->status === RideStop::STATUS_PLANNED) {
                $destination->update([
                    'status' => RideStop::STATUS_SKIPPED,
                    'completed_at' => now(),
                ]);
            }

            return;
        }

        $context = $this->resolveGroupRouteContext($conn, $ride);
        if ($context) {
            $ordered = $activePickups->map(fn (RideStop $stop) => [
                'stop_type' => RideStop::STOP_TYPE_PICKUP,
                'transport_passenger_id' => (int) $stop->transport_passenger_id,
                'passenger_name' => $stop->passenger_name,
                'address' => (string) $stop->address,
                'lat' => $stop->lat !== null ? (float) $stop->lat : null,
                'lng' => $stop->lng !== null ? (float) $stop->lng : null,
            ])->all();

            $result = $this->routePlanner->recalculateTimesForOrder(
                $context['group'],
                $context['template'],
                $ordered
            );

            foreach ($result['stops'] as $planned) {
                if (($planned['stop_type'] ?? '') === RideStop::STOP_TYPE_PICKUP) {
                    $passengerId = (int) ($planned['transport_passenger_id'] ?? 0);
                    $stop = $activePickups->first(
                        fn (RideStop $s) => (int) $s->transport_passenger_id === $passengerId
                    );
                    if (! $stop || ! in_array($stop->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true)) {
                        continue;
                    }
                    $stop->update([
                        'planned_at' => ContractTransportTimezone::parseLocalDateTime(
                            $date,
                            (string) $planned['planned_at_time']
                        ),
                    ]);
                }

                if (($planned['stop_type'] ?? '') === RideStop::STOP_TYPE_DESTINATION && $destination) {
                    $destination->update([
                        'planned_at' => ContractTransportTimezone::parseLocalDateTime(
                            $date,
                            (string) $planned['planned_at_time']
                        ),
                    ]);
                }
            }

            $departureTime = $result['departure_time'] ?? null;
            if ($departureTime) {
                $ride->pickup_at = ContractTransportTimezone::parseLocalDateTime($date, (string) $departureTime);
            }
        }

        $firstActive = $activePickups
            ->sortBy(fn (RideStop $s) => [(int) $s->sequence, (int) $s->id])
            ->first(fn (RideStop $s) => in_array($s->status, [RideStop::STATUS_PLANNED, RideStop::STATUS_ARRIVED], true))
            ?? $activePickups->first();

        $ride->fill([
            'pickup_address' => $firstActive?->address ?? $ride->pickup_address,
            'pickup_lat' => $firstActive?->lat ?? $ride->pickup_lat,
            'pickup_lng' => $firstActive?->lng ?? $ride->pickup_lng,
            'passengers' => $activePickups->count(),
        ]);
        $ride->save();
    }

    private function refreshIndividualRideForAbsence(string $conn, RideRequest $ride, string $date): void
    {
        $isAbsent = TransportPassengerAbsence::on($conn)
            ->where('transport_passenger_id', (int) $ride->transport_passenger_id)
            ->whereDate('absence_date', $date)
            ->whereNull('cancelled_at')
            ->exists();

        if ($isAbsent) {
            if (! in_array($ride->status, [RideRequest::STATUS_COMPLETED, RideRequest::STATUS_CANCELLED], true)) {
                $ride->update(['status' => RideRequest::STATUS_CANCELLED]);
            }

            return;
        }

        // Afmelding ingetrokken: heropen geannuleerde rit indien nog vandaag/toekomst
        if ($ride->status === RideRequest::STATUS_CANCELLED) {
            $ride->update(['status' => RideRequest::STATUS_ACCEPTED]);
        }
    }

    /**
     * @return array{group: TransportGroup, template: TransportRouteTemplate}|null
     */
    private function resolveGroupRouteContext(string $conn, RideRequest $ride): ?array
    {
        $occurrence = TransportOccurrence::on($conn)
            ->where('ride_request_id', $ride->id)
            ->first();

        if (! $occurrence?->transport_route_template_id) {
            return null;
        }

        $template = TransportRouteTemplate::on($conn)
            ->with('group')
            ->find($occurrence->transport_route_template_id);

        if (! $template || ! $template->group) {
            return null;
        }

        return [
            'group' => $template->group,
            'template' => $template,
        ];
    }

    private function notifyDriverOfAbsence(
        string $conn,
        RideRequest $ride,
        TransportPassenger $passenger,
        bool $restored = false
    ): void {
        if ((int) ($ride->driver_id ?? 0) <= 0) {
            return;
        }

        $this->driverPush->notifyDriver((int) $ride->driver_id, (int) $ride->id);

        Cache::put(
            'taxi_driver_absence_alert:'.(int) $ride->driver_id,
            [
                'ride_request_id' => (int) $ride->id,
                'passenger_name' => $passenger->full_name,
                'restored' => $restored,
                'route_updated' => true,
                'at' => now()->toIso8601String(),
                'message' => $restored
                    ? ($passenger->full_name.' is weer aanwezig — route bijgewerkt, wel ophalen.')
                    : ($passenger->full_name.' is afgemeld — route bijgewerkt, niet ophalen.'),
            ],
            300
        );
    }
}
