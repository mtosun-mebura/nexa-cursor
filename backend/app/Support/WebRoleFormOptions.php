<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

final class WebRoleFormOptions
{
    /**
     * Eén formulier-optie per rolnaam (Spatie teams: dezelfde name/guard per company_id).
     *
     * @param  Collection<int, Role>  $roles
     * @return Collection<int, Role>
     */
    public static function dedupe(Collection $roles): Collection
    {
        $teamKey = config('permission.column_names.team_foreign_key', 'company_id');

        return $roles
            ->sortBy(fn (Role $role) => [
                strtolower(trim((string) $role->name)),
                $role->getAttribute($teamKey) === null ? 0 : 1,
                (int) $role->getKey(),
            ])
            ->unique(fn (Role $role) => strtolower(trim((string) $role->name)))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
