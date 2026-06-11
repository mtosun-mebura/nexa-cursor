<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatIntent;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Voert uitsluitend vooraf goedgekeurde, tenant-gefilterde queries uit.
 */
final class AiChatLiveQueryService
{
    public function __construct(
        private readonly ModuleDatabaseService $moduleDatabaseService,
        private readonly AiChatSqlGuardService $sqlGuard,
        private readonly AiChatTaxiRoleQueryService $roleQueryService,
    ) {}

    /**
     * @param  array{
     *     company_id: int,
     *     user_id: ?int,
     *     intent: string,
     *     allow_live_data: bool,
     *     allow_public_rates: bool,
     *     exp: int,
     *     query_hint?: ?string,
     *     response_mode?: string
     * }  $claims
     * @return array{count: int, rows: list<array<string, mixed>>, summary?: array<string, mixed>}
     */
    public function execute(AiChatIntent $intent, array $claims): array
    {
        $this->sqlGuard->assertMayExecute($claims, $intent);

        $connection = $this->resolveTaxiConnection();
        if ($connection === null) {
            return ['count' => 0, 'rows' => []];
        }

        $companyId = (int) $claims['company_id'];
        $hint = $claims['query_hint'] ?? null;

        $result = match ($intent) {
            AiChatIntent::Tarieven => ['rows' => $this->publicRates($connection), 'summary' => null],
            AiChatIntent::MijnRit => ['rows' => $this->ownRides($connection, $companyId, (int) ($claims['user_id'] ?? 0), $hint), 'summary' => null],
            AiChatIntent::RittenMorgen => ['rows' => $this->ridesForDate($connection, $companyId, now()->addDay()->toDateString()), 'summary' => null],
            AiChatIntent::RittenVandaag => ['rows' => $this->ridesForDate($connection, $companyId, now()->toDateString()), 'summary' => null],
            AiChatIntent::RittenKomend => ['rows' => $this->upcomingRides($connection, $companyId), 'summary' => null],
            AiChatIntent::OpenRitten => ['rows' => $this->openRides($connection, $companyId), 'summary' => null],
            AiChatIntent::RittenGeannuleerd => ['rows' => $this->cancelledRides($connection, $companyId), 'summary' => null],
            AiChatIntent::RittenZonderChauffeur => ['rows' => $this->ridesWithoutDriver($connection, $companyId), 'summary' => null],
            AiChatIntent::RittenZonderVoertuig => ['rows' => $this->ridesWithoutVehicle($connection, $companyId), 'summary' => null],
            AiChatIntent::RittenLuchthavenMorgen => ['rows' => $this->airportRidesForDate($connection, $companyId, now()->addDay()->toDateString()), 'summary' => null],
            AiChatIntent::RittenVoorAchtUur => ['rows' => $this->ridesBeforeTime($connection, $companyId, '08:00'), 'summary' => null],
            AiChatIntent::RittenLang => ['rows' => $this->longRides($connection, $companyId), 'summary' => null],
            AiChatIntent::VrijeChauffeursMorgen => ['rows' => $this->availableDriversForDate($connection, $companyId, now()->addDay()->toDateString()), 'summary' => null],
            AiChatIntent::ChauffeursVandaag => ['rows' => $this->driversWithRidesOnDate($connection, $companyId, now()->toDateString()), 'summary' => null],
            AiChatIntent::ChauffeursMeesteRittenVandaag => ['rows' => $this->topDriversByRidesOnDate($connection, $companyId, now()->toDateString(), 5), 'summary' => null],
            AiChatIntent::ChauffeursZonderRit => ['rows' => $this->driversWithoutRideOnDate($connection, $companyId, now()->toDateString()), 'summary' => null],
            AiChatIntent::ChauffeursSchipholMorgen => ['rows' => $this->driversForAirportDate($connection, $companyId, now()->addDay()->toDateString()), 'summary' => null],
            AiChatIntent::ChauffeursOnderweg => ['rows' => $this->driversOnTrip($connection, $companyId, $hint), 'summary' => null],
            AiChatIntent::KlantenMeesteRitten => ['rows' => $this->topCustomersByRides($connection, $companyId, 10), 'summary' => null],
            AiChatIntent::KlantenDezeMaand => ['rows' => $this->customersWithRideInMonth($connection, $companyId, now()), 'summary' => null],
            AiChatIntent::KlantenLuchthaven => ['rows' => $this->customersWithAirportRide($connection, $companyId), 'summary' => null],
            AiChatIntent::KlantenGeannuleerd => ['rows' => $this->customersWithCancelledRide($connection, $companyId), 'summary' => null],
            AiChatIntent::KlantenNieuwDezeMaand => ['rows' => $this->newCustomersThisMonth($connection, $companyId), 'summary' => null],
            AiChatIntent::OmzetVandaag => ['rows' => [], 'summary' => $this->revenueForDate($connection, $companyId, now()->toDateString(), 'vandaag')],
            AiChatIntent::OmzetMorgen => ['rows' => [], 'summary' => $this->revenueForDate($connection, $companyId, now()->addDay()->toDateString(), 'morgen')],
            AiChatIntent::OmzetVorigeMaand => ['rows' => [], 'summary' => $this->revenueForMonth($connection, $companyId, now()->subMonth())],
            AiChatIntent::RittenHoogsteOmzet => ['rows' => $this->topRidesByRevenue($connection, $companyId), 'summary' => null],
            AiChatIntent::LuchthavenrittenDezeMaand => ['rows' => $this->airportRidesCompletedThisMonth($connection, $companyId), 'summary' => null],
            AiChatIntent::Planning => ['rows' => $this->planningQuery($connection, $companyId, $hint), 'summary' => null],
            AiChatIntent::VoertuigenMorgen => ['rows' => $this->vehiclesScheduledForDate($connection, $companyId, now()->addDay()->toDateString()), 'summary' => null],
            AiChatIntent::VoertuigenBeschikbaar => ['rows' => $this->availableVehicles($connection, $companyId), 'summary' => null],
            default => throw new RuntimeException('Geen live query beschikbaar voor intent '.$intent->value),
        };

        $rows = $result['rows'];

        $count = count($rows);
        if (is_array($result['summary'] ?? null) && isset($result['summary']['ride_count'])) {
            $count = (int) $result['summary']['ride_count'];
        }

        return [
            'count' => $count,
            'rows' => $rows,
            'summary' => $result['summary'],
            'response_mode' => $claims['response_mode'] ?? 'list',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function publicRates(string $connection): array
    {
        $table = (string) config('ai_chat.public_rates_table', 'default_rates');

        if (! $this->tableExists($connection, $table)) {
            return [];
        }

        return DB::connection($connection)
            ->table($table)
            ->orderBy('person_range')
            ->limit(20)
            ->get([
                'id', 'person_range', 'base_fare', 'min_fare', 'price_per_km', 'price_per_min', 'cleaning_costs',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ownRides(string $connection, int $companyId, int $userId, ?string $hint): array
    {
        if ($userId <= 0 || ! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            return [];
        }

        $query = RideRequest::on($connection)->newQuery();
        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        $email = strtolower(trim((string) $user->email));
        $hasCustomerUserId = Schema::connection($connection)->hasColumn('ride_requests', 'customer_user_id');

        if (! $hasCustomerUserId && $email === '') {
            return [];
        }

        $query->where(function (Builder $q) use ($userId, $email, $hasCustomerUserId) {
            $matched = false;

            if ($hasCustomerUserId) {
                $q->where('customer_user_id', $userId);
                $matched = true;
            }

            if ($email !== '') {
                $method = $matched ? 'orWhere' : 'where';
                $q->{$method}(function (Builder $q2) use ($email, $hasCustomerUserId, $userId) {
                    $q2->whereRaw('LOWER(TRIM(customer_email)) = ?', [$email]);
                    if ($hasCustomerUserId) {
                        $q2->where(function (Builder $q3) use ($userId) {
                            $q3->whereNull('customer_user_id')
                                ->orWhere('customer_user_id', '!=', $userId);
                        });
                    }
                });
            }
        });

        match ($hint) {
            'vandaag' => $query
                ->whereDate('pickup_at', now()->toDateString())
                ->where('status', '<>', RideRequest::STATUS_CANCELLED)
                ->orderBy('pickup_at')
                ->limit(50),
            'morgen' => $query
                ->whereDate('pickup_at', now()->addDay()->toDateString())
                ->where('status', '<>', RideRequest::STATUS_CANCELLED)
                ->orderBy('pickup_at')
                ->limit(50),
            'aankomend', 'gepland' => $query
                ->whereNotIn('status', [RideRequest::STATUS_CANCELLED, RideRequest::STATUS_COMPLETED])
                ->where('pickup_at', '>=', now())
                ->orderBy('pickup_at')
                ->limit(100),
            'voltooid' => $query
                ->where('status', RideRequest::STATUS_COMPLETED)
                ->orderByDesc('pickup_at')
                ->limit(100),
            'factuur' => $query
                ->where('status', RideRequest::STATUS_COMPLETED)
                ->orderByDesc('pickup_at')
                ->limit(1),
            'prijs', 'volgende' => $query
                ->whereNotIn('status', [RideRequest::STATUS_CANCELLED, RideRequest::STATUS_COMPLETED])
                ->where('pickup_at', '>=', now())
                ->orderBy('pickup_at')
                ->limit(1),
            'ophaaltijd' => $query
                ->whereNotIn('status', [RideRequest::STATUS_CANCELLED, RideRequest::STATUS_COMPLETED])
                ->where('pickup_at', '>=', now()->subHour())
                ->orderBy('pickup_at')
                ->limit(1),
            default => $query
                ->whereNotIn('status', [RideRequest::STATUS_CANCELLED, RideRequest::STATUS_COMPLETED])
                ->where('pickup_at', '>=', now())
                ->orderBy('pickup_at')
                ->limit(10),
        };

        $rows = $query
            ->get($this->ownRideSelectColumns($connection))
            ->map(fn ($row) => (array) $row->toArray())
            ->all();

        return $this->enrichOwnRideRows($connection, $rows);
    }

    /**
     * @return list<string>
     */
    private function ownRideSelectColumns(string $connection): array
    {
        $columns = $this->rideSelectColumns();

        foreach (['quoted_price', 'final_price', 'invoice_id', 'payment_status', 'customer_user_id', 'customer_email'] as $column) {
            if (Schema::connection($connection)->hasColumn('ride_requests', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function enrichOwnRideRows(string $connection, array $rows): array
    {
        $rows = $this->enrichRideRows($connection, $rows);

        foreach ($rows as &$row) {
            $finalPrice = $row['final_price'] ?? null;
            $quotedPrice = $row['quoted_price'] ?? null;
            $price = $finalPrice !== null && $finalPrice !== '' ? $finalPrice : $quotedPrice;
            $row['display_price'] = $price !== null && $price !== '' ? (float) $price : null;

            $invoiceId = $row['invoice_id'] ?? null;
            if ($invoiceId !== null && $invoiceId !== '' && Schema::hasTable('invoices')) {
                $invoice = Invoice::query()->find((int) $invoiceId);
                if ($invoice !== null) {
                    $row['invoice_number'] = (string) $invoice->invoice_number;
                    $row['invoice_pdf_url'] = route('taxi.portal.api.invoices.pdf', $invoice);
                }
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ridesForDate(string $connection, int $companyId, string $date): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereDate('pickup_at', $date)
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingRides(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->where('pickup_at', '>=', now())
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function openRides(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereIn('status', [
                RideRequest::STATUS_DRAFT,
                RideRequest::STATUS_PENDING_DISPATCH,
                RideRequest::STATUS_OFFERED,
                RideRequest::STATUS_PENDING_PAYMENT,
            ])
            ->orderByDesc('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cancelledRides(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->where('status', RideRequest::STATUS_CANCELLED)
            ->orderByDesc('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ridesWithoutDriver(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereNull('driver_id')
            ->where('pickup_at', '>=', now()->startOfDay())
            ->whereNotIn('status', [RideRequest::STATUS_CANCELLED, RideRequest::STATUS_COMPLETED])
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ridesWithoutVehicle(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereNull('vehicle_id')
            ->where('pickup_at', '>=', now()->startOfDay())
            ->whereNotIn('status', [RideRequest::STATUS_CANCELLED, RideRequest::STATUS_COMPLETED])
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function airportRidesForDate(string $connection, int $companyId, string $date): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereDate('pickup_at', $date)
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('pickup_address', 'ilike', '%schiphol%')
                    ->orWhere('dropoff_address', 'ilike', '%schiphol%')
                    ->orWhere('pickup_address', 'ilike', '%luchthaven%')
                    ->orWhere('dropoff_address', 'ilike', '%luchthaven%')
                    ->orWhere('pickup_address', 'ilike', '%airport%')
                    ->orWhere('dropoff_address', 'ilike', '%airport%');
            })
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ridesBeforeTime(string $connection, int $companyId, string $time): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereDate('pickup_at', '>=', now()->toDateString())
            ->whereTime('pickup_at', '<', $time)
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function longRides(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->where('duration_seconds', '>', 3600)
            ->where('pickup_at', '>=', now()->startOfDay())
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->orderByDesc('duration_seconds')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get(array_merge($this->rideSelectColumns(), ['duration_seconds']))
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function availableDriversForDate(string $connection, int $companyId, string $date): array
    {
        $busyDriverIds = $this->busyDriverIdsForDate($connection, $companyId, $date);

        return $this->roleQueryService->chauffeursForCompany($companyId)
            ->limit(50)
            ->get(['id', 'first_name', 'last_name'])
            ->map(function (User $user) use ($busyDriverIds): array {
                $name = trim($user->first_name.' '.$user->last_name);

                return [
                    'driver_id' => $user->id,
                    'driver_name' => $name,
                    'available' => ! in_array($user->id, $busyDriverIds, true),
                ];
            })
            ->filter(fn (array $row) => $row['available'] === true)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function driversWithRidesOnDate(string $connection, int $companyId, string $date): array
    {
        $rides = $this->ridesForDate($connection, $companyId, $date);
        $grouped = [];

        foreach ($rides as $ride) {
            $driver = (string) ($ride['driver_name'] ?? '');
            if ($driver === '') {
                continue;
            }
            $grouped[$driver] = ($grouped[$driver] ?? 0) + 1;
        }

        $rows = [];
        foreach ($grouped as $name => $count) {
            $rows[] = ['driver_name' => $name, 'ride_count' => $count];
        }

        usort($rows, fn ($a, $b) => $b['ride_count'] <=> $a['ride_count']);

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topDriversByRidesOnDate(string $connection, int $companyId, string $date, int $limit): array
    {
        return array_slice($this->driversWithRidesOnDate($connection, $companyId, $date), 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function driversWithoutRideOnDate(string $connection, int $companyId, string $date): array
    {
        $busyDriverIds = $this->busyDriverIdsForDate($connection, $companyId, $date);

        return $this->roleQueryService->chauffeursForCompany($companyId)
            ->whereNotIn('id', $busyDriverIds === [] ? [0] : $busyDriverIds)
            ->limit(50)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $user) => [
                'driver_id' => $user->id,
                'driver_name' => trim($user->first_name.' '.$user->last_name),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function driversForAirportDate(string $connection, int $companyId, string $date): array
    {
        $rides = $this->airportRidesForDate($connection, $companyId, $date);
        $seen = [];
        $rows = [];

        foreach ($rides as $ride) {
            $driver = (string) ($ride['driver_name'] ?? '');
            if ($driver === '' || isset($seen[$driver])) {
                continue;
            }
            $seen[$driver] = true;
            $rows[] = [
                'driver_name' => $driver,
                'pickup_at' => $ride['pickup_at'] ?? null,
                'pickup_address' => $ride['pickup_address'] ?? null,
                'dropoff_address' => $ride['dropoff_address'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function driversOnTrip(string $connection, int $companyId, ?string $hint): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $statuses = $hint === 'niet_gestart'
            ? [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_ACCEPTED]
            : [RideRequest::STATUS_ACCEPTED, RideRequest::STATUS_ASSIGNED];

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereIn('status', $statuses)
            ->whereDate('pickup_at', now()->toDateString())
            ->whereNotNull('driver_id')
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topCustomersByRides(string $connection, int $companyId, int $limit): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        return DB::connection($connection)
            ->table('ride_requests')
            ->select('customer_name', DB::raw('COUNT(*) as ride_count'))
            ->where('company_id', $companyId)
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->whereNotNull('customer_name')
            ->groupBy('customer_name')
            ->orderByDesc('ride_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customersWithRideInMonth(string $connection, int $companyId, Carbon $month): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        return DB::connection($connection)
            ->table('ride_requests')
            ->select('customer_name', DB::raw('COUNT(*) as ride_count'))
            ->where('company_id', $companyId)
            ->whereBetween('pickup_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->whereNotNull('customer_name')
            ->groupBy('customer_name')
            ->orderByDesc('ride_count')
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customersWithAirportRide(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        return DB::connection($connection)
            ->table('ride_requests')
            ->select('customer_name', 'pickup_at', 'pickup_address', 'dropoff_address')
            ->where('company_id', $companyId)
            ->where('pickup_at', '>=', now()->startOfDay())
            ->where(function ($q) {
                $q->where('pickup_address', 'ilike', '%schiphol%')
                    ->orWhere('dropoff_address', 'ilike', '%schiphol%')
                    ->orWhere('pickup_address', 'ilike', '%luchthaven%')
                    ->orWhere('dropoff_address', 'ilike', '%luchthaven%');
            })
            ->orderBy('pickup_at')
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customersWithCancelledRide(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        return DB::connection($connection)
            ->table('ride_requests')
            ->select('customer_name', 'pickup_at', 'pickup_address', 'dropoff_address')
            ->where('company_id', $companyId)
            ->where('status', RideRequest::STATUS_CANCELLED)
            ->orderByDesc('pickup_at')
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function newCustomersThisMonth(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $start = now()->startOfMonth();

        return DB::connection($connection)
            ->table('ride_requests as r1')
            ->select('r1.customer_name', DB::raw('MIN(r1.created_at) as first_ride_at'), DB::raw('COUNT(*) as ride_count'))
            ->where('r1.company_id', $companyId)
            ->whereNotNull('r1.customer_name')
            ->groupBy('r1.customer_name')
            ->havingRaw('MIN(r1.created_at) >= ?', [$start])
            ->orderBy('first_ride_at')
            ->limit(50)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array{date: string, label: string, ride_count: int, total_amount: float, currency: string}
     */
    private function revenueForDate(string $connection, int $companyId, string $date, ?string $label = null): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return ['date' => $date, 'label' => $label ?? $date, 'ride_count' => 0, 'total_amount' => 0.0, 'currency' => 'EUR'];
        }

        $stats = DB::connection($connection)
            ->table('ride_requests')
            ->where('company_id', $companyId)
            ->whereDate('pickup_at', $date)
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->selectRaw('COUNT(*) as ride_count')
            ->selectRaw('COALESCE(SUM(COALESCE(final_price, quoted_price, 0)), 0) as total_amount')
            ->first();

        return [
            'date' => $date,
            'label' => $label ?? $date,
            'ride_count' => (int) ($stats->ride_count ?? 0),
            'total_amount' => round((float) ($stats->total_amount ?? 0), 2),
            'currency' => 'EUR',
        ];
    }

    /**
     * @return array{label: string, ride_count: int, total_amount: float, currency: string}
     */
    private function revenueForMonth(string $connection, int $companyId, \Illuminate\Support\Carbon $month): array
    {
        $label = 'vorige maand';
        if (! $this->tableExists($connection, 'ride_requests')) {
            return ['label' => $label, 'ride_count' => 0, 'total_amount' => 0.0, 'currency' => 'EUR'];
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $stats = DB::connection($connection)
            ->table('ride_requests')
            ->where('company_id', $companyId)
            ->whereBetween('pickup_at', [$start, $end])
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->selectRaw('COUNT(*) as ride_count')
            ->selectRaw('COALESCE(SUM(COALESCE(final_price, quoted_price, 0)), 0) as total_amount')
            ->first();

        return [
            'label' => $label,
            'ride_count' => (int) ($stats->ride_count ?? 0),
            'total_amount' => round((float) ($stats->total_amount ?? 0), 2),
            'currency' => 'EUR',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topRidesByRevenue(string $connection, int $companyId, int $limit = 10): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->select([
                'id', 'customer_name', 'pickup_address', 'dropoff_address', 'pickup_at', 'status',
            ])
            ->selectRaw('COALESCE(final_price, quoted_price, 0) as revenue')
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->orderByDesc('revenue')
            ->limit($limit);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function airportRidesCompletedThisMonth(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->where('company_id', $companyId)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->whereBetween('pickup_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where(function ($q) {
                $this->applyAirportAddressScope($q);
            })
            ->orderByDesc('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $query->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyAirportAddressScope($query): void
    {
        $query->where(function ($q) {
            $q->where('pickup_address', 'ilike', '%schiphol%')
                ->orWhere('dropoff_address', 'ilike', '%schiphol%')
                ->orWhere('pickup_address', 'ilike', '%luchthaven%')
                ->orWhere('dropoff_address', 'ilike', '%luchthaven%')
                ->orWhere('pickup_address', 'ilike', '%airport%')
                ->orWhere('dropoff_address', 'ilike', '%airport%');
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function planningQuery(string $connection, int $companyId, ?string $hint): array
    {
        return match ($hint) {
            'overlapping' => $this->overlappingRides($connection, $companyId),
            'binnen_uur' => $this->ridesWithinHour($connection, $companyId),
            default => $this->ridesWithoutDriver($connection, $companyId),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function overlappingRides(string $connection, int $companyId): array
    {
        $rides = $this->upcomingRides($connection, $companyId);
        $overlaps = [];

        for ($i = 0; $i < count($rides); $i++) {
            for ($j = $i + 1; $j < count($rides); $j++) {
                $a = $rides[$i];
                $b = $rides[$j];
                if (($a['driver_id'] ?? null) && $a['driver_id'] === ($b['driver_id'] ?? null)) {
                    $overlaps[] = [
                        'driver_name' => $a['driver_name'] ?? 'Onbekend',
                        'rit_a' => $a['id'] ?? null,
                        'rit_b' => $b['id'] ?? null,
                        'pickup_a' => $a['pickup_at'] ?? null,
                        'pickup_b' => $b['pickup_at'] ?? null,
                    ];
                }
            }
        }

        return array_slice($overlaps, 0, 50);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ridesWithinHour(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereBetween('pickup_at', [now(), now()->addHour()])
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->orderBy('pickup_at')
            ->limit(50);

        return $this->enrichRideRows(
            $connection,
            $this->scopeRidesForCompany($query, $connection, $companyId)
                ->get($this->rideSelectColumns())
                ->map(fn ($row) => (array) $row)
                ->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vehiclesScheduledForDate(string $connection, int $companyId, string $date): array
    {
        if (! $this->tableExists($connection, 'ride_requests') || ! $this->tableExists($connection, 'vehicles')) {
            return [];
        }

        $vehicleIds = DB::connection($connection)
            ->table('ride_requests')
            ->whereDate('pickup_at', $date)
            ->whereNotNull('vehicle_id')
            ->where('status', '<>', RideRequest::STATUS_CANCELLED)
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->distinct()
            ->pluck('vehicle_id')
            ->all();

        if ($vehicleIds === []) {
            return [];
        }

        return DB::connection($connection)
            ->table('vehicles')
            ->whereIn('id', $vehicleIds)
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'license_plate'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function availableVehicles(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'vehicles')) {
            return [];
        }

        return DB::connection($connection)
            ->table('vehicles')
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'license_plate', 'person_range'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function busyDriverIdsForDate(string $connection, int $companyId, string $date): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('ride_requests')
            ->whereDate('pickup_at', $date)
            ->whereNotNull('driver_id')
            ->where('status', '<>', RideRequest::STATUS_CANCELLED);

        return $this->scopeRidesForCompany($query, $connection, $companyId)
            ->pluck('driver_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function rideSelectColumns(): array
    {
        return [
            'id', 'customer_name', 'customer_phone', 'pickup_address', 'dropoff_address',
            'pickup_at', 'status', 'driver_id', 'vehicle_id',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function enrichRideRows(string $connection, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $vehicleIds = collect($rows)->pluck('vehicle_id')->filter()->unique()->values()->all();
        $driverIds = collect($rows)->pluck('driver_id')->filter()->unique()->values()->all();

        $vehicles = [];
        if ($vehicleIds !== [] && $this->tableExists($connection, 'vehicles')) {
            $vehicles = DB::connection($connection)->table('vehicles')->whereIn('id', $vehicleIds)->pluck('name', 'id')->all();
        }

        $drivers = [];
        if ($driverIds !== []) {
            $drivers = User::query()->whereIn('id', $driverIds)->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn (User $user) => [$user->id => trim($user->first_name.' '.$user->last_name)])
                ->all();
        }

        $statusLabels = RideRequest::statusLabels();

        return array_map(function (array $row) use ($vehicles, $drivers, $statusLabels): array {
            $vehicleId = $row['vehicle_id'] ?? null;
            $driverId = $row['driver_id'] ?? null;
            $status = (string) ($row['status'] ?? '');

            $row['vehicle_name'] = $vehicleId !== null ? ($vehicles[$vehicleId] ?? null) : null;
            $row['driver_name'] = $driverId !== null ? ($drivers[$driverId] ?? null) : null;
            $row['status_label'] = $statusLabels[$status] ?? $status;

            return $row;
        }, $rows);
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private function scopeRidesForCompany($query, string $connection, int $companyId)
    {
        return $query->where(function ($scoped) use ($connection, $companyId) {
            $scoped->where('company_id', $companyId);

            if ($this->tableExists($connection, 'vehicles')) {
                $scoped->orWhereIn('vehicle_id', function ($sub) use ($connection, $companyId) {
                    $sub->from('vehicles')->select('id')->where('company_id', $companyId);
                });
            }
        });
    }

    private function tableExists(string $connection, string $table): bool
    {
        return DB::connection($connection)->getSchemaBuilder()->hasTable($table);
    }

    private function resolveTaxiConnection(): ?string
    {
        try {
            $this->moduleDatabaseService->ensureModuleStorageReady('taxi');
        } catch (\Throwable) {
            return null;
        }

        $connection = $this->moduleDatabaseService->getModuleConnectionName('taxi');

        return is_array(config("database.connections.{$connection}")) ? $connection : null;
    }
}
