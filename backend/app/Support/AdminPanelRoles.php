<?php

namespace App\Support;

use App\Models\User;

final class AdminPanelRoles
{
    /** Rollen die het admin-panel mogen gebruiken. */
    public const PANEL = ['super-admin', 'company-admin', 'marketplace', 'staff', 'demo'];

    /** Tenant-beheerderrollen (company-admin of marketplace). */
    public const TENANT_ADMIN = ['company-admin', 'marketplace'];

    /** Systeemrollen die niet via de UI verwijderd mogen worden. */
    public const SYSTEM = ['super-admin', 'company-admin', 'marketplace', 'staff', 'candidate', 'demo'];

    public static function canAccessPanel(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return count(array_intersect($user->webRoleNames(), self::PANEL)) > 0;
    }

    public static function isTenantAdmin(User $user): bool
    {
        return count(array_intersect($user->webRoleNames(), self::TENANT_ADMIN)) > 0;
    }

    public static function canManageCompanySubscription(User $user): bool
    {
        return $user->canManageCompanySubscription();
    }

    public static function isSystemRole(string $name): bool
    {
        return in_array($name, self::SYSTEM, true);
    }
}
