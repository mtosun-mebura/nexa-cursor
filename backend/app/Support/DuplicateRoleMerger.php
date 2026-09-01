<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie-rollen met dezelfde naam (andere hoofdletters) samenvoegen naar de slug uit de seeder.
 */
final class DuplicateRoleMerger
{
    public static function mergeCaseDuplicates(): void
    {
        $rolesTable = config('permission.table_names.roles');
        $pivot = config('permission.table_names.model_has_roles');
        $rolePerms = config('permission.table_names.role_has_permissions');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';

        if (! Schema::hasTable($rolesTable) || ! Schema::hasTable($pivot)) {
            return;
        }

        $roles = DB::table($rolesTable)->orderBy('id')->get();
        $groups = $roles->groupBy(function ($role) use ($teamKey) {
            $team = $role->{$teamKey} ?? '';

            return WebRoleFormOptions::normalizeName((string) $role->name)
                .'|'.$role->guard_name
                .'|'.(string) $team;
        });

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $canonical = $group->first(
                fn ($role) => (string) $role->name === WebRoleFormOptions::normalizeName((string) $role->name)
            ) ?? $group->first();

            foreach ($group as $duplicate) {
                if ((int) $duplicate->id === (int) $canonical->id) {
                    continue;
                }

                self::repointPivots(
                    $pivot,
                    $rolePivotKey,
                    $morphKey,
                    $teamKey,
                    (int) $duplicate->id,
                    (int) $canonical->id
                );

                if (Schema::hasTable($rolePerms)) {
                    DB::table($rolePerms)->where('role_id', $duplicate->id)->delete();
                }

                DB::table($rolesTable)->where('id', $duplicate->id)->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private static function repointPivots(
        string $pivot,
        string $rolePivotKey,
        string $morphKey,
        string $teamKey,
        int $fromRoleId,
        int $toRoleId
    ): void {
        $rows = DB::table($pivot)->where($rolePivotKey, $fromRoleId)->get();

        foreach ($rows as $row) {
            $existsQuery = DB::table($pivot)
                ->where($rolePivotKey, $toRoleId)
                ->where($morphKey, $row->{$morphKey})
                ->where('model_type', $row->model_type);

            if (Schema::hasColumn($pivot, $teamKey)) {
                $team = $row->{$teamKey} ?? null;
                if ($team === null) {
                    $existsQuery->whereNull($teamKey);
                } else {
                    $existsQuery->where($teamKey, $team);
                }
            }

            if ($existsQuery->exists()) {
                DB::table($pivot)
                    ->where($rolePivotKey, $fromRoleId)
                    ->where($morphKey, $row->{$morphKey})
                    ->where('model_type', $row->model_type)
                    ->when(
                        Schema::hasColumn($pivot, $teamKey),
                        function ($q) use ($row, $teamKey) {
                            $team = $row->{$teamKey} ?? null;
                            if ($team === null) {
                                $q->whereNull($teamKey);
                            } else {
                                $q->where($teamKey, $team);
                            }
                        }
                    )
                    ->delete();

                continue;
            }

            DB::table($pivot)
                ->where($rolePivotKey, $fromRoleId)
                ->where($morphKey, $row->{$morphKey})
                ->where('model_type', $row->model_type)
                ->when(
                    Schema::hasColumn($pivot, $teamKey),
                    function ($q) use ($row, $teamKey) {
                        $team = $row->{$teamKey} ?? null;
                        if ($team === null) {
                            $q->whereNull($teamKey);
                        } else {
                            $q->where($teamKey, $team);
                        }
                    }
                )
                ->update([$rolePivotKey => $toRoleId]);
        }
    }
}
