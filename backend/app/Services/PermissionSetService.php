<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermissionSetService
{
    /**
     * Define permission sets
     * Each set contains a name, description, and array of permission patterns
     */
    public static function getSets(): array
    {
        return [
            'full-crud' => [
                'name' => 'Full CRUD',
                'description' => 'Volledige toegang: bekijken, aanmaken, bewerken en verwijderen',
                'permissions' => ['view-', 'create-', 'edit-', 'delete-'],
            ],
            'view-only' => [
                'name' => 'Alleen Bekijken',
                'description' => 'Alleen bekijken, geen aanpassingen',
                'permissions' => ['view-'],
            ],
            'view-create' => [
                'name' => 'Bekijken & Aanmaken',
                'description' => 'Bekijken en nieuwe items aanmaken, geen bewerken of verwijderen',
                'permissions' => ['view-', 'create-'],
            ],
            'view-edit' => [
                'name' => 'Bekijken & Bewerken',
                'description' => 'Bekijken en bewerken, geen aanmaken of verwijderen',
                'permissions' => ['view-', 'edit-'],
            ],
            'no-delete' => [
                'name' => 'Alles Behalve Verwijderen',
                'description' => 'Volledige toegang behalve verwijderen',
                'permissions' => ['view-', 'create-', 'edit-'],
            ],
        ];
    }

    /**
     * Get permissions for a specific module and set
     */
    public static function getPermissionsForSet(string $module, string $setKey): array
    {
        $sets = self::getSets();
        
        if (!isset($sets[$setKey])) {
            return [];
        }

        $set = $sets[$setKey];
        $permissions = [];

        foreach ($set['permissions'] as $permissionPrefix) {
            $permissionName = $permissionPrefix . $module;
            $permissions[] = $permissionName;
        }

        return $permissions;
    }

    /**
     * Get all permissions for a set across all modules
     */
    public static function getAllPermissionsForSet(string $setKey, array $modules = null): array
    {
        if ($modules === null) {
            // Get all modules from existing permissions
            $modules = Permission::where('guard_name', 'web')
                ->whereNotNull('group')
                ->distinct()
                ->pluck('group')
                ->toArray();
        }

        $allPermissions = [];
        foreach ($modules as $module) {
            $permissions = self::getPermissionsForSet($module, $setKey);
            $allPermissions = array_merge($allPermissions, $permissions);
        }

        return $allPermissions;
    }

    /**
     * Check if a permission belongs to a set
     */
    public static function permissionBelongsToSet(string $permissionName, string $setKey): bool
    {
        $sets = self::getSets();
        
        if (!isset($sets[$setKey])) {
            return false;
        }

        $set = $sets[$setKey];
        
        foreach ($set['permissions'] as $prefix) {
            if (str_starts_with($permissionName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}



