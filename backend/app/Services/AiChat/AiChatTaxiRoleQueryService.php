<?php

namespace App\Services\AiChat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class AiChatTaxiRoleQueryService
{
    /**
     * @param  list<string>  $roleNamesLower
     * @return Builder<User>
     */
    public function usersWithRoles(int $companyId, array $roleNamesLower): Builder
    {
        if ($companyId <= 0 || $roleNamesLower === []) {
            return User::query()->whereRaw('1 = 0');
        }

        $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
        $rolesTable = DB::getTablePrefix().config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));

        return User::query()
            ->where('company_id', $companyId)
            ->whereExists(function ($sub) use ($companyId, $pivot, $rolesTable, $teamKey, $morphTypes, $roleNamesLower) {
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
                    ->where(function ($q) use ($rolesTable, $roleNamesLower) {
                        foreach ($roleNamesLower as $i => $slug) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            $q->{$method}('LOWER(TRIM('.$rolesTable.'.name)) = ?', [$slug]);
                        }
                    });
            })
            ->orderBy('first_name')
            ->orderBy('last_name');
    }

    /**
     * @return Builder<User>
     */
    public function chauffeursForCompany(int $companyId): Builder
    {
        return $this->usersWithRoles($companyId, [
            'chauffeur', 'taxi-chauffeur', 'taxi_chauffeur', 'taxichauffeur',
        ]);
    }
}
