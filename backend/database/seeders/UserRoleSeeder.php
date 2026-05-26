<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ModuleSchemaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or find roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Create or find users (super admin wachtwoord uit ModuleSchemaService)
        $superAdmin = User::firstOrCreate(
            ['email' => ModuleSchemaService::SUPERADMIN_EMAIL],
            [
                'first_name' => 'Mehmet',
                'last_name' => 'Tosun',
                'password' => Hash::make(ModuleSchemaService::SUPERADMIN_PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        // Assign roles to users
        $superAdmin->syncRoles([$superAdminRole]);

        // Ensure Mehmet Tosun has super-admin role
        $mehmetUser = User::where('email', ModuleSchemaService::SUPERADMIN_EMAIL)->first();
        if ($mehmetUser) {
            $mehmetUser->syncRoles([$superAdminRole]);
        }

        $this->command->info('Users and roles assigned successfully!');
        $this->command->info('Super Admin: '.ModuleSchemaService::SUPERADMIN_EMAIL.' (wachtwoord: in ModuleSchemaService)');
    }
}
