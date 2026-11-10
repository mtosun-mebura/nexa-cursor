<?php

namespace Database\Seeders;

use App\Services\DurableUserCredentials;
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

        // Alleen aanmaken als ontbrekend — bestaand wachtwoord nooit overschrijven (draait bij elke deploy).
        $superAdmin = app(DurableUserCredentials::class)->ensureByEmail(
            ModuleSchemaService::SUPERADMIN_EMAIL,
            [
                'first_name' => 'Mehmet',
                'last_name' => 'Tosun',
                'password' => Hash::make(ModuleSchemaService::SUPERADMIN_PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->syncRoles([$superAdminRole]);

        $this->command?->info('Users and roles assigned successfully!');
        $this->command?->info('Super Admin: '.ModuleSchemaService::SUPERADMIN_EMAIL.' (bestaand wachtwoord blijft behouden)');
    }
}
