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
                self::normalizeName((string) $role->name),
                $role->getAttribute($teamKey) === null ? 0 : 1,
                (int) $role->getKey(),
            ])
            ->unique(fn (Role $role) => self::normalizeName((string) $role->name))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public static function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * @param  list<mixed>  $selectedRoles
     */
    public static function isSelected(string $roleName, array $selectedRoles): bool
    {
        $needle = self::normalizeName($roleName);

        foreach ($selectedRoles as $selected) {
            if (self::normalizeName((string) $selected) === $needle) {
                return true;
            }
        }

        return false;
    }
}
