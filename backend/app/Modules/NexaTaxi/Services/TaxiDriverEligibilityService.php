<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TaxiDriverEligibilityService
{
    /** @var list<string> */
    private const CHAUFFEUR_ROLE_NAMES = ['chauffeur', 'taxi-chauffeur', 'taxi_chauffeur', 'taxichauffeur'];

    public function isChauffeurForCompany(User $user, int $companyId): bool
    {
        return $this->buildChauffeurQuery($companyId)
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * @return Builder<User>
     */
    public function buildChauffeurQuery(int $companyId): Builder
    {
        if ($companyId <= 0) {
            return User::query()->whereRaw('1 = 0');
        }

        $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
        $rolesTable = DB::getTablePrefix().config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));

        $query = User::query()
            ->where('company_id', $companyId)
            ->whereExists(function ($sub) use ($companyId, $pivot, $rolesTable, $teamKey, $morphTypes) {
                $sub->select(DB::raw('1'))
                    ->from($pivot)
                    ->join($rolesTable, $rolesTable.'.id', '=', $pivot.'.role_id')
                    ->whereColumn($pivot.'.model_id', 'users.id')
                    ->whereIn($pivot.'.model_type', $morphTypes)
                    ->where(function ($q) use ($pivot, $teamKey, $companyId) {
                        $q->where($pivot.'.'.$teamKey, $companyId)
                            ->orWhere(function ($q2) use ($pivot, $teamKey, $companyId) {
                                $q2->whereNull($pivot.'.'.$teamKey)
                                    ->where('users.company_id', $companyId);
                            });
                    })
                    ->whereIn($rolesTable.'.guard_name', ['web', 'api'])
                    ->where(function ($q) use ($rolesTable) {
                        foreach (self::CHAUFFEUR_ROLE_NAMES as $i => $slug) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            $q->{$method}('LOWER(TRIM('.$rolesTable.'.name)) = ?', [$slug]);
                        }
                    });
            })
            ->orderBy('first_name')
            ->orderBy('last_name');

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active')) {
            $query->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            });
        }

        return $query;
    }

    /**
     * Online chauffeurs voor dispatch (zonder locatie-filter in fase 1).
     *
     * @return list<int> user ids
     */
    public function onlineDriverIdsForCompany(string $conn, int $companyId, int $limit = 8): array
    {
        $onlineIds = [];
        if (TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            $onlineIds = DriverAvailability::on($conn)
                ->where('company_id', $companyId)
                ->where('is_online', true)
                ->orderByDesc('last_seen_at')
                ->limit($limit * 2)
                ->pluck('driver_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($onlineIds === []) {
            return $this->buildChauffeurQuery($companyId)->limit($limit)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $eligible = $this->buildChauffeurQuery($companyId)
            ->whereIn('id', $onlineIds)
            ->limit($limit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($eligible) < $limit) {
            $more = $this->buildChauffeurQuery($companyId)
                ->whereNotIn('id', $eligible)
                ->limit($limit - count($eligible))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $eligible = array_merge($eligible, $more);
        }

        return $eligible;
    }
}
