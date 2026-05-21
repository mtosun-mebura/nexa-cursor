<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Meerdere web-rollen per gebruiker (Spatie teams: company_id op pivot, super-admin globaal).
 */
class UserRoleAssignmentService
{
    /**
     * @param  list<string>  $roleNames
     */
    public function syncWebRoles(User $user, array $roleNames): void
    {
        $roleNames = array_values(array_unique(array_filter(array_map(
            fn ($name) => is_string($name) ? trim($name) : '',
            $roleNames
        ))));

        if ($roleNames === []) {
            return;
        }

        $this->removeAllWebRoles($user);

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        $hasSuperAdmin = in_array('super-admin', $roleNames, true);
        $teamRoleNames = array_values(array_filter(
            $roleNames,
            fn (string $name) => $name !== 'super-admin'
        ));

        // Eerst bedrijfsrollen in één keer (syncRoles binnen team-context).
        if ($teamRoleNames !== []) {
            $teamId = $user->company_id ? (int) $user->company_id : null;
            $registrar->setPermissionsTeamId($teamId);
            $user->syncRoles($teamRoleNames);
            $user->unsetRelation('roles');
        }

        // Super-admin apart (andere team-context), zodat bedrijfsrollen behouden blijven.
        if ($hasSuperAdmin) {
            $registrar->setPermissionsTeamId(null);
            $user->assignRole(Role::findByName('super-admin', 'web'));
            $user->unsetRelation('roles');
        }

        $registrar->setPermissionsTeamId($previousTeamId);
        $registrar->forgetCachedPermissions();
    }

    private function removeAllWebRoles(User $user): void
    {
        $pivot = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

        $morphTypes = array_values(array_unique(array_filter([
            $user->getMorphClass(),
            User::class,
        ])));

        $webRoleIds = Role::query()
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($webRoleIds->isEmpty()) {
            return;
        }

        DB::table($pivot)
            ->where($morphKey, $user->getKey())
            ->whereIn('model_type', $morphTypes)
            ->whereIn($rolePivotKey, $webRoleIds->all())
            ->delete();
    }
}
