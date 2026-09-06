<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxiAppPresenceService
{
    /** @var list<string> */
    public const CONTRACT_ROLE_NAMES = [
        TaxiContractPortalAccessService::SPATIE_CONTRACTANT,
        TaxiContractPortalAccessService::SPATIE_CONTRACTOUDER,
    ];

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected TaxiDriverEligibilityService $driverEligibility,
    ) {}

    /**
     * @param  Collection<int, User>|iterable<User>  $users
     * @return array<int, array{chauffeur: array{applicable: bool, online: bool}, contract: array{applicable: bool, online: bool}}>
     */
    public function forUsers(iterable $users): array
    {
        $users = Collection::make($users)->values();
        if ($users->isEmpty()) {
            return [];
        }

        $ids = $users->map(fn (User $user) => (int) $user->id)->filter(fn (int $id) => $id > 0)->values()->all();
        $roleNamesByUserId = $this->webRoleNamesByUserId($ids);
        $onlineByDriverId = $this->chauffeurOnlineByDriverId($ids);
        $contractUserIds = $this->activeContractPortalUserIds($ids);

        $result = [];
        foreach ($users as $user) {
            $id = (int) $user->id;
            $roleNames = $roleNamesByUserId[$id] ?? [];
            $isChauffeur = $this->driverEligibility->rolesIncludeChauffeur($roleNames)
                || array_key_exists($id, $onlineByDriverId);
            $isContract = $this->rolesIncludeContract($roleNames)
                || isset($contractUserIds[$id]);

            $result[$id] = [
                'chauffeur' => [
                    'applicable' => $isChauffeur,
                    'online' => $isChauffeur && ($onlineByDriverId[$id] ?? false),
                ],
                'contract' => [
                    'applicable' => $isContract,
                    'online' => false,
                ],
            ];
        }

        return $result;
    }

    /**
     * @return array{chauffeur: array{applicable: bool, online: bool}, contract: array{applicable: bool, online: bool}}
     */
    public function emptyPresence(): array
    {
        return [
            'chauffeur' => ['applicable' => false, 'online' => false],
            'contract' => ['applicable' => false, 'online' => false],
        ];
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, bool>
     */
    private function chauffeurOnlineByDriverId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return [];
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return [];
        }

        $map = [];
        foreach (DriverAvailability::on($conn)->whereIn('driver_id', $userIds)->get(['driver_id', 'is_online']) as $row) {
            $map[(int) $row->driver_id] = (bool) $row->is_online;
        }

        return $map;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, true>
     */
    private function activeContractPortalUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        try {
            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
            app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);
        } catch (\Throwable) {
            return [];
        }

        if (! Schema::connection($conn)->hasTable('transport_customer_portal_users')) {
            return [];
        }

        $ids = TransportCustomerPortalUser::on($conn)
            ->whereIn('user_id', $userIds)
            ->where('active', true)
            ->pluck('user_id');

        $map = [];
        foreach ($ids as $id) {
            $map[(int) $id] = true;
        }

        return $map;
    }

    /**
     * Team-onafhankelijk, web + api (zelfde bron als {@see User::assignedRoleNames()}).
     *
     * @param  list<int>  $userIds
     * @return array<int, list<string>>
     */
    private function webRoleNamesByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $pivot = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));

        $rows = DB::table($pivot)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivot}.{$rolePivotKey}")
            ->whereIn("{$pivot}.{$morphKey}", $userIds)
            ->whereIn("{$pivot}.model_type", $morphTypes)
            ->whereIn("{$rolesTable}.guard_name", ['web', 'api'])
            ->get(["{$pivot}.{$morphKey} as user_id", "{$rolesTable}.name as role_name"]);

        $map = [];
        foreach ($rows as $row) {
            $id = (int) $row->user_id;
            $map[$id] ??= [];
            $name = (string) $row->role_name;
            if (! in_array($name, $map[$id], true)) {
                $map[$id][] = $name;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $roleNames
     */
    private function rolesIncludeContract(array $roleNames): bool
    {
        $normalized = array_map(static fn ($name) => strtolower(trim((string) $name)), $roleNames);

        return count(array_intersect($normalized, self::CONTRACT_ROLE_NAMES)) > 0;
    }
}
