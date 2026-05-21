<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use App\Services\ModuleSchemaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $seedingModule = config('module_database.seeding_module');
        if ($seedingModule !== null && $seedingModule !== '') {
            $this->runForModule($seedingModule);

            return;
        }

        $this->runFull();
    }

    /**
     * Seed alleen de rechten en rollen die bij deze module horen (op basis van registerPermissions()).
     * Gebruikt expliciet de module-connection zodat User/Role/Permission in de module-DB terechtkomen.
     */
    protected function runForModule(string $moduleName): void
    {
        $dbService = app(ModuleDatabaseService::class);
        if (! $dbService->supportsModuleDatabases()) {
            return;
        }

        $conn = $dbService->getModuleConnectionName($moduleName);
        $previousDefault = Config::get('database.default');
        Config::set('database.default', $conn);
        try {
            $this->runForModuleOnConnection($moduleName, $conn);
        } finally {
            Config::set('database.default', $previousDefault);
        }
    }

    protected function runForModuleOnConnection(string $moduleName, string $conn): void
    {
        $moduleManager = app(ModuleManager::class);
        $module = $moduleManager->loadModule($moduleName);
        if (! $module) {
            return;
        }

        $permissionNames = $module->registerPermissions();
        if (empty($permissionNames)) {
            $permissionNames = ['view-dashboard'];
        }

        foreach ($permissionNames as $name) {
            Permission::on($conn)->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            Permission::on($conn)->firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }

        $superAdmin = Role::on($conn)->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::on($conn)->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);

        $webPerms = Permission::on($conn)->whereIn('name', $permissionNames)->where('guard_name', 'web')->get();
        $superAdmin->syncPermissions($webPerms);

        $apiSuperAdmin = Role::on($conn)->where(['name' => 'super-admin', 'guard_name' => 'api'])->first();
        $apiPerms = Permission::on($conn)->whereIn('name', $permissionNames)->where('guard_name', 'api')->get();
        $apiSuperAdmin->syncPermissions($apiPerms);

        $superAdminUser = User::on($conn)->updateOrCreate(
            ['email' => ModuleSchemaService::SUPERADMIN_EMAIL],
            [
                'password' => Hash::make(ModuleSchemaService::SUPERADMIN_PASSWORD),
                'first_name' => 'Mehmet',
                'last_name' => 'Tosun',
                'email_verified_at' => now(),
            ]
        );
        $superAdminUser->assignRole($superAdmin, null);
    }

    protected function runFull(): void
    {
        // Create roles for web guard (if they don't exist)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $companyAdmin = Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $candidate = Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);

        // Create roles for api guard (if they don't exist)
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'api']);

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

            // Role management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Permission management
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',

            // Branches (Skillmatching admin)
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',

            // Skillmatching module (menu + controllers; parallel aan view-* permissies)
            'skillmatching.vacancies.view',
            'skillmatching.vacancies.create',
            'skillmatching.vacancies.edit',
            'skillmatching.vacancies.delete',
            'skillmatching.matches.view',
            'skillmatching.matches.create',
            'skillmatching.interviews.view',
            'skillmatching.interviews.create',
            'skillmatching.interviews.edit',
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

        // Company admin: alle tenant-/Beheer-rechten, geen Systeem (rollen/permissies) en geen nieuwe tenants aanmaken/verwijderen
        $excludeForCompanyAdmin = [
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
            'create-companies',
            'delete-companies',
        ];

        $companyAdminWebPerms = Permission::where('guard_name', 'web')
            ->whereNotIn('name', $excludeForCompanyAdmin)
            ->get();
        $companyAdmin->syncPermissions($companyAdminWebPerms);

        $companyAdminApiPerms = Permission::where('guard_name', 'api')
            ->whereNotIn('name', $excludeForCompanyAdmin)
            ->get();
        $apiCompanyAdmin->syncPermissions($companyAdminApiPerms);

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

        // Create Super Admin user (wachtwoord uit ModuleSchemaService)
        $superAdminUser = User::updateOrCreate(
            ['email' => ModuleSchemaService::SUPERADMIN_EMAIL],
            [
                'password' => Hash::make(ModuleSchemaService::SUPERADMIN_PASSWORD),
                'first_name' => 'Mehmet',
                'last_name' => 'Tosun',
                'email_verified_at' => now(),
            ]
        );

        // Assign role with null team (global super admin)
        $superAdminUser->assignRole($superAdmin, null);
    }
}
