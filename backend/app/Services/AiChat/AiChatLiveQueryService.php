<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatIntent;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Voert uitsluitend vooraf goedgekeurde, tenant-gefilterde queries uit.
 * n8n mag geen vrije SQL doorgeven — alleen een intent + sql_token.
 */
final class AiChatLiveQueryService
{
    public function __construct(
        private readonly ModuleDatabaseService $moduleDatabaseService,
        private readonly AiChatSqlGuardService $sqlGuard,
    ) {}

    /**
     * @param  array{
     *     company_id: int,
     *     user_id: ?int,
     *     intent: string,
     *     allow_live_data: bool,
     *     allow_public_rates: bool,
     *     exp: int
     * }  $claims
     * @return array{count: int, rows: list<array<string, mixed>>}
     */
    public function execute(AiChatIntent $intent, array $claims): array
    {
        $this->sqlGuard->assertMayExecute($claims, $intent);

        $connection = $this->resolveTaxiConnection();
        if ($connection === null) {
            return ['count' => 0, 'rows' => []];
        }

        $companyId = (int) $claims['company_id'];

        $rows = match ($intent) {
            AiChatIntent::Tarieven => $this->publicRates($connection),
            AiChatIntent::RittenMorgen => $this->ridesForDate($connection, $companyId, now()->addDay()->toDateString()),
            AiChatIntent::RittenVandaag => $this->ridesForDate($connection, $companyId, now()->toDateString()),
            AiChatIntent::OpenRitten => $this->openRides($connection, $companyId),
            AiChatIntent::VrijeChauffeursMorgen => $this->availableDriversForDate($connection, $companyId, now()->addDay()->toDateString()),
            default => throw new RuntimeException('Geen live query beschikbaar voor intent '.$intent->value),
        };

        return [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * Publieke tarieven — uitsluitend default_rates (tenant via module-DB/schema).
     *
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
                'id',
                'person_range',
                'base_fare',
                'min_fare',
                'price_per_km',
                'price_per_min',
                'cleaning_costs',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ridesForDate(string $connection, int $companyId, string $date): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        return DB::connection($connection)
            ->table('ride_requests')
            ->where('company_id', $companyId)
            ->whereDate('pickup_at', $date)
            ->where('status', '<>', 'cancelled')
            ->orderBy('pickup_at')
            ->limit(50)
            ->get([
                'id',
                'customer_name',
                'pickup_address',
                'dropoff_address',
                'pickup_at',
                'status',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function openRides(string $connection, int $companyId): array
    {
        if (! $this->tableExists($connection, 'ride_requests')) {
            return [];
        }

        return DB::connection($connection)
            ->table('ride_requests')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'offered', 'awaiting_confirmation', 'draft'])
            ->orderByDesc('pickup_at')
            ->limit(50)
            ->get([
                'id',
                'customer_name',
                'pickup_address',
                'dropoff_address',
                'pickup_at',
                'status',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function availableDriversForDate(string $connection, int $companyId, string $date): array
    {
        if (! $this->tableExists($connection, 'vehicles')) {
            return [];
        }

        $busyDriverIds = [];
        if ($this->tableExists($connection, 'ride_requests')) {
            $busyDriverIds = DB::connection($connection)
                ->table('ride_requests')
                ->where('company_id', $companyId)
                ->whereDate('pickup_at', $date)
                ->whereNotNull('driver_id')
                ->where('status', '<>', 'cancelled')
                ->pluck('driver_id')
                ->all();
        }

        $query = DB::connection($connection)
            ->table('vehicles')
            ->where('company_id', $companyId)
            ->where('active', true);

        return $query
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'license_plate', 'person_range'])
            ->map(function ($row) use ($busyDriverIds): array {
                $vehicle = (array) $row;
                $vehicle['available'] = ! in_array($vehicle['id'] ?? null, $busyDriverIds, true);

                return $vehicle;
            })
            ->all();
    }

    private function tableExists(string $connection, string $table): bool
    {
        return DB::connection($connection)->getSchemaBuilder()->hasTable($table);
    }

    private function resolveTaxiConnection(): ?string
    {
        $connection = $this->moduleDatabaseService->getModuleConnectionName('taxi');

        if (! is_array(config("database.connections.{$connection}"))) {
            return null;
        }

        return $connection;
    }
}
