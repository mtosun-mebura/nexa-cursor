<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Role management permissions
        $rolePermissions = [
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
        ];
        
        // Permission management permissions
        $permissionPermissions = [
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
        ];
        
        $allPermissions = array_merge($rolePermissions, $permissionPermissions);
        
        // Create permissions for web guard
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }
        
        // Create permissions for api guard
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
        }
        
        // Assign all permissions to super-admin role (web)
        $superAdmin = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->first();
        if ($superAdmin) {
            $webPermissions = Permission::where('guard_name', 'web')
                ->whereIn('name', $allPermissions)
                ->get();
            $superAdmin->givePermissionTo($webPermissions);
        }
        
        // Assign all permissions to super-admin role (api)
        $apiSuperAdmin = Role::where(['name' => 'super-admin', 'guard_name' => 'api'])->first();
        if ($apiSuperAdmin) {
            $apiPermissions = Permission::where('guard_name', 'api')
                ->whereIn('name', $allPermissions)
                ->get();
            $apiSuperAdmin->givePermissionTo($apiPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        $permissions = [
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
        ];
        
        Permission::whereIn('name', $permissions)->delete();
    }
};
