<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Toegang tot chauffeur-inkomsten via Spatie-rechten.
 * Rol "chauffeur-inkomsten" kan in Beheer → Rollen aan chauffeurs worden toegekend.
 */
class TaxiDriverEarningsAccessService
{
    public const PERMISSION_VIEW = 'earnings.view';

    public const PERMISSION_VIEW_MONTH = 'earnings.view_month';

    public const ROLE_NAME = 'chauffeur-inkomsten';

    /**
     * @return array{view: bool, view_month: bool}
     */
    public function permissionsFor(User $user, int $companyId): array
    {
        $this->ensureRegistered();

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($companyId > 0 ? $companyId : null);

        try {
            $view = $this->userCan($user, self::PERMISSION_VIEW);
            $viewMonth = $view && $this->userCan($user, self::PERMISSION_VIEW_MONTH);

            return [
                'view' => $view,
                'view_month' => $viewMonth,
            ];
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    public function ensureRegistered(): void
    {
        foreach ([self::PERMISSION_VIEW, self::PERMISSION_VIEW_MONTH] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }

        foreach (['web', 'api'] as $guard) {
            $role = Role::firstOrCreate(['name' => self::ROLE_NAME, 'guard_name' => $guard]);
            $perms = Permission::query()
                ->where('guard_name', $guard)
                ->whereIn('name', [self::PERMISSION_VIEW, self::PERMISSION_VIEW_MONTH])
                ->get();
            foreach ($perms as $perm) {
                if (! $role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
        }
    }

    private function userCan(User $user, string $permission): bool
    {
        try {
            return $user->can($permission)
                || $user->hasPermissionTo($permission, 'web')
                || $user->hasPermissionTo($permission, 'api');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }
}
