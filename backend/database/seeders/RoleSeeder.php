<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles for web guard (if they don't exist)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $companyAdmin = Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $candidate = Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

        // Create roles for api guard (if they don't exist)
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'api']);

        // Create permissions
        $permissions = [
            // User management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'assign-roles',
            
            // Company management
            'view-companies',
            'create-companies',
            'edit-companies',
            'delete-companies',
            
            // Vacancy management
            'view-vacancies',
            'create-vacancies',
            'edit-vacancies',
            'delete-vacancies',
            'publish-vacancies',
            
            // Category management
            'view-categories',
            'create-categories',
            'edit-categories',
            'delete-categories',
            
            // Job configuration management
            'view-job-configurations',
            'create-job-configurations',
            'edit-job-configurations',
            'delete-job-configurations',
            
            // Match management
            'view-matches',
            'create-matches',
            'edit-matches',
            'delete-matches',
            'approve-matches',
            
            // Interview management
            'view-interviews',
            'create-interviews',
            'edit-interviews',
            'delete-interviews',
            'schedule-interviews',
            
            // Notification management
            'view-notifications',
            'create-notifications',
            'edit-notifications',
            'delete-notifications',
            'send-notifications',
            
            // Email template management
            'view-email-templates',
            'create-email-templates',
            'edit-email-templates',
            'delete-email-templates',
            
            // Dashboard access
            'view-dashboard',
            'view-tenant-dashboard',
            
            // Agenda access
            'view-agenda',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Get API roles
        $apiSuperAdmin = Role::where(['name' => 'super-admin', 'guard_name' => 'api'])->first();
        $apiCompanyAdmin = Role::where(['name' => 'company-admin', 'guard_name' => 'api'])->first();
        $apiStaff = Role::where(['name' => 'staff', 'guard_name' => 'api'])->first();
        $apiCandidate = Role::where(['name' => 'candidate', 'guard_name' => 'api'])->first();

        // Get API permissions
        $apiPermissions = Permission::where('guard_name', 'api')->get();

        // Assign all permissions to super admin (web)
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'web')->get());

        // Assign all permissions to super admin (api)
        $apiSuperAdmin->givePermissionTo($apiPermissions);

        // Assign company-specific permissions to company admin (web)
        $companyAdmin->givePermissionTo([
            'view-users',
            'create-users',
            'edit-users',
            'view-vacancies',
            'create-vacancies',
            'edit-vacancies',
            'delete-vacancies',
            'publish-vacancies',
            'view-matches',
            'edit-matches',
            'approve-matches',
            'view-interviews',
            'create-interviews',
            'edit-interviews',
            'schedule-interviews',
            'view-notifications',
            'create-notifications',
            'send-notifications',
            'view-email-templates',
            'edit-email-templates',
            'view-tenant-dashboard',
            'view-agenda',
        ]);

        // Assign company-specific permissions to company admin (api)
        $apiCompanyAdmin->givePermissionTo([
            'view-users',
            'create-users',
            'edit-users',
            'view-vacancies',
            'create-vacancies',
            'edit-vacancies',
            'delete-vacancies',
            'publish-vacancies',
            'view-matches',
            'edit-matches',
            'approve-matches',
            'view-interviews',
            'create-interviews',
            'edit-interviews',
            'schedule-interviews',
            'view-notifications',
            'create-notifications',
            'send-notifications',
            'view-email-templates',
            'edit-email-templates',
            'view-tenant-dashboard',
            'view-agenda',
        ]);

        // Assign limited permissions to staff (web)
        $staff->givePermissionTo([
            'view-vacancies',
            'create-vacancies',
            'edit-vacancies',
            'view-matches',
            'view-interviews',
            'create-interviews',
            'view-notifications',
            'view-tenant-dashboard',
            'view-agenda',
        ]);

        // Assign limited permissions to staff (api)
        $apiStaff->givePermissionTo([
            'view-vacancies',
            'create-vacancies',
            'edit-vacancies',
            'view-matches',
            'view-interviews',
            'create-interviews',
            'view-notifications',
            'view-tenant-dashboard',
            'view-agenda',
        ]);

        // Assign candidate permissions (web)
        $candidate->givePermissionTo([
            'view-vacancies',
            'view-matches',
            'view-interviews',
            'view-agenda',
        ]);

        // Assign candidate permissions (api)
        $apiCandidate->givePermissionTo([
            'view-vacancies',
            'view-matches',
            'view-interviews',
            'view-agenda',
        ]);

        // Create Super Admin user
        $superAdminUser = User::updateOrCreate(
            ['email' => 'm.tosun@mebura.nl'],
            [
                'password' => Hash::make('!'),
                'first_name' => 'Mehmet',
                'last_name' => 'Tosun',
                'email_verified_at' => now(),
            ]
        );

        // Assign role with null team (global super admin)
        $superAdminUser->assignRole($superAdmin, null);
    }
}
