<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\DriverSchedule;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiDriverScheduleSchema;
use App\Support\UserAgendaColor;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DriverScheduleService
{
    public const AVAILABILITY_OCCUPANCY_MINUTES = 30;

    public const OVERLAP_HORIZON_YEARS = 2;

    public const EXPAND_MAX_DAYS = 1100;

    public function __construct(
        protected TaxiDriverEligibilityService $eligibility
    ) {}

    public function ensureReady(string $connection): void
    {
        TaxiDriverScheduleSchema::ensureTable($connection);
    }

    /**
     * @return Collection<int, User>
     */
    public function chauffeursForCompany(int $companyId): Collection
    {
        if ($companyId <= 0) {
            return collect();
        }

        return $this->eligibility->buildChauffeurQuery($companyId)
            ->get(['id', 'first_name', 'last_name', 'agenda_color', 'company_id']);
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function vehiclesForCompany(string $connection, int $companyId, bool $activeOnly = true): Collection
    {
        if ($companyId <= 0) {
            return collect();
        }

        return Vehicle::on($connection)
            ->where('company_id', $companyId)
            ->when($activeOnly, fn ($q) => $q->where('active', true))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<DriverSchedule>
     */
    public function createShifts(
        string $connection,
        int $companyId,
        int $driverId,
        int $vehicleId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $notes,
        bool $repeatWeekly,
        ?string $repeatUntil,
        ?int $createdByUserId,
        array $weekdays = []
    ): array {
        $this->ensureReady($connection);
        $this->assertDriverAndVehicle($connection, $companyId, $driverId, $vehicleId);

        $weekdays = $this->normalizeWeekdays($weekdays, $date);
        $firstDate = $this->firstDateOnOrAfter($date, $weekdays);
        $first = $this->parseWindow($firstDate, $startTime, $endTime);
        [$repeatWeekly, $until] = $this->normalizeRecurrence($repeatWeekly, $repeatUntil);
        $horizonEnd = $this->overlapHorizonEnd($first['starts'], true, $until);

        $this->assertWindowsNoOverlap(
            $connection,
            $companyId,
            $driverId,
            $vehicleId,
            $this->expandFromRule($weekdays, $startTime, $endTime, $firstDate, true, $until, $first['starts'], $horizonEnd)
        );

        $row = DriverSchedule::on($connection)->create([
            'company_id' => $companyId,
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'starts_at' => $first['starts']->format('Y-m-d H:i:s'),
            'ends_at' => $first['ends']->format('Y-m-d H:i:s'),
            'series_id' => null,
            'weekdays' => $this->encodeWeekdays($weekdays),
            'repeat_weekly' => $repeatWeekly,
            'repeat_until' => $until,
            'notes' => $notes,
            'created_by_user_id' => $createdByUserId,
        ]);

        return [$row];
    }

    public function updateShift(
        DriverSchedule $schedule,
        string $connection,
        int $driverId,
        int $vehicleId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $notes,
        array $weekdays = [],
        bool $repeatWeekly = false,
        ?string $repeatUntil = null
    ): DriverSchedule {
        $this->ensureReady($connection);
        $companyId = (int) $schedule->company_id;
        $this->assertDriverAndVehicle($connection, $companyId, $driverId, $vehicleId);

        $weekdays = $this->normalizeWeekdays($weekdays, $date);
        $firstDate = $this->firstDateOnOrAfter($date, $weekdays);
        $window = $this->parseWindow($firstDate, $startTime, $endTime);
        [$repeatWeekly, $until] = $this->normalizeRecurrence($repeatWeekly, $repeatUntil);
        $horizonEnd = $this->overlapHorizonEnd($window['starts'], true, $until);

        $this->assertWindowsNoOverlap(
            $connection,
            $companyId,
            $driverId,
            $vehicleId,
            $this->expandFromRule($weekdays, $startTime, $endTime, $firstDate, true, $until, $window['starts'], $horizonEnd),
            (int) $schedule->id
        );

        $schedule->fill([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'starts_at' => $window['starts']->format('Y-m-d H:i:s'),
            'ends_at' => $window['ends']->format('Y-m-d H:i:s'),
            'weekdays' => $this->encodeWeekdays($weekdays),
            'repeat_weekly' => $repeatWeekly,
            'repeat_until' => $until,
            'notes' => $notes,
        ]);
        $schedule->save();

        return $schedule;
    }

    public function currentForDriver(string $connection, int $companyId, int $driverId, ?CarbonInterface $at = null): ?DriverSchedule
    {
        $this->ensureReady($connection);
        if ($companyId <= 0 || $driverId <= 0) {
            return null;
        }

        $now = $this->naiveNow($at);
        $nowWall = $at?->copy()->timezone(ContractTransportTimezone::TIMEZONE) ?? now(ContractTransportTimezone::TIMEZONE);
        $from = $nowWall->copy()->startOfDay();
        $to = $nowWall->copy()->endOfDay();

        $candidates = DriverSchedule::on($connection)
            ->where('company_id', $companyId)
            ->where('driver_id', $driverId)
            ->get();

        foreach ($candidates as $row) {
            foreach ($this->expandWindows($row, $from, $to) as $window) {
                $startNaive = ContractTransportTimezone::naiveUtcForWallClockQuery($window['starts']);
                $endNaive = ContractTransportTimezone::naiveUtcForWallClockQuery($window['ends']);
                if ($startNaive->lte($now) && $endNaive->gt($now)) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Uitgebreide diensten van deze chauffeur, gegroepeerd per kalenderdag.
     *
     * @return array<string, list<array{
     *     start: string,
     *     end: string,
     *     start_time: string,
     *     end_time: string,
     *     vehicle_id: int,
     *     vehicle_label: string,
     *     notes: ?string
     * }>>
     */
    public function planningShiftsForDriver(
        string $connection,
        int $companyId,
        int $driverId,
        CarbonInterface $from,
        CarbonInterface $to
    ): array {
        $this->ensureReady($connection);
        if ($companyId <= 0 || $driverId <= 0 || ! TaxiDriverScheduleSchema::tableExists($connection)) {
            return [];
        }

        $rangeStart = $from->copy()->timezone(ContractTransportTimezone::TIMEZONE)->startOfDay();
        $rangeEnd = $to->copy()->timezone(ContractTransportTimezone::TIMEZONE)->endOfDay();
        $fromDate = $rangeStart->toDateString();
        $toNaive = ContractTransportTimezone::naiveUtcForWallClockQuery($rangeEnd);

        $schedules = DriverSchedule::on($connection)
            ->where('company_id', $companyId)
            ->where('driver_id', $driverId)
            ->where('starts_at', '<', $toNaive)
            ->where(function ($q) use ($fromDate) {
                $q->where('repeat_weekly', false)
                    ->orWhereNull('repeat_weekly')
                    ->orWhereNull('repeat_until')
                    ->orWhereDate('repeat_until', '>=', $fromDate);
            })
            ->orderBy('starts_at')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $vehiclesById = collect();
        if (Schema::connection($connection)->hasTable('vehicles')) {
            $vehicleIds = $schedules->pluck('vehicle_id')->filter()->unique()->values();
            if ($vehicleIds->isNotEmpty()) {
                $vehiclesById = Vehicle::on($connection)
                    ->whereIn('id', $vehicleIds)
                    ->get()
                    ->keyBy('id');
            }
        }

        $grouped = [];
        foreach ($schedules as $schedule) {
            $vehicle = $vehiclesById->get((int) $schedule->vehicle_id);
            $vehicleLabel = $vehicle ? $vehicle->fleetLabel() : 'Onbekend voertuig';
            $notes = trim((string) ($schedule->notes ?? ''));
            if (mb_strlen($notes) > 80) {
                $notes = mb_substr($notes, 0, 77).'…';
            }

            foreach ($this->expandWindows($schedule, $rangeStart, $rangeEnd) as $window) {
                $date = $window['starts']->toDateString();
                $grouped[$date][] = [
                    'start' => $window['starts']->format('Y-m-d\TH:i:s'),
                    'end' => $window['ends']->format('Y-m-d\TH:i:s'),
                    'start_time' => $window['starts']->format('H:i'),
                    'end_time' => $window['ends']->format('H:i'),
                    'vehicle_id' => (int) $schedule->vehicle_id,
                    'vehicle_label' => $vehicleLabel,
                    'notes' => $notes !== '' ? $notes : null,
                ];
            }
        }

        foreach ($grouped as &$windows) {
            usort($windows, fn (array $a, array $b) => strcmp($a['start'], $b['start']));
        }
        unset($windows);

        return $grouped;
    }

    /**
     * Voertuigen die nu niet door deze chauffeur gekozen mogen worden.
     *
     * @return list<int>
     */
    public function occupiedVehicleIdsForOthers(string $connection, int $companyId, int $driverId, ?CarbonInterface $at = null): array
    {
        $this->ensureReady($connection);
        $nowWall = $at?->copy()->timezone(ContractTransportTimezone::TIMEZONE) ?? now(ContractTransportTimezone::TIMEZONE);
        $nowNaive = $this->naiveNow($nowWall);
        $ids = [];

        $scheduledRows = DriverSchedule::on($connection)
            ->where('company_id', $companyId)
            ->where('driver_id', '!=', $driverId)
            ->get();

        $from = $nowWall->copy()->startOfDay();
        $to = $nowWall->copy()->endOfDay();
        foreach ($scheduledRows as $row) {
            foreach ($this->expandWindows($row, $from, $to) as $window) {
                $startNaive = ContractTransportTimezone::naiveUtcForWallClockQuery($window['starts']);
                $endNaive = ContractTransportTimezone::naiveUtcForWallClockQuery($window['ends']);
                if ($startNaive->lte($nowNaive) && $endNaive->gt($nowNaive)) {
                    $ids[] = (int) $row->vehicle_id;
                    break;
                }
            }
        }

        if (TaxiDispatchSchema::driverAvailabilityExists($connection)
            && Schema::connection($connection)->hasColumn('driver_availability', 'vehicle_id')) {
            $cutoff = $nowWall->copy()->subMinutes(self::AVAILABILITY_OCCUPANCY_MINUTES);
            $taken = DriverAvailability::on($connection)
                ->where('company_id', $companyId)
                ->where('driver_id', '!=', $driverId)
                ->whereNotNull('vehicle_id')
                ->where(function ($q) use ($cutoff) {
                    $q->where('is_online', true)
                        ->orWhere('last_seen_at', '>=', $cutoff);
                })
                ->pluck('vehicle_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_merge($ids, $taken);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function isVehicleOccupiedByOther(
        string $connection,
        int $companyId,
        int $driverId,
        int $vehicleId,
        ?CarbonInterface $at = null
    ): bool {
        if ($vehicleId <= 0) {
            return false;
        }

        return in_array($vehicleId, $this->occupiedVehicleIdsForOthers($connection, $companyId, $driverId, $at), true);
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     locked: bool,
     *     assigned_vehicle: ?array<string, mixed>,
     *     assigned_until: ?string
     * }
     */
    public function vehiclesPayloadForDriver(string $connection, int $companyId, int $driverId): array
    {
        $this->ensureReady($connection);
        TaxiDispatchSchema::ensureVehicleIdColumn($connection);

        $locked = $this->currentForDriver($connection, $companyId, $driverId);
        $occupied = $this->occupiedVehicleIdsForOthers($connection, $companyId, $driverId);

        $vehicles = Vehicle::on($connection)
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'license_plate', 'type']);

        if ($locked) {
            $assigned = $vehicles->firstWhere('id', (int) $locked->vehicle_id)
                ?? Vehicle::on($connection)->whereKey((int) $locked->vehicle_id)->first();
            $mapped = $assigned ? [$this->mapVehicle($assigned)] : [];
            $nowWall = now(ContractTransportTimezone::TIMEZONE);
            $todayWindows = $this->expandWindows($locked, $nowWall->copy()->startOfDay(), $nowWall->copy()->endOfDay());
            $until = $todayWindows[0]['ends'] ?? ContractTransportTimezone::asAmsterdamWall($locked->ends_at);

            return [
                'data' => $mapped,
                'locked' => true,
                'assigned_vehicle' => $mapped[0] ?? null,
                'assigned_until' => $until?->format('H:i'),
            ];
        }

        $available = $vehicles
            ->reject(fn (Vehicle $vehicle) => in_array((int) $vehicle->id, $occupied, true))
            ->values()
            ->map(fn (Vehicle $vehicle) => $this->mapVehicle($vehicle))
            ->all();

        return [
            'data' => $available,
            'locked' => false,
            'assigned_vehicle' => null,
            'assigned_until' => null,
        ];
    }

    public function resolveLockedVehicleId(string $connection, int $companyId, int $driverId): ?int
    {
        $current = $this->currentForDriver($connection, $companyId, $driverId);

        return $current ? (int) $current->vehicle_id : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function agendaEvents(
        string $connection,
        ?int $companyId,
        string $start,
        string $end,
        ?int $driverId = null,
        ?int $vehicleId = null
    ): array {
        $this->ensureReady($connection);

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();
        $to = ContractTransportTimezone::naiveUtcForWallClockQuery(
            Carbon::parse($endDate->format('Y-m-d').' 23:59:59', ContractTransportTimezone::TIMEZONE)
        );

        $query = DriverSchedule::on($connection)->orderBy('starts_at');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($driverId) {
            $query->where('driver_id', $driverId);
        }
        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }

        $rangeStart = Carbon::parse($startDate->format('Y-m-d').' 00:00:00', ContractTransportTimezone::TIMEZONE);
        $rangeEnd = Carbon::parse($endDate->format('Y-m-d').' 23:59:59', ContractTransportTimezone::TIMEZONE);
        $fromDate = $rangeStart->toDateString();

        $query->where('starts_at', '<', $to)
            ->where(function ($q) use ($fromDate) {
                $q->where('repeat_weekly', false)
                    ->orWhereNull('repeat_weekly')
                    ->orWhereNull('repeat_until')
                    ->orWhereDate('repeat_until', '>=', $fromDate);
            });

        $schedules = $query->get();
        if ($schedules->isEmpty()) {
            return [];
        }

        $driverIds = $schedules->pluck('driver_id')->filter()->unique()->values();
        $driversById = User::query()
            ->whereIn('id', $driverIds)
            ->get(['id', 'first_name', 'last_name', 'agenda_color', 'company_id'])
            ->keyBy('id');

        $vehicleIds = $schedules->pluck('vehicle_id')->filter()->unique()->values();
        $vehiclesById = Vehicle::on($connection)
            ->whereIn('id', $vehicleIds)
            ->get()
            ->keyBy('id');

        $companyIds = $schedules->pluck('company_id')->filter()->unique()->values();
        $companiesById = Company::query()
            ->whereIn('id', $companyIds)
            ->get(['id', 'name', 'phone', 'street', 'house_number', 'house_number_extension', 'postal_code', 'city'])
            ->keyBy('id');

        $events = [];
        foreach ($schedules as $schedule) {
            $driver = $driversById->get((int) $schedule->driver_id);
            $driverName = $this->userDisplayName($driver);
            $vehicle = $vehiclesById->get((int) $schedule->vehicle_id);
            $vehicleLabel = $vehicle ? $vehicle->fleetLabel() : 'Onbekend voertuig';
            $company = $companiesById->get((int) $schedule->company_id);
            $color = UserAgendaColor::resolved($driver);
            $notes = trim((string) ($schedule->notes ?? ''));

            foreach ($this->expandWindows($schedule, $rangeStart, $rangeEnd) as $window) {
                $startWall = $window['starts'];
                $endWall = $window['ends'];
                $events[] = [
                    'id' => 'driver-schedule-'.$schedule->id.'-'.$startWall->format('Y-m-d'),
                    'title' => $driverName.' · '.$vehicleLabel,
                    'start' => $startWall->format('Y-m-d\TH:i:s'),
                    'end' => $endWall->format('Y-m-d\TH:i:s'),
                    'color' => $color,
                    'extendedProps' => [
                        'event_kind' => 'driver_schedule',
                        'schedule_id' => (int) $schedule->id,
                        'candidate_name' => $driverName,
                        'driver_id' => (int) $schedule->driver_id,
                        'driver_name' => $driverName,
                        'vehicle_id' => (int) $schedule->vehicle_id,
                        'vehicle_label' => $vehicleLabel,
                        'agenda_color' => $color,
                        'location' => $vehicleLabel,
                        'notes' => $notes,
                        'company_name' => $company->name ?? 'Onbekend bedrijf',
                        'company_phone' => $company->phone ?? '',
                        'scheduled_at' => $startWall->format('d-m-Y H:i'),
                        'duration' => max(15, (int) $startWall->diffInMinutes($endWall)),
                        'detail_url' => $this->scheduleEditUrl((int) $schedule->id),
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * @return array{starts: Carbon, ends: Carbon}
     */
    public function parseWindow(string $date, string $startTime, string $endTime): array
    {
        $starts = ContractTransportTimezone::parseLocalDateTime($date, $startTime);
        $ends = ContractTransportTimezone::parseLocalDateTime($date, $endTime);
        if ($ends->lte($starts)) {
            throw ValidationException::withMessages([
                'end_time' => 'Eindtijd moet na de starttijd liggen.',
            ]);
        }

        return ['starts' => $starts, 'ends' => $ends];
    }

    private function assertDriverAndVehicle(string $connection, int $companyId, int $driverId, int $vehicleId): void
    {
        if (! $this->eligibility->isChauffeurForCompany(User::query()->findOrFail($driverId), $companyId)) {
            throw ValidationException::withMessages([
                'driver_id' => 'Deze gebruiker is geen chauffeur van dit bedrijf.',
            ]);
        }

        $exists = Vehicle::on($connection)
            ->where('company_id', $companyId)
            ->whereKey($vehicleId)
            ->where('active', true)
            ->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Kies een actief voertuig van dit bedrijf.',
            ]);
        }
    }

    /**
     * @param  list<array{starts: Carbon, ends: Carbon}>  $windows
     */
    private function assertWindowsNoOverlap(
        string $connection,
        int $companyId,
        int $driverId,
        int $vehicleId,
        array $windows,
        ?int $exceptId = null
    ): void {
        if ($windows === []) {
            return;
        }

        $fromWall = $windows[0]['starts']->copy()->timezone(ContractTransportTimezone::TIMEZONE)->startOfDay();
        $toWall = $windows[0]['ends']->copy()->timezone(ContractTransportTimezone::TIMEZONE)->endOfDay();
        foreach ($windows as $window) {
            if ($window['starts']->lt($fromWall)) {
                $fromWall = $window['starts']->copy()->timezone(ContractTransportTimezone::TIMEZONE)->startOfDay();
            }
            if ($window['ends']->gt($toWall)) {
                $toWall = $window['ends']->copy()->timezone(ContractTransportTimezone::TIMEZONE)->endOfDay();
            }
        }

        $candidates = DriverSchedule::on($connection)
            ->where('company_id', $companyId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get();

        foreach ($candidates as $row) {
            foreach ($this->expandWindows($row, $fromWall, $toWall) as $existing) {
                foreach ($windows as $proposed) {
                    if (! $this->windowsOverlap($proposed['starts'], $proposed['ends'], $existing['starts'], $existing['ends'])) {
                        continue;
                    }
                    if ((int) $row->driver_id === $driverId) {
                        throw ValidationException::withMessages([
                            'driver_id' => 'Deze chauffeur is in dit tijdvak al ingepland.',
                        ]);
                    }
                    if ((int) $row->vehicle_id === $vehicleId) {
                        throw ValidationException::withMessages([
                            'vehicle_id' => 'Dit voertuig is in dit tijdvak al aan een chauffeur gekoppeld.',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param  list<int|string>  $weekdays
     * @return list<int>
     */
    public function normalizeWeekdays(array $weekdays, string $fallbackDate): array
    {
        $normalized = [];
        foreach ($weekdays as $day) {
            $value = (int) $day;
            if ($value >= 1 && $value <= 7) {
                $normalized[] = $value;
            }
        }
        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        if ($normalized === []) {
            $parsed = parse_admin_date($fallbackDate) ?: $fallbackDate;
            $normalized = [(int) Carbon::parse($parsed, ContractTransportTimezone::TIMEZONE)->isoWeekday()];
        }

        return $normalized;
    }

    /**
     * Gekozen dagen herhalen altijd wekelijks. De checkbox bepaalt alleen of een einddatum geldt.
     * Uitgevinkt, of een leeggemaakte einddatum, maakt de planning doorlopend.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function normalizeRecurrence(bool $repeatWeekly, ?string $repeatUntil): array
    {
        $until = $repeatUntil ? parse_admin_date($repeatUntil) : null;
        if ($until === '') {
            $until = null;
        }
        if (! $repeatWeekly) {
            return [false, null];
        }

        return [true, $until];
    }

    /**
     * @param  list<int>  $weekdays
     */
    public function firstDateOnOrAfter(string $date, array $weekdays): string
    {
        $parsed = parse_admin_date($date) ?: $date;
        $cursor = Carbon::parse($parsed, ContractTransportTimezone::TIMEZONE)->startOfDay();
        for ($i = 0; $i < 7; $i++) {
            if (in_array($cursor->isoWeekday(), $weekdays, true)) {
                return $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return Carbon::parse($parsed, ContractTransportTimezone::TIMEZONE)->toDateString();
    }

    /**
     * @param  list<int>  $weekdays
     */
    public function encodeWeekdays(array $weekdays): string
    {
        return implode(',', $weekdays);
    }

    /**
     * @return list<int>
     */
    public function parseWeekdays(?string $weekdays, Carbon $fallbackStart): array
    {
        $parts = array_filter(array_map('intval', explode(',', (string) $weekdays)));
        $parts = array_values(array_unique(array_filter($parts, fn (int $day) => $day >= 1 && $day <= 7)));
        if ($parts === []) {
            return [$fallbackStart->isoWeekday()];
        }
        sort($parts);

        return $parts;
    }

    /**
     * @return list<array{starts: Carbon, ends: Carbon}>
     */
    public function expandWindows(DriverSchedule $schedule, Carbon $from, Carbon $to): array
    {
        $startWall = ContractTransportTimezone::asAmsterdamWall($schedule->starts_at);
        $endWall = ContractTransportTimezone::asAmsterdamWall($schedule->ends_at);
        if (! $startWall || ! $endWall) {
            return [];
        }

        $weekdays = $this->parseWeekdays($schedule->weekdays ?? null, $startWall);
        $until = null;
        if ((bool) $schedule->repeat_weekly && $schedule->repeat_until) {
            $until = Carbon::parse($schedule->repeat_until, ContractTransportTimezone::TIMEZONE)->toDateString();
        }

        return $this->expandFromRule(
            $weekdays,
            $startWall->format('H:i'),
            $endWall->format('H:i'),
            $startWall->toDateString(),
            true,
            $until,
            $from,
            $to
        );
    }

    /**
     * @param  list<int>  $weekdays
     * @return list<array{starts: Carbon, ends: Carbon}>
     */
    public function expandFromRule(
        array $weekdays,
        string $startTime,
        string $endTime,
        string $anchorDate,
        bool $repeatWeekly,
        ?string $repeatUntil,
        Carbon $from,
        Carbon $to
    ): array {
        $anchor = Carbon::parse($anchorDate, ContractTransportTimezone::TIMEZONE)->startOfDay();
        $rangeStart = $from->copy()->timezone(ContractTransportTimezone::TIMEZONE)->startOfDay();
        $rangeEnd = $to->copy()->timezone(ContractTransportTimezone::TIMEZONE)->endOfDay();
        if ($repeatUntil) {
            $untilEnd = Carbon::parse($repeatUntil, ContractTransportTimezone::TIMEZONE)->endOfDay();
            if ($untilEnd->lt($rangeEnd)) {
                $rangeEnd = $untilEnd;
            }
        }

        if ($anchor->gt($rangeEnd)) {
            return [];
        }

        $windows = [];
        $seen = [];
        $cursor = $anchor->copy();
        if ($repeatWeekly && $rangeStart->gt($anchor)) {
            $cursor = $rangeStart->copy();
        }
        $guard = 0;
        $maxDays = $repeatWeekly ? self::EXPAND_MAX_DAYS : 8;

        while ($cursor->lte($rangeEnd) && $guard < $maxDays) {
            $iso = $cursor->isoWeekday();
            if (in_array($iso, $weekdays, true) && $cursor->gte($anchor)) {
                $seen[$iso] = true;
                if ($cursor->gte($rangeStart)) {
                    $windows[] = $this->parseWindow($cursor->toDateString(), $startTime, $endTime);
                }
            }
            if (! $repeatWeekly && count($seen) === count($weekdays)) {
                break;
            }
            $cursor->addDay();
            $guard++;
        }

        return $windows;
    }

    private function overlapHorizonEnd(Carbon $firstStart, bool $repeatWeekly, ?string $until): Carbon
    {
        if (! $repeatWeekly) {
            return $firstStart->copy()->addDays(6)->endOfDay();
        }
        if ($until) {
            return Carbon::parse($until, ContractTransportTimezone::TIMEZONE)->endOfDay();
        }

        return $firstStart->copy()->addYears(self::OVERLAP_HORIZON_YEARS)->endOfDay();
    }

    private function windowsOverlap(Carbon $startA, Carbon $endA, Carbon $startB, Carbon $endB): bool
    {
        return $startA->lt($endB) && $endA->gt($startB);
    }

    private function naiveNow(?CarbonInterface $at = null): Carbon
    {
        $wall = $at?->copy()->timezone(ContractTransportTimezone::TIMEZONE)
            ?? now(ContractTransportTimezone::TIMEZONE);

        return ContractTransportTimezone::naiveUtcForWallClockQuery($wall);
    }

    /**
     * @return array{id: int, name: string, license_plate: ?string, type: string}
     */
    private function mapVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => (int) $vehicle->id,
            'name' => (string) ($vehicle->name ?? ''),
            'license_plate' => trim((string) ($vehicle->license_plate ?? '')) ?: null,
            'type' => (string) ($vehicle->type ?? ''),
            'label' => $vehicle->fleetLabel(),
        ];
    }

    public function userDisplayName(?User $user): string
    {
        if (! $user) {
            return 'Onbekende chauffeur';
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : 'Onbekende chauffeur';
    }

    private function scheduleEditUrl(int $scheduleId): ?string
    {
        try {
            return route('admin.taxi.driver_schedules.edit', $scheduleId);
        } catch (\Throwable) {
            return null;
        }
    }
}
